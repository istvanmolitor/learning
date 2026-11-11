<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use League\HTMLToMarkdown\HtmlConverter;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->longText('markdown')->default('');
            $table->longText('body_html')->default('');
        });

        $this->convertContentBlocks();
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['markdown', 'body_html']);
        });
    }

    /**
     * Flatten each page's ordered content blocks into a single Markdown document
     * (the same rendering the zip exporter used), so no content is lost when the
     * content_blocks table is dropped. Inlined here on purpose: migrations must
     * not depend on app code that may change or disappear later.
     */
    private function convertContentBlocks(): void
    {
        if (! Schema::hasTable('content_blocks')) {
            return;
        }

        $converter = new HtmlConverter(['strip_tags' => false]);

        DB::table('content_blocks')
            ->orderBy('page_id')
            ->orderBy('order')
            ->get()
            ->groupBy('page_id')
            ->each(function ($blocks, int $pageId) use ($converter) {
                $parts = [];

                foreach ($blocks as $block) {
                    $data = json_decode($block->data, true) ?: [];

                    $parts[] = match ($block->type) {
                        'heading' => str_repeat('#', max(1, min(6, (int) ($data['level'] ?? 2)))).' '.($data['text'] ?? ''),
                        'text' => trim($converter->convert($data['html'] ?? '')),
                        'image' => $this->imageHtml($data),
                        'youtube_video' => sprintf(
                            '<iframe width="560" height="315" src="https://www.youtube.com/embed/%s" title="%s" frameborder="0" allowfullscreen></iframe>',
                            e($data['video_id'] ?? ''),
                            e($data['title'] ?? '')
                        ),
                        default => '',
                    };
                }

                $markdown = implode("\n\n", array_filter($parts, fn ($part) => $part !== ''));

                DB::table('pages')->where('id', $pageId)->update([
                    'markdown' => $markdown,
                    'body_html' => Str::markdown($markdown),
                ]);
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function imageHtml(array $data): string
    {
        $path = $data['path'] ?? null;

        if (! $path) {
            return '';
        }

        $html = '<figure><img src="/storage/'.ltrim($path, '/').'" alt="'.e($data['alt'] ?? '').'">';

        if (! empty($data['caption'])) {
            $html .= '<figcaption>'.e($data['caption']).'</figcaption>';
        }

        return $html.'</figure>';
    }
};
