<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function create(Course $course): Response
    {
        return Inertia::render('admin/lessons/Edit', [
            'course' => $course,
            'lesson' => null,
        ]);
    }

    public function store(LessonRequest $request, Course $course): RedirectResponse
    {
        $course->lessons()->create($request->validated());

        return to_route('admin.courses.edit', $course);
    }

    public function edit(Lesson $lesson): Response
    {
        $lesson->load(['pages', 'questions.answerOptions']);

        return Inertia::render('admin/lessons/Edit', [
            'course' => $lesson->course,
            'lesson' => $lesson,
        ]);
    }

    public function update(LessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $lesson->update($request->validated());

        return to_route('admin.courses.edit', $lesson->course);
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $course = $lesson->course;
        $lesson->delete();

        return to_route('admin.courses.edit', $course);
    }
}
