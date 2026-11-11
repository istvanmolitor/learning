<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use RuntimeException;

#[Description('Rename a lesson or page (changes its display title only — the slug, and therefore any links to it, stay stable).')]
class RenameItemTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $course = $this->resolveCourse((string) $request->get('course_slug'));
        $type = (string) $request->get('type');
        $title = (string) $request->get('title');

        $item = match ($type) {
            'lesson' => $this->resolveLesson($course, (string) $request->get('lesson_slug')),
            'page' => $this->resolvePage(
                $this->resolveLesson($course, (string) $request->get('lesson_slug')),
                (string) $request->get('page_slug'),
            ),
            default => throw new RuntimeException("Ismeretlen típus: {$type} (lesson vagy page lehet)."),
        };

        $item->update(['title' => $title]);

        $this->logWrite($user, 'rename_item', ['course_slug' => $course->slug, 'type' => $type, 'slug' => $item->slug]);

        return Response::json(['status' => 'renamed', 'slug' => $item->slug, 'title' => $item->title]);
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
            'title' => $schema->string()->description('The new title.')->required(),
        ];
    }
}
