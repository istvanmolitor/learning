<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentBlockRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['heading', 'text', 'image', 'youtube_video'])],
            'order' => ['nullable', 'integer', 'min:0'],

            'text' => ['required_if:type,heading', 'nullable', 'string', 'max:255'],
            'level' => ['required_if:type,heading', 'nullable', 'integer', 'min:1', 'max:6'],

            'html' => ['required_if:type,text', 'nullable', 'string'],

            'image' => ['nullable', 'image', 'max:4096'],
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],

            'video_id' => ['required_if:type,youtube_video', 'nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
