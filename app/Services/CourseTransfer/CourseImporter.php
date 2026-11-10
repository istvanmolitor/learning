<?php

namespace App\Services\CourseTransfer;

use App\Enums\ContentBlockType;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class CourseImporter
{
    /**
     * Import a course export zip. If it contains a course.json, its slug decides
     * whether an existing course is updated (full resync) or a new one is created.
     * If it has no course.json (just loose/foldered .md files), a course is created
     * from a synthesized manifest, left for the admin to fill in afterwards.
     */
    public function import(UploadedFile $file, ?int $createdBy = null): Course
    {
        $extractPath = storage_path('app/tmp/course-import-'.Str::random(12));

        $zip = new ZipArchive;

        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('A feltöltött fájl nem érvényes zip archívum.');
        }

        $zip->extractTo($extractPath);
        $zip->close();

        try {
            $manifestRoot = $this->locateManifestRoot($extractPath);

            if ($manifestRoot !== null) {
                $manifest = json_decode($this->readFile($manifestRoot.'/course.json'), true, flags: JSON_THROW_ON_ERROR);

                return $this->importManifest($manifest, $manifestRoot, $createdBy);
            }

            $markdownRoot = $this->resolveMarkdownRoot($extractPath);
            $manifest = $this->synthesizeManifest($markdownRoot, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

            return $this->importManifest($manifest, $markdownRoot, $createdBy);
        } finally {
            File::deleteDirectory($extractPath);
        }
    }

    /**
     * Import loose .md files (no zip, no course.json): each uploaded file becomes
     * its own single-page lesson in a newly created placeholder course.
     *
     * @param  UploadedFile[]  $files
     */
    public function importMarkdownFiles(array $files, ?int $createdBy = null): Course
    {
        $root = storage_path('app/tmp/course-import-'.Str::random(12));
        File::makeDirectory($root, recursive: true);

        try {
            foreach ($files as $file) {
                $file->move($root, $file->getClientOriginalName());
            }

            $manifest = $this->synthesizeManifest($root, 'Importált kurzus');

            return $this->importManifest($manifest, $root, $createdBy);
        } finally {
            File::deleteDirectory($root);
        }
    }

    /**
     * Import a bare course.json manifest, with no zip and no content files at all: creates (or
     * fully resyncs, see importManifest()) the course skeleton — lessons/pages/questions exactly
     * as declared — but every page's content is left empty, since there is nothing to fill it
     * with. The admin edits pages' content afterwards in the UI.
     */
    public function importManifestFile(UploadedFile $file, ?int $createdBy = null): Course
    {
        $manifest = json_decode($this->readFile($file->getRealPath()), true, flags: JSON_THROW_ON_ERROR);

        return $this->importManifest($manifest, null, $createdBy);
    }

    /**
     * Read narration (.txt/.mp3 sibling files) from a source directory into an already-imported
     * course's existing Lesson/Page records, matched by slug. Does not create or delete anything,
     * so it is safe to re-run against a course that already has enrollments/completions.
     */
    public function syncNarration(string $root): int
    {
        $manifest = json_decode($this->readFile($root.'/course.json'), true, flags: JSON_THROW_ON_ERROR);

        $course = Course::where('slug', $manifest['slug'] ?? Str::slug($manifest['title']))->first();

        if (! $course) {
            throw new RuntimeException("Nincs importált kurzus ezzel a slug-gal: {$manifest['slug']}");
        }

        $updated = 0;

        foreach ($manifest['lessons'] ?? [] as $lessonData) {
            $lesson = $course->lessons()->where('slug', $lessonData['slug'])->first();

            if (! $lesson) {
                continue;
            }

            foreach ($lessonData['pages'] ?? [] as $pageData) {
                $page = $lesson->pages()->where('slug', $pageData['slug'])->first();

                if (! $page) {
                    continue;
                }

                $pagePath = $root.'/'.$pageData['file'];

                $changes = array_filter([
                    'narration_text' => $this->readSiblingFile($pagePath, 'txt'),
                    'narration_audio_path' => $this->importNarrationAudio($pagePath, $course->id),
                ], fn ($value) => $value !== null);

                if ($changes === []) {
                    continue;
                }

                $page->update($changes);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Create the course if its manifest slug is new, or fully resync an existing one:
     * lessons/pages/questions in the manifest are created/updated by slug, and anything
     * belonging to the course that is no longer in the manifest is deleted.
     */
    private function importManifest(array $manifest, ?string $root, ?int $createdBy): Course
    {
        return DB::transaction(function () use ($manifest, $root, $createdBy) {
            $slug = $manifest['slug'] ?? Str::slug($manifest['title']);
            $course = Course::where('slug', $slug)->first();

            $attributes = [
                'title' => $manifest['title'],
                'description' => $manifest['description'] ?? null,
                'is_published' => $manifest['is_published'] ?? false,
                'order' => $manifest['order'] ?? 0,
            ];

            if ($course) {
                $course->update($attributes);
            } else {
                $course = Course::create([
                    ...$attributes,
                    'slug' => $slug,
                    'created_by' => $createdBy,
                ]);
            }

            if ($createdBy !== null) {
                $course->enrollments()->firstOrCreate(
                    ['user_id' => $createdBy],
                    ['assigned_by' => $createdBy, 'assigned_at' => now()],
                );
            }

            $this->syncLessons($course, $manifest['lessons'] ?? [], $root);

            return $course;
        });
    }

    private function syncLessons(Course $course, array $lessonsData, ?string $root): void
    {
        $keepSlugs = array_column($lessonsData, 'slug');
        $course->lessons()->whereNotIn('slug', $keepSlugs)->get()->each(fn (Lesson $lesson) => $lesson->delete());

        foreach ($lessonsData as $lessonData) {
            $lesson = $course->lessons()->updateOrCreate(
                ['slug' => $lessonData['slug']],
                ['title' => $lessonData['title'], 'order' => $lessonData['order'] ?? 0],
            );

            $this->syncPages($lesson, $lessonData['pages'] ?? [], $root, $course->id);
            $this->syncQuestions($lesson, $lessonData['questions'] ?? []);
        }
    }

    private function syncPages(Lesson $lesson, array $pagesData, ?string $root, int $courseId): void
    {
        $keepSlugs = array_column($pagesData, 'slug');
        $lesson->pages()->whereNotIn('slug', $keepSlugs)->get()->each(fn ($page) => $page->delete());

        foreach ($pagesData as $pageData) {
            if ($root === null) {
                $body = '';
                $narrationChanges = [];
            } else {
                $pagePath = $root.'/'.$pageData['file'];
                $body = $this->rewriteImageSources($this->pageBodyHtml($pagePath), $root, $courseId);

                $narrationChanges = array_filter([
                    'narration_text' => $this->readSiblingFile($pagePath, 'txt'),
                    'narration_audio_path' => $this->importNarrationAudio($pagePath, $courseId),
                ], fn ($value) => $value !== null);
            }

            $page = $lesson->pages()->where('slug', $pageData['slug'])->first();

            if ($page) {
                $page->update([
                    'title' => $pageData['title'],
                    'order' => $pageData['order'] ?? 0,
                    ...$narrationChanges,
                ]);
            } else {
                $page = $lesson->pages()->create([
                    'title' => $pageData['title'],
                    'slug' => $pageData['slug'],
                    'order' => $pageData['order'] ?? 0,
                    ...$narrationChanges,
                ]);
            }

            $page->contentBlocks()->delete();
            $page->contentBlocks()->create([
                'type' => ContentBlockType::TextBlock,
                'data' => ['html' => $body],
                'order' => 0,
            ]);
        }
    }

    private function syncQuestions(Lesson $lesson, array $questionsData): void
    {
        $lesson->questions()->delete();

        foreach ($questionsData as $questionData) {
            $question = $lesson->questions()->create([
                'text' => $questionData['text'],
                'order' => $questionData['order'] ?? 0,
            ]);

            foreach ($questionData['answer_options'] ?? [] as $optionData) {
                $question->answerOptions()->create([
                    'text' => $optionData['text'],
                    'is_correct' => $optionData['is_correct'] ?? false,
                    'order' => $optionData['order'] ?? 0,
                ]);
            }
        }
    }

    /**
     * Build a course manifest from a directory of loose/foldered .md files (no course.json):
     * each top-level folder becomes a lesson (one page per .md file inside it, folder = lesson),
     * and any .md file directly at the root becomes its own single-page lesson.
     */
    private function synthesizeManifest(string $root, string $titleFallback): array
    {
        $lessons = [];

        $looseFiles = glob($root.'/*.md') ?: [];
        sort($looseFiles);

        foreach ($looseFiles as $mdFile) {
            $name = pathinfo($mdFile, PATHINFO_FILENAME);

            $lessons[] = [
                'title' => Str::headline($name),
                'slug' => Str::slug($name),
                'order' => count($lessons),
                'pages' => [[
                    'title' => Str::headline($name),
                    'slug' => Str::slug($name),
                    'order' => 0,
                    'file' => basename($mdFile),
                ]],
                'questions' => [],
            ];
        }

        $dirs = glob($root.'/*', GLOB_ONLYDIR) ?: [];
        sort($dirs);

        foreach ($dirs as $dir) {
            $mdFiles = glob($dir.'/*.md') ?: [];

            if ($mdFiles === []) {
                continue;
            }

            sort($mdFiles);
            $folderName = basename($dir);
            $pages = [];

            foreach ($mdFiles as $index => $mdFile) {
                $pageName = pathinfo($mdFile, PATHINFO_FILENAME);

                $pages[] = [
                    'title' => Str::headline($pageName),
                    'slug' => Str::slug($pageName),
                    'order' => $index,
                    'file' => $folderName.'/'.basename($mdFile),
                ];
            }

            $lessons[] = [
                'title' => Str::headline($folderName),
                'slug' => Str::slug($folderName),
                'order' => count($lessons),
                'pages' => $pages,
                'questions' => [],
            ];
        }

        if ($lessons === []) {
            throw new RuntimeException('A feltöltött fájlok nem tartalmaznak course.json-t, és .md oldalfájlt sem találtunk bennük.');
        }

        $title = Str::headline($titleFallback) ?: $titleFallback;

        return [
            'title' => $title,
            'slug' => Str::slug($title) ?: Str::random(8),
            'description' => null,
            'is_published' => false,
            'order' => 0,
            'lessons' => $lessons,
        ];
    }

    /**
     * .md content is typically one level deeper than the extracted zip root, wrapped in a
     * single top-level folder (the zip's own name). Unwrap one such level if present.
     */
    private function resolveMarkdownRoot(string $extractPath): string
    {
        if ($this->hasMarkdownContent($extractPath)) {
            return $extractPath;
        }

        $dirs = glob($extractPath.'/*', GLOB_ONLYDIR) ?: [];

        if (count($dirs) === 1 && $this->hasMarkdownContent($dirs[0])) {
            return $dirs[0];
        }

        return $extractPath;
    }

    private function hasMarkdownContent(string $dir): bool
    {
        if (glob($dir.'/*.md') !== []) {
            return true;
        }

        foreach (glob($dir.'/*', GLOB_ONLYDIR) ?: [] as $sub) {
            if (glob($sub.'/*.md') !== []) {
                return true;
            }
        }

        return false;
    }

    private function readFile(string $path): string
    {
        $contents = file_exists($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new RuntimeException("A várt fájl hiányzik a zip archívumból: {$path}");
        }

        return $contents;
    }

    private function locateManifestRoot(string $extractPath): ?string
    {
        if (file_exists($extractPath.'/course.json')) {
            return $extractPath;
        }

        foreach (glob($extractPath.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (file_exists($dir.'/course.json')) {
                return $dir;
            }
        }

        return null;
    }

    private function readSiblingFile(string $pagePath, string $extension): ?string
    {
        $path = preg_replace('/\.(html|md)$/i', '.'.$extension, $pagePath);

        if (! file_exists($path)) {
            return null;
        }

        return trim($this->readFile($path)) ?: null;
    }

    private function importNarrationAudio(string $pagePath, int $courseId): ?string
    {
        $path = preg_replace('/\.(html|md)$/i', '.mp3', $pagePath);

        if (! file_exists($path)) {
            return null;
        }

        $storedPath = "course-media/{$courseId}/narration/".Str::random(12).'.mp3';
        Storage::disk('public')->put($storedPath, $this->readFile($path));

        return $storedPath;
    }

    /**
     * Resolve a page's content file (.md or legacy .html) into its HTML body.
     */
    private function pageBodyHtml(string $path): string
    {
        $contents = $this->readFile($path);

        if (Str::endsWith(strtolower($path), '.md')) {
            return Str::markdown($contents);
        }

        return $this->extractBody($contents);
    }

    private function extractBody(string $html): string
    {
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $matches)) {
            return trim($matches[1]);
        }

        return trim($html);
    }

    private function rewriteImageSources(string $html, string $root, int $courseId): string
    {
        return preg_replace_callback(
            '/src="images\/([^"]+)"/i',
            function (array $matches) use ($root, $courseId) {
                $sourcePath = $root.'/images/'.$matches[1];

                if (! file_exists($sourcePath)) {
                    return $matches[0];
                }

                $storedPath = "course-media/{$courseId}/".Str::random(12).'-'.basename($matches[1]);
                Storage::disk('public')->put($storedPath, $this->readFile($sourcePath));

                return 'src="'.Storage::disk('public')->url($storedPath).'"';
            },
            $html
        ) ?? $html;
    }
}
