<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The wizard's single drop zone: one zip, one course.json, or any number of
 * .md files. What was uploaded is detected server-side, not chosen by the user.
 */
class CourseImportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'extensions:zip,json,md', 'max:102400'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $files = $this->file('files', []);
            $extensions = array_map(
                fn ($file) => strtolower($file->getClientOriginalExtension()),
                $files
            );

            $hasZip = in_array('zip', $extensions, true);
            $hasJson = in_array('json', $extensions, true);

            if (($hasZip || $hasJson) && count($files) > 1) {
                $validator->errors()->add('files', 'Zip vagy course.json mellé nem tölthető fel további fájl — ezekből egyszerre csak egyet adj meg.');
            }
        });
    }
}
