<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseLessonsReorderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Course $course */
        $course = $this->route('course');
        $lessonIds = $course->lessons()->pluck('id');

        return [
            'lessons' => ['required', 'array'],
            'lessons.*.id' => ['required', 'integer', Rule::in($lessonIds)],
            'lessons.*.order' => ['required', 'integer', 'min:0'],
        ];
    }
}
