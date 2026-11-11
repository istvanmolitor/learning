<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CourseImportConfirmRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'regex:/^[A-Za-z0-9]{24}$/'],
            'mode' => ['required', 'in:auto,new'],
            'new_slug' => ['nullable', 'required_if:mode,new', 'string', 'max:255'],
            'confirm_deletes' => ['nullable', 'boolean'],
            'split_long_pages' => ['nullable', 'boolean'],
        ];
    }
}
