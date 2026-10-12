<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Lesson;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function create(Lesson $lesson): Response
    {
        return Inertia::render('admin/pages/Edit', [
            'lesson' => $lesson,
            'page' => null,
        ]);
    }

    public function store(PageRequest $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validated();

        if ($request->file('narration_audio')) {
            $data['narration_audio_path'] = $request->file('narration_audio')
                ->store("course-media/{$lesson->course_id}/narration", 'public');
        }
        unset($data['narration_audio']);

        $lesson->pages()->create($data);

        return to_route('admin.lessons.edit', $lesson);
    }

    public function edit(Page $page): Response
    {
        $page->load('contentBlocks');

        return Inertia::render('admin/pages/Edit', [
            'lesson' => $page->lesson,
            'page' => $page,
        ]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();

        if ($request->file('narration_audio')) {
            $data['narration_audio_path'] = $request->file('narration_audio')
                ->store("course-media/{$page->lesson->course_id}/narration", 'public');
        }
        unset($data['narration_audio']);

        $page->update($data);

        return to_route('admin.lessons.edit', $page->lesson);
    }

    public function destroy(Page $page): RedirectResponse
    {
        $lesson = $page->lesson;
        $page->delete();

        return to_route('admin.lessons.edit', $lesson);
    }
}
