<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use RuntimeException;

#[Description('Delete a lesson (with all its pages and questions) or a single page. Irreversible.')]
class DeleteItemTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $course = $this->resolveCourse((string) $request->get('course_slug'));
        $type = (string) $request->get('type');
        $lesson = $this->resolveLesson($course, (string) $request->get('lesson_slug'));

        match ($type) {
            'lesson' => $lesson->delete(),
            'page' => $this->resolvePage($lesson, (string) $request->get('page_slug'))->delete(),
            default => throw new RuntimeException("Ismeretlen típus: {$type} (lesson vagy page lehet)."),
        };

        $this->logWrite($user, 'delete_item', [
            'course_slug' => $course->slug,
            'type' => $type,
            'lesson_slug' => $lesson->slug,
            'page_slug' => $request->get('page_slug'),
        ]);

        return Response::json(['status' => 'deleted']);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'course_slug' => $schema->string()->required(),
            'type' => $schema->string()->enum(['lesson', 'page'])->required(),
            'lesson_slug' => $schema->string()->required(),
            'page_slug' => $schema->string()->description('Required when type is "page".'),
        ];
    }
}
