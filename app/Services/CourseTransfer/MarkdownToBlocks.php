<?php

namespace App\Services\CourseTransfer;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Str;

/**
 * Converts a page's legacy content (real Markdown, or raw HTML stored
 * verbatim in the same column — both already supported by CourseImporter)
 * into an ordered list of content blocks. Used by:
 *   - php artisan pages:convert-to-blocks, to migrate existing pages once;
 *   - CourseImporter, so importing a v1 (.md/.html) package still lands the
 *     page in block form, ready to edit in the new blocks editor.
 *
 * Consecutive "plain prose" elements (paragraphs, lists, …) are grouped into
 * a single `text` block rather than one block per tag — matching the rule
 * that a block boundary should be a real content boundary, not every
 * sentence.
 */
class MarkdownToBlocks
{
    /**
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public static function convert(string $markdown): array
    {
        // Str::markdown() runs league/commonmark's DisallowedRawHtmlExtension,
        // which escapes <iframe> (a hardcoded security deny-list — script,
        // style, iframe, textarea, … — unrelated to html_input) wherever it
        // appears, so a YouTube/Vimeo embed would come back as dead escaped
        // text. Iframes are therefore pulled out and parsed directly from
        // the raw source first; everything else still goes through
        // Str::markdown() + the usual DOM walk, in original order.
        $blocks = [];

        foreach (self::splitIframes($markdown) as $chunk) {
            if ($chunk['iframe'] !== null) {
                $blocks[] = $chunk['iframe'];

                continue;
            }

            array_push($blocks, ...self::fromHtml(Str::markdown($chunk['markdown'])));
        }

        return $blocks;
    }

    /**
     * @return array<int, array{markdown: string, iframe: ?array{type: string, data: array<string, mixed>}}>
     */
    private static function splitIframes(string $markdown): array
    {
        $parts = preg_split('/(<iframe\b[^>]*>.*?<\/iframe>)/is', $markdown, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$markdown];

        return array_map(function (string $part) {
            if (! preg_match('/^<iframe\b/i', trim($part))) {
                return ['markdown' => $part, 'iframe' => null];
            }

            return ['markdown' => '', 'iframe' => self::parseIframe($part) ?? ['type' => 'text', 'data' => ['html' => $part]]];
        }, $parts);
    }

    private static function parseIframe(string $iframeHtml): ?array
    {
        if (! preg_match('/src="([^"]*)"/i', $iframeHtml, $src)) {
            return null;
        }

        preg_match('/title="([^"]*)"/i', $iframeHtml, $titleMatch);
        $title = isset($titleMatch[1]) ? html_entity_decode($titleMatch[1]) : null;

        if (preg_match('/youtube(?:-nocookie)?\.com\/embed\/([A-Za-z0-9_-]+)/i', $src[1], $id)) {
            return ['type' => 'video', 'data' => ['source' => 'youtube', 'video_id' => $id[1], 'title' => $title]];
        }

        if (preg_match('/player\.vimeo\.com\/video\/(\d+)/i', $src[1], $id)) {
            return ['type' => 'video', 'data' => ['source' => 'vimeo', 'video_id' => $id[1], 'title' => $title]];
        }

        return null;
    }

