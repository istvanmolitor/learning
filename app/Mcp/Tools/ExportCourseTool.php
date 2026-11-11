<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\User;
use App\Services\CourseTransfer\CourseExporter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Export a course as the canonical zip package (course.json + Markdown pages + images + narration + thumbnail), base64-encoded. Re-importing this package elsewhere reproduces the course exactly.')]
class ExportCourseTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $course = $this->resolveCourse((string) $request->get('course_slug'));

        $zipPath = app(CourseExporter::class)->export($course);
        $contents = (string) file_get_contents($zipPath);
        @unlink($zipPath);

        $this->logWrite($user, 'export_course', ['course_slug' => $course->slug]);

        return Response::json([
            'filename' => "{$course->slug}.zip",
            'size_bytes' => strlen($contents),
            'zip_base64' => base64_encode($contents),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'course_slug' => $schema->string()->required(),
        ];
    }
}
