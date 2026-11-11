<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Page;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

/**
 * Every course-editing MCP tool goes through the same gate: a manage-courses
 * permission check, and any RuntimeException/AuthorizationException raised
 * while resolving the request becomes a tool-level error response instead of
 * an uncaught exception.
 */
abstract class CourseTool extends Tool
{
    public function handle(Request $request): Response
    {
        try {
            $user = $this->authorizedUser();

            return $this->run($request, $user);
        } catch (AuthorizationException|RuntimeException $exception) {
            return Response::error($exception->getMessage());
        }
    }

    abstract protected function run(Request $request, User $user): Response;

    private function authorizedUser(): User
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->hasPermission('manage-courses')) {
            throw new AuthorizationException('Nincs jogosultság a kurzusok kezeléséhez (manage-courses szükséges).');
        }

        return $user;
    }

    protected function resolveCourse(string $slug): Course
    {
        $course = Course::where('slug', $slug)->first();

        if (! $course) {
            throw new RuntimeException("Nincs ilyen kurzus: {$slug}");
        }

        return $course;
    }

    protected function resolveLesson(Course $course, string $slug): Lesson
    {
        $lesson = $course->lessons()->where('slug', $slug)->first();

        if (! $lesson) {
            throw new RuntimeException("Nincs ilyen lecke a „{$course->title}” kurzusban: {$slug}");
        }

        return $lesson;
    }

    protected function resolvePage(Lesson $lesson, string $slug): Page
    {
        $page = $lesson->pages()->where('slug', $slug)->first();

        if (! $page) {
            throw new RuntimeException("Nincs ilyen oldal a „{$lesson->title}” leckében: {$slug}");
        }

        return $page;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function logWrite(User $user, string $action, array $context = []): void
    {
        Log::info("mcp.course.{$action}", [...$context, 'user_id' => $user->id, 'user_email' => $user->email]);
    }
}
