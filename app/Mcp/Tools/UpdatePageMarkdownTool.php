<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Replace a page\'s Markdown content. The change is live immediately, including on an already-published course.')]
class UpdatePageMarkdownTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $course = $this->resolveCourse((string) $request->get('course_slug'));
        $lesson = $this->resolveLesson($course, (string) $request->get('lesson_slug'));
        $page = $this->resolvePage($lesson, (string) $request->get('page_slug'));

        $page->update(['markdown' => (string) $request->get('markdown')]);

        $this->logWrite($user, 'update_page_markdown', [
            'course_slug' => $course->slug,
            'lesson_slug' => $lesson->slug,
            'page_slug' => $page->slug,
        ]);

        return Response::json(['status' => 'updated', 'page_slug' => $page->slug]);
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
            'markdown' => $schema->string()->description('The new Markdown content for the page.')->required(),
        ];
    }
}
