<?php

namespace App\Services\Publishing;

use App\Models\Page;
use Illuminate\Support\Facades\Storage;

/**
 * Transforms a page's rendered HTML (the body_html markdown cache) for
 * publication targets that cannot use the web asset pipeline (PDF, PPTX):
 * storage image URLs become local file paths, embedded videos become
 * link cards, since neither target can play an iframe.
 */
class PageHtml
{
    public static function forPdf(Page $page): string
    {
        $html = $page->body_html ?? '';

        $html = preg_replace_callback(
            '/src="(?:https?:\/\/[^"\/]+)?\/storage\/([^"]+)"/i',
            function (array $matches) {
                $absolute = Storage::disk('public')->path($matches[1]);

                return is_file($absolute) ? 'src="'.e($absolute).'"' : $matches[0];
            },
            $html
        ) ?? $html;

        $html = preg_replace_callback(
            '/<iframe[^>]*src="[^"]*youtube[^"]*\/embed\/([A-Za-z0-9_-]+)[^"]*"[^>]*(?:title="([^"]*)")?[^>]*><\/iframe>/i',
            fn (array $matches) => self::videoCard($matches[1], $matches[2] ?? ''),
            $html
        ) ?? $html;

        return str_replace('<figure>', '<figure class="image-block">', $html);
    }

    private static function videoCard(string $videoId, string $title): string
    {
        $label = $title !== '' ? e($title) : 'YouTube videó megtekintése';

        return <<<HTML
        <a class="video-card" href="https://www.youtube.com/watch?v={$videoId}">
            <div class="video-card-icon"><span>&#9654;</span></div>
            <div class="video-card-body">
                <span class="video-card-label">VIDEÓ</span>
                <span class="video-card-title">{$label}</span>
                <span class="video-card-url">youtube.com/watch?v={$videoId}</span>
            </div>
        </a>
        HTML;
    }
}
