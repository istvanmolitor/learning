<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentBlockRequest;
use App\Models\ContentBlock;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;

class ContentBlockController extends Controller
{
    public function store(ContentBlockRequest $request, Page $page): RedirectResponse
    {
        $page->contentBlocks()->create([
            'type' => $request->validated('type'),
            'data' => $this->buildData($request, $page),
            'order' => $request->validated('order') ?? 0,
        ]);

        return to_route('admin.pages.edit', $page);
    }

    public function update(ContentBlockRequest $request, ContentBlock $content_block): RedirectResponse
    {
        $content_block->update([
            'type' => $request->validated('type'),
            'data' => $this->buildData($request, $content_block->page, $content_block),
            'order' => $request->validated('order') ?? $content_block->order,
        ]);

        return to_route('admin.pages.edit', $content_block->page);
    }

    public function destroy(ContentBlock $content_block): RedirectResponse
    {
        $page = $content_block->page;
        $content_block->delete();

        return to_route('admin.pages.edit', $page);
    }

    private function buildData(ContentBlockRequest $request, Page $page, ?ContentBlock $existing = null): array
    {
        return match ($request->validated('type')) {
            'heading' => [
                'text' => $request->validated('text'),
                'level' => (int) $request->validated('level'),
            ],
            'text' => [
                'html' => $request->validated('html'),
            ],
            'image' => [
                'path' => $request->file('image')
                    ? $request->file('image')->store("course-media/{$page->lesson->course_id}", 'public')
                    : $existing?->data['path'] ?? null,
                'alt' => $request->validated('alt'),
                'caption' => $request->validated('caption'),
            ],
            'youtube_video' => [
                'video_id' => $request->validated('video_id'),
                'title' => $request->validated('title'),
            ],
        };
    }
}
