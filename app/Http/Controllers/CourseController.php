<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = $request->user()->courses()
            ->orderBy('order')
            ->withCount('lessons')
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail,
                'lessons_count' => $course->lessons_count,
                'completed_lessons_count' => $course->lessons()
                    ->whereHas('completions', fn ($query) => $query->where('user_id', $request->user()->id))
                    ->count(),
            ]);

        return Inertia::render('courses/Index', [
            'courses' => $courses,
        ]);
    }

    public function show(Request $request, Course $course): Response
    {
        Gate::authorize('view', $course);

        $user = $request->user();

        $allLessons = $course->lessons()->with('pages')->get();

        $lessons = $allLessons->map(fn ($lesson) => [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'slug' => $lesson->slug,
            'is_completed' => $lesson->isCompletedBy($user),
        ]);

        $continueLesson = $allLessons->first(fn ($lesson) => ! $lesson->isCompletedBy($user))
            ?? $allLessons->last();

        $continuePage = $continueLesson?->pages->first(fn ($page) => ! $page->isCompletedBy($user));

        return Inertia::render('courses/Show', [
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail,
            ],
            'lessons' => $lessons,
            'continueTo' => $continueLesson ? [
                'lessonSlug' => $continueLesson->slug,
                'pageSlug' => $continuePage?->slug,
            ] : null,
        ]);
    }

    public function pdf(Course $course): HttpResponse
    {
        Gate::authorize('view', $course);

        $course->loadMissing('lessons.pages.contentBlocks');

        $pdf = Pdf::loadView('pdf.course', [
            'course' => $course,
            'totalPages' => $course->lessons->sum(fn ($lesson) => $lesson->pages->count()),
            'generatedAt' => now()->translatedFormat('Y. F d.'),
        ])->setPaper('a4', 'portrait');

        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $muted = [0.60, 0.59, 0.67];
        $title = mb_strtoupper($course->title);

        $canvas->page_script(function (int $pageNumber, int $pageCount, $canvas, $fontMetrics) use ($font, $muted, $title) {
            $width = $canvas->get_width();
            $height = $canvas->get_height();

            $footerText = "{$pageNumber} / {$pageCount}";
            $footerSize = 8;
            $footerWidth = $fontMetrics->getTextWidth($footerText, $font, $footerSize);
            $canvas->text(($width - $footerWidth) / 2, $height - 38, $footerText, $font, $footerSize, $muted);

            if ($pageNumber > 1) {
                $headerSize = 7.5;
                $headerWidth = $fontMetrics->getTextWidth($title, $font, $headerSize, 0.0, 0.08);
                $canvas->text(($width - $headerWidth) / 2, 48, $title, $font, $headerSize, $muted, 0.0, 0.08);
            }
        });

        return $pdf->download(Str::slug($course->slug).'.pdf');
    }
}
