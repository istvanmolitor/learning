<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Course|null $course */
        $course = $this->route('course');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('courses', 'slug')->ignore($course?->id),
            ],
            'description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
