<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseEnrollmentRequest;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CourseEnrollmentController extends Controller
{
    public function store(CourseEnrollmentRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('manageEnrollments', $course);

        $course->enrollments()->create([
            'user_id' => $request->validated('user_id'),
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);

        return to_route('admin.courses.edit', $course);
    }

    public function destroy(Course $course, Enrollment $enrollment): RedirectResponse
    {
        Gate::authorize('manageEnrollments', $course);

        abort_unless($enrollment->course_id === $course->id, 404);

        $enrollment->delete();

        return to_route('admin.courses.edit', $course);
    }
}