    /**
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public static function fromHtml(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $dom->getElementsByTagName('div')->item(0);

        if ($root === null) {
            return [];
        }

        $blocks = [];
        $pendingText = '';

        $flush = function () use (&$blocks, &$pendingText) {
            if (trim($pendingText) !== '') {
                $blocks[] = ['type' => 'text', 'data' => ['html' => trim($pendingText)]];
            }
            $pendingText = '';
        };

        foreach (iterator_to_array($root->childNodes) as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                if (trim($node->textContent) !== '') {
                    $pendingText .= $dom->saveHTML($node);
                }

                continue;
            }

            if (! $node instanceof DOMElement) {
                continue;
            }

            $block = self::elementToBlock($dom, $node);

            if ($block !== null) {
                $flush();
                $blocks[] = $block;

                continue;
            }

            $pendingText .= $dom->saveHTML($node);
        }

        $flush();

        return $blocks;
    }

    /**
     * @return array{type: string, data: array<string, mixed>}|null null means
     *                                                              "treat as plain prose instead"
     */
    private static function elementToBlock(DOMDocument $dom, DOMElement $node): ?array
    {
        $tag = strtolower($node->tagName);

        return match (true) {
            in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) => [
                'type' => 'heading',
                'data' => ['level' => max(2, min(4, (int) substr($tag, 1))), 'text' => trim($node->textContent)],
            ],
            $tag === 'hr' => ['type' => 'divider', 'data' => ['variant' => 'line']],
            $tag === 'table' => ['type' => 'table', 'data' => self::tableData($node)],
            $tag === 'blockquote' => ['type' => 'quote', 'data' => ['html' => self::innerHtml($dom, $node)]],
            $tag === 'figure' => self::figureImage($node),
            $tag === 'audio' => self::audio($node),
            $tag === 'p' && self::isSoleImageParagraph($node) => self::imageElement($node->getElementsByTagName('img')->item(0)),
            default => null,
        };
    }

    /**
     * @return array{headers: string[], rows: array<int, string[]>}
     */
    private static function tableData(DOMElement $table): array
    {
        $headers = [];

        foreach ($table->getElementsByTagName('th') as $th) {
            $headers[] = trim($th->textContent);
        }

        $rows = [];

        foreach ($table->getElementsByTagName('tr') as $tr) {
            $cells = $tr->getElementsByTagName('td');

            if ($cells->length === 0) {
                continue;
            }

            $row = [];

            foreach ($cells as $td) {
                $row[] = trim($td->textContent);
            }

            $rows[] = $row;
        }

        if ($rows === []) {
            $rows[] = array_fill(0, max(1, count($headers)), '');
        }

        if ($headers === []) {
            $headers = array_fill(0, count($rows[0]), '');
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    private static function figureImage(DOMElement $figure): ?array
    {
        $img = $figure->getElementsByTagName('img')->item(0);

        if ($img === null) {
            return null;
        }

        $block = self::imageElement($img);
        $caption = $figure->getElementsByTagName('figcaption')->item(0);

        if ($caption !== null) {
            $block['data']['caption'] = trim($caption->textContent);
        }

        return $block;
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    private static function imageElement(DOMElement $img): array
    {
        return [
            'type' => 'image',
            'data' => [
                'path' => self::extractPath($img->getAttribute('src')),
                'alt' => $img->getAttribute('alt') ?: null,
                'caption' => null,
                'size' => 'normal',
            ],
        ];
    }

    private static function audio(DOMElement $audio): ?array
    {
        $src = $audio->getAttribute('src');

        if ($src === '') {
            return null;
        }

        return ['type' => 'audio', 'data' => ['path' => self::extractPath($src), 'title' => null]];
    }

    private static function isSoleImageParagraph(DOMElement $p): bool
    {
        if ($p->getElementsByTagName('img')->length !== 1) {
            return false;
        }

        return trim(preg_replace('/\s+/u', '', $p->textContent) ?? '') === '';
    }

    private static function innerHtml(DOMDocument $dom, DOMElement $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return trim($html);
    }

    /**
     * A /storage/… URL (any host, or host-relative) is reduced to its
     * public-disk-relative path, matching what ImageBlock/VideoBlock/etc.
     * expect in `path`. Anything else (an external URL) is kept verbatim —
     * the block classes' mediaUrl() helper already knows to use such a
     * value as-is instead of re-resolving it through the disk.
     */
    private static function extractPath(string $src): ?string
    {
        if ($src === '') {
            return null;
        }

        if (preg_match('#^(?:https?://[^/]+)?/storage/(.+)$#i', $src, $matches)) {
            return $matches[1];
        }

        return $src;
    }
}
