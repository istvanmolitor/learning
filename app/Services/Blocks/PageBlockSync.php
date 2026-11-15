<?php

namespace App\Services\Blocks;

use App\Blocks\BlockRegistry;
use App\Models\Page;

/**
 * Keeps Page::markdown/body_html in sync with its content_blocks after any
 * block is created, updated, deleted, or reordered. Those two columns stay
 * the single HTML source the rest of the app already relies on — search,
 * the PDF export (App\Services\Publishing\PageHtml), the PPTX export
 * (CoursePptxExporter/MarkdownSlideSplitter), and the no-JS page fallback —
 * so none of that had to change to support blocks.
 */
class PageBlockSync
{
    public function __construct(private readonly BlockRegistry $registry) {}

    public function resync(Page $page): void
    {
        $html = $page->blocks()
            ->get(['type', 'data'])
            ->map(fn ($block) => $this->registry->resolve($block->type)->render($block->data))
            ->implode("\n\n");

        // Both columns are set explicitly (rather than just "markdown") so
        // Page's saving hook does not re-run it through Str::markdown() —
        // this is already final HTML, not Markdown source.
        $page->markdown = $html;
        $page->body_html = $html;
        $page->save();
    }
}
