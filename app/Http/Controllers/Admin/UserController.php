<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $sort = $request->string('sort', 'name')->toString();
        $direction = $request->string('direction', 'asc')->toString();

        if (! in_array($sort, ['name', 'email', 'created_at'], true)) {
            $sort = 'name';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $search = $request->string('search')->toString();

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->withCount('courses')
            ->with('groups')
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function show(User $user): Response
    {
        $user->load('groups');
        $user->load(['courses' => fn ($query) => $query->orderBy('title')]);

        $availableCourses = Course::query()
            ->whereDoesntHave('enrollments', fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('title')
            ->get(['id', 'title']);

        return Inertia::render('admin/users/Show', [
            'user' => $user,
            'availableCourses' => $availableCourses,
        ]);
    }
}
