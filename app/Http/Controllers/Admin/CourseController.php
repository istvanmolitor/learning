<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseImportRequest;
use App\Http\Requests\Admin\CourseRequest;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseTransfer\CourseExporter;
use App\Services\CourseTransfer\CourseImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return to_route('admin.courses.edit', $course);
    }

    public function edit(Course $course): Response
    {
        Gate::authorize('update', $course);

        $course->load('lessons', 'creator');
        $canManageEnrollments = Gate::allows('manageEnrollments', $course);

        $enrolledUsers = [];
        $availableUsers = [];

        if ($canManageEnrollments) {
            $course->load(['enrolledUsers' => fn ($query) => $query->orderBy('name')]);
            $enrolledUsers = $course->enrolledUsers;
            $availableUsers = User::query()
                ->whereDoesntHave('courses', fn ($query) => $query->where('course_id', $course->id))
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        return Inertia::render('admin/courses/Edit', [
            'course' => [
                ...$course->toArray(),
                'enrolledUsers' => $enrolledUsers,
                'availableUsers' => $availableUsers,
            ],
            'canManageEnrollments' => $canManageEnrollments,
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
