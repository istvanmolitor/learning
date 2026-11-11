<?php

namespace App\Services\Publishing;

use App\Models\Page;
use App\Services\CourseTransfer\MarkdownSplitter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Breaks a page's Markdown into slide-sized units along the same ## boundaries
 * MarkdownSplitter uses for over-long pages, then reduces each unit's rendered
 * HTML to what a slide can show: a heading, plain body text, at most one local
 * image (first one found), and at most one video link (first one found).
 */
class MarkdownSlideSplitter
{
    /**
     * @return array<int, array{title: string, text: string, image_path: ?string, video: ?array{title: string, url: string}}>
     */
    public static function slides(Page $page): array
    {
        $chunks = MarkdownSplitter::split($page->markdown ?? '');

        return array_map(function (array $chunk) use ($page) {
            $html = Str::markdown($chunk['markdown']);

            return [
                'title' => $chunk['title'] ?? $page->title,
                'text' => self::plainText($html),
                'image_path' => self::firstImagePath($html),
                'video' => self::firstVideo($html),
            ];
        }, $chunks);
    }

    private static function firstImagePath(string $html): ?string
    {
        if (! preg_match('/<img[^>]*src="(?:https?:\/\/[^"\/]+)?\/storage\/([^"]+)"/i', $html, $matches)) {
            return null;
        }

        $path = Storage::disk('public')->path($matches[1]);

        return is_file($path) ? $path : null;
    }

    /**
     * @return array{title: string, url: string}|null
     */
    private static function firstVideo(string $html): ?array
    {
        if (! preg_match('/<iframe[^>]*src="[^"]*youtube[^"]*\/embed\/([A-Za-z0-9_-]+)[^"]*"[^>]*(?:title="([^"]*)")?[^>]*><\/iframe>/i', $html, $matches)) {
            return null;
        }

        $title = $matches[2] ?? '';

        return [
            'title' => $title !== '' ? html_entity_decode($title) : 'YouTube videó',
            'url' => "https://www.youtube.com/watch?v={$matches[1]}",
        ];
    }

    private static function plainText(string $html): string
    {
        // Drop the chunk's own heading (already used as the slide title) and any
        // media markup — only prose should end up in the slide body text.
        $html = preg_replace('/<h[1-6][^>]*>.*?<\/h[1-6]>/is', '', $html, limit: 1);
        $html = preg_replace('/<figure.*?<\/figure>/is', '', $html);
        $html = preg_replace('/<iframe.*?<\/iframe>/is', '', $html);

        $html = preg_replace('/<li[^>]*>/i', "\n• ", $html);
        $html = preg_replace('/<\/(p|h[1-6]|li|blockquote)>/i', "\n", $html);

        $text = trim(html_entity_decode(strip_tags($html)));

        return trim(preg_replace("/\n{3,}/", "\n\n", $text));
    }
}
