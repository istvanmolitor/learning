<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class QuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.text' => ['required', 'string', 'max:255'],
            'correct_option' => ['required', 'integer', 'min:0', 'lt:options_count'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['options_count' => count($this->input('options', []))]);
    }
}
