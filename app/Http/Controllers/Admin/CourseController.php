<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/courses/Index', [
            'courses' => Course::query()->orderBy('order')->withCount('lessons')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/courses/Edit', [
            'course' => null,
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        Course::create($request->validated());

        return to_route('admin.courses.index');
    }

    public function edit(Course $course): Response
    {
        $course->load('lessons');

        return Inertia::render('admin/courses/Edit', [
            'course' => $course,
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return to_route('admin.courses.index');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return to_route('admin.courses.index');
    }
}
