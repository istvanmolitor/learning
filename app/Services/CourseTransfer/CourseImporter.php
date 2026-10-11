<?php

namespace App\Services\CourseTransfer;

use App\Enums\ContentBlockType;
use App\Models\Course;
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
     * Import a course export zip and create the Course/Lesson/Page/ContentBlock/Question records.
     */
    public function import(UploadedFile $file): Course
    {
        $extractPath = storage_path('app/tmp/course-import-'.Str::random(12));

        $zip = new ZipArchive;

        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('A feltöltött fájl nem érvényes zip archívum.');
        }

        $zip->extractTo($extractPath);
        $zip->close();

        try {
            $root = $this->locateCourseRoot($extractPath);
            $manifest = json_decode($this->readFile($root.'/course.json'), true, flags: JSON_THROW_ON_ERROR);

            return DB::transaction(function () use ($manifest, $root) {
                $course = Course::create([
                    'title' => $manifest['title'],
                    'slug' => $this->uniqueCourseSlug($manifest['slug'] ?? Str::slug($manifest['title'])),
                    'description' => $manifest['description'] ?? null,
                    'is_published' => $manifest['is_published'] ?? false,
                    'order' => $manifest['order'] ?? 0,
                ]);

                foreach ($manifest['lessons'] ?? [] as $lessonData) {
                    $lesson = $course->lessons()->create([
                        'title' => $lessonData['title'],
                        'slug' => $lessonData['slug'],
                        'order' => $lessonData['order'] ?? 0,
                    ]);

                    foreach ($lessonData['pages'] ?? [] as $pageData) {
                        $html = $this->readFile($root.'/'.$pageData['file']);
                        $body = $this->rewriteImageSources($this->extractBody($html), $root, $course->id);

                        $page = $lesson->pages()->create([
                            'title' => $pageData['title'],
                            'slug' => $pageData['slug'],
                            'order' => $pageData['order'] ?? 0,
                        ]);

                        $page->contentBlocks()->create([
                            'type' => ContentBlockType::TextBlock,
                            'data' => ['html' => $body],
                            'order' => 0,
                        ]);
                    }

                    foreach ($lessonData['questions'] ?? [] as $questionData) {
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

                return $course;
            });
        } finally {
            File::deleteDirectory($extractPath);
        }
    }

    private function readFile(string $path): string
    {
        $contents = file_exists($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new RuntimeException("A várt fájl hiányzik a zip archívumból: {$path}");
        }

        return $contents;
    }

    private function locateCourseRoot(string $extractPath): string
    {
        if (file_exists($extractPath.'/course.json')) {
            return $extractPath;
        }

        foreach (glob($extractPath.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (file_exists($dir.'/course.json')) {
                return $dir;
            }
        }

        throw new RuntimeException('A zip archívum nem tartalmaz course.json fájlt.');
    }

    private function uniqueCourseSlug(string $slug): string
    {
        $base = $slug;
        $i = 2;

        while (Course::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
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
