<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use RuntimeException;

#[Description('Reorder a course\'s lessons, or the pages within one lesson, to match the given slug order.')]
class ReorderItemsTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $course = $this->resolveCourse((string) $request->get('course_slug'));
        $type = (string) $request->get('type');
        /** @var list<string> $slugs */
        $slugs = $request->array('slugs');

        match ($type) {
            'lessons' => $this->reorderLessons($course, $slugs),
            'pages' => $this->reorderPages($course, (string) $request->get('lesson_slug'), $slugs),
            default => throw new RuntimeException("Ismeretlen típus: {$type} (lessons vagy pages lehet)."),
        };

        $this->logWrite($user, 'reorder_items', ['course_slug' => $course->slug, 'type' => $type]);

        return Response::json(['status' => 'reordered']);
    }

    /**
     * @param  list<string>  $slugs
     */
    private function reorderLessons(Course $course, array $slugs): void
    {
        DB::transaction(function () use ($course, $slugs) {
            foreach ($slugs as $index => $slug) {
                $lesson = $this->resolveLesson($course, $slug);
                $lesson->update(['order' => $index]);
            }
        });
    }

    /**
     * @param  list<string>  $slugs
     */
    private function reorderPages(Course $course, string $lessonSlug, array $slugs): void
    {
        $lesson = $this->resolveLesson($course, $lessonSlug);

        DB::transaction(function () use ($lesson, $slugs) {
            foreach ($slugs as $index => $slug) {
                $page = $this->resolvePage($lesson, $slug);
                $page->update(['order' => $index]);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'course_slug' => $schema->string()->required(),
            'type' => $schema->string()->enum(['lessons', 'pages'])->required(),
            'lesson_slug' => $schema->string()->description('Required when type is "pages".'),
            'slugs' => $schema->array()->items($schema->string())->description('All slugs of the reordered level, in the desired order.')->required(),
        ];
    }
}
