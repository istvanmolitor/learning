<?php

namespace App\Services\CourseTransfer;

use App\Enums\ContentBlockType;
use App\Models\ContentBlock;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\HTMLToMarkdown\HtmlConverter;
use RuntimeException;
use ZipArchive;

class CourseExporter
{
    private readonly HtmlConverter $htmlConverter;

    public function __construct()
    {
        $this->htmlConverter = new HtmlConverter(['strip_tags' => false]);
    }

    /**
     * Build a course export zip and return the path to the generated file.
     */
    public function export(Course $course): string
    {
        $course->loadMissing(['lessons.pages.contentBlocks', 'lessons.questions.answerOptions']);

        $zipPath = tempnam(sys_get_temp_dir(), 'course-export-');

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Nem sikerült létrehozni az export zip fájlt.');
        }

        $root = Str::slug($course->slug) ?: 'course';
        /** @var string[] $usedImageNames */
        $usedImageNames = [];

        $manifest = [
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $course->description,
            'is_published' => $course->is_published,
            'order' => $course->order,
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
                $zip->addFromString("{$root}/{$file}", $this->renderPageMarkdown($page, $zip, $root, $usedImageNames));

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

    /**
     * @param  string[]  $usedImageNames
     */
    private function renderPageMarkdown(Page $page, ZipArchive $zip, string $root, array &$usedImageNames): string
    {
        $parts = [];

        foreach ($page->contentBlocks as $block) {
            $parts[] = match ($block->type) {
                ContentBlockType::Heading => str_repeat('#', max(1, min(6, (int) ($block->data['level'] ?? 2)))).' '.($block->data['text'] ?? ''),
                ContentBlockType::TextBlock => trim($this->htmlConverter->convert($block->data['html'] ?? '')),
                ContentBlockType::Image => $this->renderImageBlock($block, $zip, $root, $usedImageNames),
                ContentBlockType::YoutubeVideo => sprintf(
                    '<iframe width="560" height="315" src="https://www.youtube.com/embed/%s" title="%s" frameborder="0" allowfullscreen></iframe>',
                    e($block->data['video_id'] ?? ''),
                    e($block->data['title'] ?? '')
                ),
            };
        }

        return implode("\n\n", array_filter($parts, fn ($part) => $part !== ''));
    }

    /**
     * @param  string[]  $usedImageNames
     */
    private function renderImageBlock(ContentBlock $block, ZipArchive $zip, string $root, array &$usedImageNames): string
    {
        $path = $block->data['path'] ?? null;
        $contents = $path ? Storage::disk('public')->get($path) : null;

        if (! $path || $contents === null) {
            return '';
        }

        $filename = $this->uniqueImageName($path, $usedImageNames);
        $zip->addFromString("{$root}/images/{$filename}", $contents);

        $alt = e($block->data['alt'] ?? '');
        $caption = $block->data['caption'] ?? null;

        $html = "<figure><img src=\"images/{$filename}\" alt=\"{$alt}\">";

        if ($caption) {
            $html .= '<figcaption>'.e($caption).'</figcaption>';
        }

        return $html.'</figure>';
    }

    /**
     * @param  string[]  $usedImageNames
     */
    private function uniqueImageName(string $path, array &$usedImageNames): string
    {
        $name = basename($path);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        $candidate = $name;
        $i = 1;

        while (in_array($candidate, $usedImageNames, true)) {
            $candidate = $extension ? "{$base}-{$i}.{$extension}" : "{$base}-{$i}";
            $i++;
        }

        $usedImageNames[] = $candidate;

        return $candidate;
    }
}
