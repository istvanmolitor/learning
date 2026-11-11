<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\CourseTransfer\CourseImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LessonController extends Controller
{
    /**
     * Append uploaded .md files as new pages at the end of the lesson —
     * additive only, nothing is deleted or overwritten.
     */
    public function uploadPages(Request $request, Lesson $lesson, CourseImporter $importer): RedirectResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'extensions:md', 'max:10240'],
        ]);

        $created = $importer->appendMarkdownPages($lesson, $request->file('files'));

        return back()->with('success', "{$created} oldal hozzáadva a leckéhez.");
    }

    public function store(LessonRequest $request, Course $course): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] ??= $this->uniqueSlug($course, $data['title']);
        $data['order'] ??= ($course->lessons()->max('order') ?? -1) + 1;

        $course->lessons()->create($data);

        return back();
    }

    public function update(LessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $data = array_filter(
            $request->validated(),
            fn ($value) => $value !== null,
        );

        $lesson->update($data);

        return back();
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $lesson->delete();

        return back();
    }

    private function uniqueSlug(Course $course, string $title): string
    {
        $base = Str::slug($title) ?: 'lecke';
        $slug = $base;
        $i = 1;

        while ($course->lessons()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
