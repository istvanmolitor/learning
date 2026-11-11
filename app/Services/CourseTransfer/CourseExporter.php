<?php

namespace App\Services\CourseTransfer;

use App\Models\Course;
use App\Models\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class CourseExporter
{
    /**
     * Build a course export zip and return the path to the generated file.
     *
     * The package is the canonical transfer format: importing it into an empty
     * database and exporting again must yield an identical package (lossless
     * round trip). It therefore contains every piece of course material —
     * manifest, Markdown pages, images, narration, thumbnail — but no
     * user/instance data (enrollments, completions, question responses).
     */
    public function export(Course $course): string
    {
        $course->loadMissing(['lessons.pages', 'lessons.questions.answerOptions']);

        $zipPath = tempnam(sys_get_temp_dir(), 'course-export-');

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Nem sikerült létrehozni az export zip fájlt.');
        }

        $root = Str::slug($course->slug) ?: 'course';
        /** @var array<string, string> $imageNames storage path => name inside the zip's images/ dir */
        $imageNames = [];

        $manifest = [
            'format_version' => CourseImporter::FORMAT_VERSION,
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $course->description,
            'is_published' => $course->is_published,
            'order' => $course->order,
            'thumbnail' => $this->exportThumbnail($course, $zip, $root),
            'lessons' => [],
        ];

        foreach ($course->lessons as $lesson) {
            $lessonManifest = [
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'order' => $lesson->order,
                'pages' => [],
                'questions' => [],
            ];

            foreach ($lesson->pages as $page) {
                $file = "pages/{$lesson->slug}/{$page->slug}.md";
                $zip->addFromString("{$root}/{$file}", $this->renderPageMarkdown($page, $zip, $root, $imageNames));

                if ($page->narration_text) {
                    $zip->addFromString("{$root}/pages/{$lesson->slug}/{$page->slug}.txt", $page->narration_text);
                }

                if ($page->narration_audio_path) {
                    $audio = Storage::disk('public')->get($page->narration_audio_path);

                    if ($audio !== null) {
                        $zip->addFromString("{$root}/pages/{$lesson->slug}/{$page->slug}.mp3", $audio);
                    }
                }

                $lessonManifest['pages'][] = [
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'order' => $page->order,
                    'file' => $file,
                ];
            }

            foreach ($lesson->questions as $question) {
                $lessonManifest['questions'][] = [
                    'text' => $question->text,
                    'order' => $question->order,
                    'answer_options' => $question->answerOptions->map(fn ($option) => [
                        'text' => $option->text,
                        'is_correct' => $option->is_correct,
                        'order' => $option->order,
                    ])->all(),
                ];
            }

            $manifest['lessons'][] = $lessonManifest;
        }

        $zip->addFromString(
            "{$root}/course.json",
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        $zip->close();

        return $zipPath;
    }

    private function exportThumbnail(Course $course, ZipArchive $zip, string $root): ?string
    {
        if (! $course->thumbnail) {
            return null;
        }

        $contents = Storage::disk('public')->get($course->thumbnail);

        if ($contents === null) {
            return null;
        }

        $extension = pathinfo($course->thumbnail, PATHINFO_EXTENSION) ?: 'png';
        $file = "thumbnail.{$extension}";
        $zip->addFromString("{$root}/{$file}", $contents);

        return $file;
    }

    /**
     * A page's Markdown goes into the zip as-is, except that image references
     * pointing into public storage (/storage/…) are copied into the package's
     * images/ directory and rewritten to package-relative paths — the exact
     * inverse of what the importer does.
     *
     * @param  array<string, string>  $imageNames
     */
    private function renderPageMarkdown(Page $page, ZipArchive $zip, string $root, array &$imageNames): string
    {
        $markdown = $page->markdown ?? '';

        $rewrite = function (array $matches) use ($zip, $root, &$imageNames) {
            [$full, $prefix, $storagePath, $suffix] = $matches;

            $contents = Storage::disk('public')->get($storagePath);

            if ($contents === null) {
                return $full;
            }

            if (! isset($imageNames[$storagePath])) {
                $imageNames[$storagePath] = $this->uniqueImageName($storagePath, $imageNames);
                $zip->addFromString("{$root}/images/{$imageNames[$storagePath]}", $contents);
            }

            return $prefix.'images/'.$imageNames[$storagePath].$suffix;
        };

        $markdown = preg_replace_callback('/(src=")(?:https?:\/\/[^"\/]+)?\/storage\/([^"]+)(")/i', $rewrite, $markdown) ?? $markdown;

        return preg_replace_callback('/(\]\()(?:https?:\/\/[^)\s\/]+)?\/storage\/([^)\s]+)(\))/', $rewrite, $markdown) ?? $markdown;
    }

    /**
     * @param  array<string, string>  $imageNames
     */
    private function uniqueImageName(string $path, array $imageNames): string
    {
        $name = basename($path);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        $candidate = $name;
        $i = 1;

        while (in_array($candidate, $imageNames, true)) {
            $candidate = $extension ? "{$base}-{$i}.{$extension}" : "{$base}-{$i}";
            $i++;
        }

        return $candidate;
    }
}
