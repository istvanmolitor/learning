<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Lesson;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function store(PageRequest $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validated();
        $data['markdown'] = $data['markdown'] ?? '';
        $data['slug'] ??= $this->uniqueSlug($lesson, $data['title']);
        $data['order'] ??= ($lesson->pages()->max('order') ?? -1) + 1;

        if ($request->file('narration_audio')) {
            $data['narration_audio_path'] = $request->file('narration_audio')
                ->store("course-media/{$lesson->course_id}/narration", 'public');
        }
        unset($data['narration_audio']);

        $lesson->pages()->create($data);

        return back();
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();

        if (array_key_exists('markdown', $data) && $data['markdown'] === null) {
            $data['markdown'] = '';
        }

        if (array_key_exists('slug', $data) && $data['slug'] === null) {
            unset($data['slug']);
        }

        if ($request->file('narration_audio')) {
            $data['narration_audio_path'] = $request->file('narration_audio')
                ->store("course-media/{$page->lesson->course_id}/narration", 'public');
        }
        unset($data['narration_audio']);

        $page->update($data);

        return back();
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return back();
    }

    private function uniqueSlug(Lesson $lesson, string $title): string
    {
        $base = Str::slug($title) ?: 'oldal';
        $slug = $base;
        $i = 1;

        while ($lesson->pages()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
