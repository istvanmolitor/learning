<?php

namespace App\Http\Requests\Admin;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Group|null $group */
        $group = $this->route('group');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('groups', 'name')->ignore($group?->id),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
