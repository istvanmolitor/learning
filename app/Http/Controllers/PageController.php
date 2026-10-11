<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function show(Request $request, Course $course, Lesson $lesson, Page $page): Response
    {
        Gate::authorize('view', $course);

        $lessons = $course->lessons()
            ->get()
            ->map(fn ($lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'is_completed' => $lesson->isCompletedBy($request->user()),
            ]);

        $lessonPages = $lesson->pages()->get(['id', 'slug', 'title']);
        $currentIndex = $lessonPages->search(fn ($lessonPage) => $lessonPage->id === $page->id);
        $previousPage = $currentIndex > 0 ? $lessonPages->get($currentIndex - 1) : null;
        $nextPage = $currentIndex !== false ? $lessonPages->get($currentIndex + 1) : null;

        return Inertia::render('courses/lessons/pages/Show', [
            'course' => ['slug' => $course->slug, 'title' => $course->title],
            'lesson' => ['slug' => $lesson->slug, 'title' => $lesson->title],
            'lessons' => $lessons,
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'is_completed' => $page->isCompletedBy($request->user()),
            ],
            'pagination' => [
                'index' => $currentIndex !== false ? $currentIndex + 1 : null,
                'total' => $lessonPages->count(),
                'previous' => $previousPage ? ['slug' => $previousPage->slug, 'title' => $previousPage->title] : null,
                'next' => $nextPage ? ['slug' => $nextPage->slug, 'title' => $nextPage->title] : null,
            ],
            'contentBlocks' => $page->contentBlocks->map(fn ($block) => [
                'id' => $block->id,
                'type' => $block->type->value,
                'data' => $block->data,
            ]),
        ]);
    }

    public function complete(Request $request, Course $course, Lesson $lesson, Page $page): RedirectResponse
    {
        Gate::authorize('view', $course);

        $page->markCompletedBy($request->user());

        return back();
    }
}
