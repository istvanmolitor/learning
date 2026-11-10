<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function pages(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json(['pages' => []]);
        }

        $courseIds = $request->user()->courses()->pluck('courses.id');

        $pages = Page::query()
            ->where('title', 'like', '%'.$query.'%')
            ->whereHas('lesson', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->with('lesson.course')
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn (Page $page) => [
                'title' => $page->title,
                'slug' => $page->slug,
                'lesson' => [
                    'title' => $page->lesson->title,
                    'slug' => $page->lesson->slug,
                ],
                'course' => [
                    'title' => $page->lesson->course->title,
                    'slug' => $page->lesson->course->slug,
                ],
            ]);

        return response()->json(['pages' => $pages]);
    }
}
