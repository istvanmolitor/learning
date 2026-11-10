<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function view(User $user, Course $course): bool
    {
        return $user->isAdmin() || $course->isEnrolledBy($user);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-courses') || $user->hasPermission('create-courses');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Course $course): bool
    {
        return $user->hasPermission('manage-courses') || $course->wasCreatedBy($user);
    }

    public function manageEnrollments(User $user, Course $course): bool
    {
        return $user->hasPermission('manage-users') || $course->wasCreatedBy($user);
    }
}
