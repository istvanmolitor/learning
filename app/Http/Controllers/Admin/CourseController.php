<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseImportRequest;
use App\Http\Requests\Admin\CoursePagesReorderRequest;
use App\Http\Requests\Admin\CourseRequest;
use App\Models\Course;
use App\Models\Page;
use App\Services\CourseTransfer\CourseExporter;
use App\Services\CourseTransfer\CourseImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Course::class);

        $courses = Course::query()
            ->when(
                ! $request->user()->hasPermission('manage-courses'),
                fn ($query) => $query->where('created_by', $request->user()->id),
            )
            ->orderBy('order')
            ->withCount('lessons')
            ->with('creator')
            ->get();

        return Inertia::render('admin/courses/Index', [
            'courses' => $courses,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Course::class);

        return Inertia::render('admin/courses/Edit', [
            'course' => null,
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Course::class);

        $data = $request->safe()->except(['thumbnail', 'remove_thumbnail']);
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('course-thumbnails', 'public');
        }

        $course = Course::create($data);

        $course->enrollments()->create([
            'user_id' => $request->user()->id,
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);

        return to_route('admin.courses.lessons.index', $course);
    }

    public function edit(Course $course): Response
    {
        Gate::authorize('update', $course);

        $course->load('creator');

        return Inertia::render('admin/courses/Edit', [
            'course' => $course,
            'canManageEnrollments' => Gate::allows('manageEnrollments', $course),
        ]);
    }

    public function lessons(Course $course): Response
    {
        Gate::authorize('update', $course);

        $course->load('lessons.pages', 'creator');

        return Inertia::render('admin/courses/Lessons', [
            'course' => $course,
            'canManageEnrollments' => Gate::allows('manageEnrollments', $course),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

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

        return to_route('admin.courses.lessons.index', $course);
    }

    public function reorderPages(CoursePagesReorderRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        DB::transaction(function () use ($request): void {
            foreach ($request->validated('pages') as $pageData) {
                Page::whereKey($pageData['id'])->update([
                    'lesson_id' => $pageData['lesson_id'],
                    'order' => $pageData['order'],
                ]);
            }
        });

        return back();
    }

    public function destroy(Course $course): RedirectResponse
    {
        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        $course->delete();

        return to_route('admin.courses.index');
    }

    public function exportIndex(): Response
    {
        $courses = Course::query()
            ->orderBy('order')
            ->get(['id', 'title', 'slug']);

        return Inertia::render('admin/courses/Export', [
            'courses' => $courses,
        ]);
    }

    public function export(Course $course, CourseExporter $exporter): BinaryFileResponse
    {
        $path = $exporter->export($course);

        return response()->download($path, $course->slug.'.zip')->deleteFileAfterSend();
    }

    public function importCreate(): Response
    {
        Gate::authorize('create', Course::class);

        return Inertia::render('admin/courses/Import');
    }

    public function import(CourseImportRequest $request, CourseImporter $importer): RedirectResponse
    {
        Gate::authorize('create', Course::class);

        $course = match (true) {
            $request->hasFile('archive') => $importer->import($request->file('archive'), $request->user()->id),
            $request->hasFile('files') => $importer->importMarkdownFiles($request->file('files'), $request->user()->id),
            default => $importer->importManifestFile($request->file('manifest'), $request->user()->id),
        };

        return to_route('admin.courses.edit', $course);
    }
}
