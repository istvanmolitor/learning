<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function show(Request $request, Course $course, Lesson $lesson): Response
    {
        Gate::authorize('view', $course);

        $user = $request->user();
        $isCompleted = $lesson->isCompletedBy($user);

        $pages = $lesson->pages->map(fn ($page) => [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'is_completed' => $page->isCompletedBy($user),
        ]);

        $questions = $isCompleted
            ? $lesson->questions->map(function ($question) use ($user) {
                $response = $question->responses->firstWhere('user_id', $user->id);

                return [
                    'id' => $question->id,
                    'text' => $question->text,
                    'options' => $question->answerOptions->map(fn ($option) => [
                        'id' => $option->id,
                        'text' => $option->text,
                    ]),
                    'selected_answer_option_id' => $response?->answer_option_id,
                    'is_correct' => $response?->isCorrect(),
                ];
            })
            : [];

        $lessons = $course->lessons()
            ->get()
            ->map(fn ($lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'is_completed' => $lesson->isCompletedBy($user),
            ]);

        return Inertia::render('courses/lessons/Show', [
            'course' => ['slug' => $course->slug, 'title' => $course->title],
            'lesson' => [
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'is_completed' => $isCompleted,
            ],
            'lessons' => $lessons,
            'pages' => $pages,
            'questions' => $questions,
        ]);
    }

    public function complete(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('view', $course);

        $lesson->markCompletedBy($request->user());

        return back();
    }
}
