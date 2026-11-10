<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseImportRequest;
use App\Http\Requests\Admin\CourseRequest;
use App\Models\Course;
use App\Services\CourseTransfer\CourseExporter;
use App\Services\CourseTransfer\CourseImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/courses/Index', [
            'courses' => Course::query()->orderBy('order')->withCount('lessons')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/courses/Edit', [
            'course' => null,
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['thumbnail', 'remove_thumbnail']);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('course-thumbnails', 'public');
        }

        Course::create($data);

        return to_route('admin.courses.index');
    }

    public function edit(Course $course): Response
    {
        $course->load('lessons');

        return Inertia::render('admin/courses/Edit', [
            'course' => $course,
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $data = $request->safe()->except(['thumbnail', 'remove_thumbnail']);

        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }

            $data['thumbnail'] = $request->file('thumbnail')->store('course-thumbnails', 'public');
        } elseif ($request->boolean('remove_thumbnail')) {
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }

            $data['thumbnail'] = null;
        }

        $course->update($data);

        return to_route('admin.courses.index');
    }

    public function destroy(Course $course): RedirectResponse
    {
        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        $course->delete();

        return to_route('admin.courses.index');
    }

    public function export(Course $course, CourseExporter $exporter): BinaryFileResponse
    {
        $path = $exporter->export($course);

        return response()->download($path, $course->slug.'.zip')->deleteFileAfterSend();
    }

    public function import(CourseImportRequest $request, CourseImporter $importer): RedirectResponse
    {
        $course = $importer->import($request->file('archive'));

        return to_route('admin.courses.edit', $course);
    }
}
