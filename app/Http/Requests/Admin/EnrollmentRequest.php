<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'course_id' => [
                'required',
                'exists:courses,id',
                Rule::unique('enrollments')->where('user_id', $this->route('user')?->id),
            ],
        ];
    }
}
