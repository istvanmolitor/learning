<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GroupRequest;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/groups/Index', [
            'groups' => Group::query()
                ->orderBy('name')
                ->withCount(['users', 'permissions'])
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/groups/Edit', [
            'group' => null,
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name', 'description']),
            'availableUsers' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(GroupRequest $request): RedirectResponse
    {
        $group = Group::create($request->safe()->only('name'));

        $group->permissions()->sync($request->validated('permissions', []));

        return to_route('admin.groups.index');
    }

    public function edit(Group $group): Response
    {
        $group->load(['users' => fn ($query) => $query->orderBy('name'), 'permissions']);

        return Inertia::render('admin/groups/Edit', [
            'group' => $group,
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name', 'description']),
            'availableUsers' => User::query()
                ->whereNotIn('id', $group->users->pluck('id'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function update(GroupRequest $request, Group $group): RedirectResponse
    {
        $group->update($request->safe()->only('name'));

        $group->permissions()->sync($request->validated('permissions', []));

        return to_route('admin.groups.index');
    }

    public function destroy(Group $group): RedirectResponse
    {
        abort_if($group->name === 'admin', 403);

        $group->delete();

        return to_route('admin.groups.index');
    }
}
