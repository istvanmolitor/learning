<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Read a single page\'s raw Markdown content.')]
class GetPageMarkdownTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $course = $this->resolveCourse((string) $request->get('course_slug'));
        $lesson = $this->resolveLesson($course, (string) $request->get('lesson_slug'));
        $page = $this->resolvePage($lesson, (string) $request->get('page_slug'));

        return Response::json([
            'title' => $page->title,
            'slug' => $page->slug,
            'markdown' => $page->markdown,
            'narration_text' => $page->narration_text,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'course_slug' => $schema->string()->required(),
            'lesson_slug' => $schema->string()->required(),
            'page_slug' => $schema->string()->required(),
        ];
    }
}
