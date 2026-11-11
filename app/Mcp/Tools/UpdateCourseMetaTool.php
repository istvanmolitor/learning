<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Update a course\'s title, description, publish state, or display order. Only the provided fields change.')]
class UpdateCourseMetaTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $course = $this->resolveCourse((string) $request->get('course_slug'));

        $changes = array_filter(
            $request->only(['title', 'description', 'is_published', 'order']),
            fn ($value) => $value !== null,
        );

        $course->update($changes);

        $this->logWrite($user, 'update_course_meta', ['course_slug' => $course->slug, 'fields' => array_keys($changes)]);

        return Response::json(['status' => 'updated', 'course_slug' => $course->slug]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'course_slug' => $schema->string()->required(),
            'title' => $schema->string(),
            'description' => $schema->string(),
            'is_published' => $schema->boolean(),
            'order' => $schema->integer(),
        ];
    }
}
