<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a new, empty lesson at the end of a course.')]
class CreateLessonTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $course = $this->resolveCourse((string) $request->get('course_slug'));
        $title = (string) $request->get('title');
        $slug = $this->uniqueSlug($course, (string) ($request->get('slug') ?: $title));

        $lesson = $course->lessons()->create([
            'title' => $title,
            'slug' => $slug,
            'order' => ($course->lessons()->max('order') ?? -1) + 1,
        ]);

        $this->logWrite($user, 'create_lesson', ['course_slug' => $course->slug, 'lesson_slug' => $lesson->slug]);

        return Response::json(['status' => 'created', 'lesson_slug' => $lesson->slug]);
    }

    private function uniqueSlug(Course $course, string $title): string
    {
        $base = Str::slug($title) ?: 'lecke';
        $slug = $base;
        $i = 1;

        while ($course->lessons()->where('slug', $slug)->exists()) {
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
            'title' => $schema->string()->required(),
            'slug' => $schema->string()->description('Optional; derived from the title when omitted.'),
        ];
    }
}
