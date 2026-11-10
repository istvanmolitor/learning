<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GroupUserRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class GroupUserController extends Controller
{
    public function store(GroupUserRequest $request, Group $group): RedirectResponse
    {
        $group->users()->attach($request->validated('user_id'));

        return to_route('admin.groups.edit', $group);
    }

    public function destroy(Group $group, User $user): RedirectResponse
    {
        $group->users()->detach($user->id);

        return to_route('admin.groups.edit', $group);
    }
}
