<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseImportConfirmRequest;
use App\Http\Requests\Admin\CourseImportRequest;
use App\Http\Requests\Admin\CourseLessonsReorderRequest;
use App\Http\Requests\Admin\CoursePagesReorderRequest;
use App\Http\Requests\Admin\CourseRequest;
use App\Models\Course;
use App\Models\Page;
use App\Services\CourseTransfer\CourseExporter;
use App\Services\CourseTransfer\ImportPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
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

        $course->load(['lessons.pages', 'lessons.questions', 'creator']);

        return Inertia::render('admin/courses/Editor', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail,
                'is_published' => $course->is_published,
                'order' => $course->order,
                'lessons' => $course->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug,
                    'order' => $lesson->order,
                    'questions' => $lesson->questions->map(fn ($question) => [
                        'id' => $question->id,
                        'text' => $question->text,
                    ]),
                    'pages' => $lesson->pages->map(fn ($page) => [
                        'id' => $page->id,
                        'title' => $page->title,
                        'slug' => $page->slug,
                        'order' => $page->order,
                        'markdown' => $page->markdown,
                        'narration_text' => $page->narration_text,
                        'narration_audio_path' => $page->narration_audio_path,
                    ]),
                ]),
            ],
            'canManageEnrollments' => Gate::allows('manageEnrollments', $course),
        ]);
    }

    public function reorderLessons(CourseLessonsReorderRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        DB::transaction(function () use ($request, $course): void {
            foreach ($request->validated('lessons') as $lessonData) {
                $course->lessons()->whereKey($lessonData['id'])->update([
                    'order' => $lessonData['order'],
                ]);
            }
        });

        return back();
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

    public function importCreate(Request $request): Response
    {
        Gate::authorize('create', Course::class);

        return Inertia::render('admin/courses/Import', [
            'plan' => $request->session()->get('importPlan'),
            'token' => $request->session()->get('importToken'),
        ]);
    }

    public function importAnalyze(CourseImportRequest $request, ImportPlanner $planner): RedirectResponse
    {
        Gate::authorize('create', Course::class);

        try {
            $staged = $planner->stage($request->file('files'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['files' => $exception->getMessage()]);
        }

        return to_route('admin.courses.import.create')
            ->with('importPlan', $staged['plan'])
            ->with('importToken', $staged['token']);
    }

    public function importConfirm(CourseImportConfirmRequest $request, ImportPlanner $planner): RedirectResponse
    {
        Gate::authorize('create', Course::class);

        try {
            $course = $planner->confirm(
                $request->validated('token'),
                $request->validated(),
                $request->user()->id,
            );
        } catch (RuntimeException $exception) {
            return to_route('admin.courses.import.create')->withErrors(['files' => $exception->getMessage()]);
        }

        return to_route('admin.courses.edit', $course)->with('success', 'A kurzus importálása sikerült.');
    }

    public function importDiscard(Request $request, ImportPlanner $planner): RedirectResponse
    {
        Gate::authorize('create', Course::class);

        $token = (string) $request->input('token');

        if (preg_match('/^[A-Za-z0-9]{24}$/', $token)) {
            $planner->discard($token);
        }

        return to_route('admin.courses.import.create');
    }
}
