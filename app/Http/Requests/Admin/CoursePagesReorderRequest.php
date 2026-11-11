<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CoursePagesReorderRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Course $course */
        $course = $this->route('course');
        $lessonIds = $course->lessons()->pluck('id');
        $pageIds = Page::whereIn('lesson_id', $lessonIds)->pluck('id');

        return [
            'pages' => ['required', 'array'],
            'pages.*.id' => ['required', 'integer', Rule::in($pageIds)],
            'pages.*.lesson_id' => ['required', 'integer', Rule::in($lessonIds)],
            'pages.*.order' => ['required', 'integer', 'min:0'],
        ];
    }
}
