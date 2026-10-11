<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/users/Index', [
            'users' => User::query()
                ->orderBy('name')
                ->with('courses')
                ->get(),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
