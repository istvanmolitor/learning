<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = $request->user()->courses()
            ->orderBy('order')
            ->withCount('lessons')
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'lessons_count' => $course->lessons_count,
                'completed_lessons_count' => $course->lessons()
                    ->whereHas('completions', fn ($query) => $query->where('user_id', $request->user()->id))
                    ->count(),
            ]);

        return Inertia::render('courses/Index', [
            'courses' => $courses,
        ]);
    }

    public function show(Request $request, Course $course): Response
    {
        Gate::authorize('view', $course);

        $user = $request->user();

        $allLessons = $course->lessons()->with('pages')->get();

        $lessons = $allLessons->map(fn ($lesson) => [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'slug' => $lesson->slug,
            'is_completed' => $lesson->isCompletedBy($user),
        ]);

        $continueLesson = $allLessons->first(fn ($lesson) => ! $lesson->isCompletedBy($user))
            ?? $allLessons->last();

        $continuePage = $continueLesson?->pages->first(fn ($page) => ! $page->isCompletedBy($user));

        return Inertia::render('courses/Show', [
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
            ],
            'lessons' => $lessons,
            'continueTo' => $continueLesson ? [
                'lessonSlug' => $continueLesson->slug,
                'pageSlug' => $continuePage?->slug,
            ] : null,
        ]);
    }
}
