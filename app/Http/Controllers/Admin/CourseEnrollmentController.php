<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseEnrollmentRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseEnrollmentController extends Controller
{
    public function index(Course $course): Response
    {
        Gate::authorize('manageEnrollments', $course);

        $course->load(['enrolledUsers' => fn ($query) => $query->orderBy('name')]);

        $availableUsers = User::query()
            ->whereDoesntHave('courses', fn ($query) => $query->where('course_id', $course->id))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('admin/courses/Enrollments', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'enrolledUsers' => $course->enrolledUsers,
            ],
            'availableUsers' => $availableUsers,
        ]);
    }

    public function store(CourseEnrollmentRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('manageEnrollments', $course);

        $course->enrollments()->create([
            'user_id' => $request->validated('user_id'),
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);

        return to_route('admin.courses.enrollments.index', $course);
    }

    public function destroy(Course $course, Enrollment $enrollment): RedirectResponse
    {
        Gate::authorize('manageEnrollments', $course);

        abort_unless($enrollment->course_id === $course->id, 404);

        $enrollment->delete();

        return to_route('admin.courses.enrollments.index', $course);
    }
}
