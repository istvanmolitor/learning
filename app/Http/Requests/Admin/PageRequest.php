<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Page|null $page */
        $page = $this->route('page');
        $lessonId = $page?->lesson_id ?? $this->route('lesson')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('pages', 'slug')->where('lesson_id', $lessonId)->ignore($page?->id),
            ],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
