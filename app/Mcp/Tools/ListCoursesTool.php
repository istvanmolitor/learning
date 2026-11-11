<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List every course on the platform with its slug, title, publish state, and lesson count.')]
class ListCoursesTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $courses = Course::query()
            ->orderBy('order')
            ->withCount('lessons')
            ->get()
            ->map(fn (Course $course) => [
                'slug' => $course->slug,
                'title' => $course->title,
                'is_published' => $course->is_published,
                'lesson_count' => $course->lessons_count,
            ]);

        return Response::json(['courses' => $courses]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
