<?php

namespace App\Http\Controllers\Admin;

use App\Blocks\BlockRegistry;
use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Services\Blocks\PageBlockSync;
use App\Services\CourseTransfer\MarkdownToBlocks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContentBlockController extends Controller
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly PageBlockSync $sync,
    ) {}

    public function store(Request $request, Page $page): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'type' => ['required', 'string', Rule::in(array_keys($this->registry->all()))],
            'data' => ['array'],
        ])->validate();

        $data = $this->registry->validate($validated['type'], $validated['data'] ?? []);

        $order = ($page->blocks()->max('order') ?? -1) + 1;
        $page->blocks()->create(['type' => $validated['type'], 'order' => $order, 'data' => $data]);

        $this->sync->resync($page);

        return back();
    }

    public function update(Request $request, ContentBlock $block): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'data' => ['array'],
        ])->validate();

        $data = $this->registry->validate($block->type, $validated['data'] ?? []);
        $block->update(['data' => $data]);

        $this->sync->resync($block->page);

        return back();
    }

    public function destroy(ContentBlock $block): RedirectResponse
    {
        $page = $block->page;
        $block->delete();

        $this->sync->resync($page);

        return back();
    }

    /**
     * One-off opt-in for a page that still only has legacy markdown (e.g.
     * imported from a v1/.md package, or never touched since the blocks
     * editor shipped): converts it into editable blocks. No-ops if the page
     * already has blocks, so it is safe to call more than once.
     */
    public function convert(Page $page): RedirectResponse
    {
        if ($page->blocks()->exists()) {
            return back();
        }

        foreach (MarkdownToBlocks::convert($page->markdown ?? '') as $order => $block) {
            $page->blocks()->create(['type' => $block['type'], 'order' => $order, 'data' => $block['data']]);
        }

        return back();
    }

    public function reorder(Request $request, Page $page): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'distinct'],
        ]);

        $ids = $page->blocks()->pluck('id');

        foreach ($validated['order'] as $index => $blockId) {
            if ($ids->contains($blockId)) {
                ContentBlock::whereKey($blockId)->update(['order' => $index]);
            }
        }

        $this->sync->resync($page);

        return back();
    }
}
