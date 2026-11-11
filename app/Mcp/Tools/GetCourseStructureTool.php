<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Get the full lesson/page/question tree of a course by slug, including each page\'s word count so you can tell which pages are empty or long.')]
class GetCourseStructureTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $course = $this->resolveCourse((string) $request->get('course_slug'));
        $course->load(['lessons.pages', 'lessons.questions']);

        return Response::json([
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $course->description,
            'is_published' => $course->is_published,
            'lessons' => $course->lessons->map(fn ($lesson) => [
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'order' => $lesson->order,
                'pages' => $lesson->pages->map(fn ($page) => [
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'order' => $page->order,
                    'word_count' => str_word_count(strip_tags($page->markdown ?? '')),
                ]),
                'questions' => $lesson->questions->map(fn ($question) => [
                    'id' => $question->id,
                    'text' => $question->text,
                ]),
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'course_slug' => $schema->string()->description('The course slug.')->required(),
        ];
    }
}
