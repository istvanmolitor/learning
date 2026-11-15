<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Services\CourseTransfer\MarkdownToBlocks;
use Illuminate\Console\Command;

/**
 * One-time (idempotent) migration of existing pages from Markdown-only
 * content into content_blocks, so the new blocks editor has something to
 * show for them. Purely additive: page.markdown/body_html are left exactly
 * as they are — only pages:convert-to-blocks (or a fresh block edit
 * afterwards) ever touches content_blocks.
 */
class ConvertPagesToBlocks extends Command
{
    protected $signature = 'pages:convert-to-blocks {--force : Re-convert pages that already have blocks, replacing them}';

    protected $description = 'Oldalak meglévő Markdown tartalmának egyszeri átalakítása tartalomblokkokká.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $converted = 0;
        $skippedEmpty = 0;
        $skippedExisting = 0;

        Page::query()->withCount('blocks')->chunkById(100, function ($pages) use (&$converted, &$skippedEmpty, &$skippedExisting, $force) {
            foreach ($pages as $page) {
                if ($page->blocks_count > 0 && ! $force) {
                    $skippedExisting++;

                    continue;
                }

                if (trim($page->markdown ?? '') === '') {
                    $skippedEmpty++;

                    continue;
                }

                $blocks = MarkdownToBlocks::convert($page->markdown);

                if ($blocks === []) {
                    $skippedEmpty++;

                    continue;
                }

                $page->blocks()->delete();

                foreach ($blocks as $order => $block) {
                    $page->blocks()->create(['type' => $block['type'], 'order' => $order, 'data' => $block['data']]);
                }

                $converted++;
            }
        });

        $this->info("Blokkosítva: {$converted} oldal.");
        $this->line("Kihagyva (üres): {$skippedEmpty}.");
        $this->line("Kihagyva (már volt blokkja, --force nélkül): {$skippedExisting}.");

        return self::SUCCESS;
    }
}
