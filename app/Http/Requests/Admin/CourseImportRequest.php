<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CourseImportRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'archive' => ['required_without_all:files,manifest', 'nullable', 'file', 'mimes:zip', 'max:51200'],
            'files' => ['required_without_all:archive,manifest', 'nullable', 'array', 'min:1'],
            'files.*' => ['file', 'extensions:md', 'max:10240'],
            'manifest' => ['required_without_all:archive,files', 'nullable', 'file', 'extensions:json', 'max:5120'],
        ];
    }
}
