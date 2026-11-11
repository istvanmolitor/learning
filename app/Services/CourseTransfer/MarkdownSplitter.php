<?php

namespace App\Services\CourseTransfer;

use Illuminate\Support\Str;

/**
 * Splits an over-long Markdown document into several page-sized documents along
 * its H2 (##) headings. Content before the first H2 stays with the first chunk.
 */
class MarkdownSplitter
{
    public const WORD_LIMIT = 3000;

    public const BYTE_LIMIT = 51200;

    public static function isTooLong(string $markdown): bool
    {
        return strlen($markdown) > self::BYTE_LIMIT
            || str_word_count(strip_tags($markdown)) > self::WORD_LIMIT;
    }

    /**
     * @return array<int, array{title: ?string, markdown: string}> one entry per
     *                                                             chunk; title is the H2 text (null for the leading chunk without one).
     *                                                             A single entry means the document could not be split.
     */
    public static function split(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $chunks = [];
        $current = ['title' => null, 'lines' => []];
        $inFence = false;

        foreach ($lines as $line) {
            if (preg_match('/^(```|~~~)/', trim($line))) {
                $inFence = ! $inFence;
            }

            if (! $inFence && preg_match('/^##\s+(.+?)\s*#*\s*$/', $line, $matches)) {
                if ($current['lines'] !== [] || $current['title'] !== null) {
                    $chunks[] = $current;
                }

                $current = ['title' => trim($matches[1]), 'lines' => [$line]];

                continue;
            }

            $current['lines'][] = $line;
        }

        $chunks[] = $current;

        // Content before the first ## heading (an intro, or nothing) belongs to
        // the first real section, not to a page of its own.
        if (count($chunks) > 1 && $chunks[0]['title'] === null) {
            $intro = array_shift($chunks);
            $introText = trim(implode("\n", $intro['lines']));

            if ($introText !== '') {
                $chunks[0]['lines'] = [...explode("\n", $introText), '', ...$chunks[0]['lines']];
            }
        }

        return array_map(fn (array $chunk) => [
            'title' => $chunk['title'],
            'markdown' => trim(implode("\n", $chunk['lines'])),
        ], $chunks);
    }

    public static function chunkSlug(?string $title, string $fallbackSlug, int $index): string
    {
        $slug = $title !== null ? Str::slug($title) : '';

        return $slug !== '' ? $slug : "{$fallbackSlug}-".($index + 1);
    }
}
