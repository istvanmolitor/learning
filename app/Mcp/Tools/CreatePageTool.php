<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a new page at the end of a lesson, optionally with initial Markdown content.')]
class CreatePageTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $course = $this->resolveCourse((string) $request->get('course_slug'));
        $lesson = $this->resolveLesson($course, (string) $request->get('lesson_slug'));
        $title = (string) $request->get('title');
        $slug = $this->uniqueSlug($lesson, (string) ($request->get('slug') ?: $title));

        $page = $lesson->pages()->create([
            'title' => $title,
            'slug' => $slug,
            'order' => ($lesson->pages()->max('order') ?? -1) + 1,
            'markdown' => (string) ($request->get('markdown') ?? ''),
        ]);

        $this->logWrite($user, 'create_page', [
            'course_slug' => $course->slug,
            'lesson_slug' => $lesson->slug,
            'page_slug' => $page->slug,
        ]);

        return Response::json(['status' => 'created', 'page_slug' => $page->slug]);
    }

    private function uniqueSlug(Lesson $lesson, string $title): string
    {
        $base = Str::slug($title) ?: 'oldal';
        $slug = $base;
        $i = 1;

        while ($lesson->pages()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'course_slug' => $schema->string()->required(),
            'lesson_slug' => $schema->string()->required(),
            'title' => $schema->string()->required(),
            'slug' => $schema->string()->description('Optional; derived from the title when omitted.'),
            'markdown' => $schema->string()->description('Optional initial content.'),
        ];
    }
}
