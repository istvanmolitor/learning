<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\CourseTool;
use App\Models\User;
use App\Services\CourseTransfer\ImportPlanner;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description(
    'Import a course zip package (base64-encoded), mirroring the admin import wizard. First call with '.
    'zip_base64 only: stages the package and returns a plan (what would be created/updated/deleted) plus '.
    'a token, without writing anything. If the plan shows deletions, call again with the same token and '.
    'confirm_deletes=true to actually import; otherwise call again with just the token to confirm.'
)]
class ImportCourseTool extends CourseTool
{
    protected function run(Request $request, User $user): Response
    {
        $planner = app(ImportPlanner::class);
        $token = $request->get('token');

        if ($token === null) {
            $zipBase64 = (string) $request->get('zip_base64');
            $decoded = base64_decode($zipBase64, strict: true);

            if ($decoded === false) {
                return Response::error('A zip_base64 nem érvényes base64 adat.');
            }

            $tmpPath = tempnam(sys_get_temp_dir(), 'mcp-import-').'.zip';
            file_put_contents($tmpPath, $decoded);

            $uploaded = new UploadedFile($tmpPath, (string) ($request->get('filename') ?? 'import.zip'), 'application/zip', null, true);
            $staged = $planner->stage([$uploaded]);
            @unlink($tmpPath);

            return Response::json(['status' => 'staged', 'token' => $staged['token'], 'plan' => $staged['plan']]);
        }

        try {
            $course = $planner->confirm((string) $token, [
                'mode' => (string) ($request->get('mode') ?? 'auto'),
                'new_slug' => $request->get('new_slug') ? Str::slug((string) $request->get('new_slug')) : null,
                'confirm_deletes' => (bool) $request->get('confirm_deletes', false),
                'split_long_pages' => (bool) $request->get('split_long_pages', false),
            ], $user->id);
        } catch (ValidationException $exception) {
            return Response::error(implode(' ', $exception->errors()['confirm_deletes'] ?? ['A törléssel járó frissítéshez megerősítés kell (confirm_deletes).']));
        }

        $this->logWrite($user, 'import_course', ['course_slug' => $course->slug]);

        return Response::json(['status' => 'imported', 'course_slug' => $course->slug]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'zip_base64' => $schema->string()->description('Required for the first call (staging); omit on the confirm call.'),
            'filename' => $schema->string()->description('Original filename, used only to guess a title fallback.'),
            'token' => $schema->string()->description('Returned by the staging call; pass it back to confirm the import.'),
            'mode' => $schema->string()->enum(['auto', 'new'])->description('"new" creates a separate course even if the slug collides.'),
            'new_slug' => $schema->string()->description('Required when mode is "new".'),
            'confirm_deletes' => $schema->boolean()->description('Required (true) when the staged plan shows deletions.'),
            'split_long_pages' => $schema->boolean()->description('Auto-split over-long pages along ## headings.'),
        ];
    }
}
