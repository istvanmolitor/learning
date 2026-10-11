<?php

namespace App\Http\Requests\Admin;

use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Lesson|null $lesson */
        $lesson = $this->route('lesson');
        $courseId = $lesson?->course_id ?? $this->route('course')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('lessons', 'slug')->where('course_id', $courseId)->ignore($lesson?->id),
            ],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
