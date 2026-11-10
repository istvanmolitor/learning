<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EnrollmentRequest;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class EnrollmentController extends Controller
{
    public function store(EnrollmentRequest $request, User $user): RedirectResponse
    {
        $user->enrollments()->create([
            'course_id' => $request->validated('course_id'),
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);

        return to_route('admin.users.show', $user);
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $userId = $enrollment->user_id;

        $enrollment->delete();

        return to_route('admin.users.show', $userId);
    }
}
