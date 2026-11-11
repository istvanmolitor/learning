<?php

namespace Tests\Feature;

use App\Mcp\Servers\CourseServer;
use App\Mcp\Tools\CreateLessonTool;
use App\Mcp\Tools\DeleteItemTool;
use App\Mcp\Tools\ExportCourseTool;
use App\Mcp\Tools\GetCourseStructureTool;
use App\Mcp\Tools\ImportCourseTool;
use App\Mcp\Tools\ListCoursesTool;
use App\Mcp\Tools\RenameItemTool;
use App\Mcp\Tools\ReorderItemsTool;
use App\Mcp\Tools\SetQuestionsTool;
use App\Mcp\Tools\UpdateCourseMetaTool;
use App\Mcp\Tools\UpdatePageMarkdownTool;
use App\Models\Course;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\Testing\TestResponse;
use ReflectionProperty;
use Tests\TestCase;

class McpCourseServerTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        $group = Group::create(['name' => 'Kurzusmenedzser']);
        $permission = Permission::create(['name' => 'manage-courses', 'description' => 'x']);
        $group->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->groups()->attach($group);

        return $user;
    }

    private function courseWithContent(): Course
    {
        $course = Course::create(['title' => 'MCP kurzus', 'slug' => 'mcp-kurzus', 'order' => 0]);
        $lesson = $course->lessons()->create(['title' => 'Lecke 1', 'slug' => 'lecke-1', 'order' => 0]);
        $lesson->pages()->create(['title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0, 'markdown' => '# Eredeti']);

        return $course;
    }

    public function test_tools_reject_users_without_manage_courses_permission(): void
    {
        $user = User::factory()->create();

        CourseServer::actingAs($user)
            ->tool(ListCoursesTool::class, [])
            ->assertHasErrors()
            ->assertSee('Nincs jogosultság');
    }

    public function test_list_courses_returns_known_courses(): void
    {
        $this->courseWithContent();

        CourseServer::actingAs($this->manager())
            ->tool(ListCoursesTool::class, [])
            ->assertHasNoErrors()
            ->assertSee('mcp-kurzus');
    }

    public function test_get_course_structure_reports_pages_and_word_counts(): void
    {
        $this->courseWithContent();

        CourseServer::actingAs($this->manager())
            ->tool(GetCourseStructureTool::class, ['course_slug' => 'mcp-kurzus'])
            ->assertHasNoErrors()
            ->assertSee('oldal-1')
            ->assertSee('word_count');
    }

    public function test_update_page_markdown_rerenders_body_html(): void
    {
        $course = $this->courseWithContent();

        CourseServer::actingAs($this->manager())
            ->tool(UpdatePageMarkdownTool::class, [
                'course_slug' => 'mcp-kurzus',
                'lesson_slug' => 'lecke-1',
                'page_slug' => 'oldal-1',
                'markdown' => '## Átírva',
            ])
            ->assertHasNoErrors();

        $page = $course->lessons()->first()->pages()->first();
        $this->assertSame('## Átírva', $page->markdown);
        $this->assertStringContainsString('<h2>Átírva</h2>', $page->body_html);
    }

    public function test_create_lesson_appends_and_rename_updates_title_only(): void
    {
        $course = $this->courseWithContent();
        $manager = $this->manager();

        CourseServer::actingAs($manager)
            ->tool(CreateLessonTool::class, ['course_slug' => 'mcp-kurzus', 'title' => 'Új lecke'])
            ->assertHasNoErrors()
            ->assertSee('uj-lecke');

        $this->assertSame(2, $course->lessons()->count());

        CourseServer::actingAs($manager)
            ->tool(RenameItemTool::class, [
                'course_slug' => 'mcp-kurzus',
                'type' => 'lesson',
                'lesson_slug' => 'uj-lecke',
                'title' => 'Átnevezett lecke',
            ])
            ->assertHasNoErrors();

        $lesson = $course->lessons()->where('slug', 'uj-lecke')->first();
        $this->assertSame('Átnevezett lecke', $lesson->title);
        $this->assertSame('uj-lecke', $lesson->slug, 'A slug-nak stabilnak kell maradnia átnevezéskor.');
    }

    public function test_reorder_items_reorders_lessons(): void
    {
        $course = $this->courseWithContent();
        $second = $course->lessons()->create(['title' => 'Lecke 2', 'slug' => 'lecke-2', 'order' => 1]);

        CourseServer::actingAs($this->manager())
            ->tool(ReorderItemsTool::class, [
                'course_slug' => 'mcp-kurzus',
                'type' => 'lessons',
                'slugs' => ['lecke-2', 'lecke-1'],
            ])
            ->assertHasNoErrors();

        $this->assertSame(['lecke-2', 'lecke-1'], $course->lessons()->orderBy('order')->pluck('slug')->all());
    }

    public function test_set_questions_replaces_lessons_quiz(): void
    {
        $course = $this->courseWithContent();
        $lesson = $course->lessons()->first();
        $lesson->questions()->create(['text' => 'Régi kérdés', 'order' => 0]);

        CourseServer::actingAs($this->manager())
            ->tool(SetQuestionsTool::class, [
                'course_slug' => 'mcp-kurzus',
                'lesson_slug' => 'lecke-1',
                'questions' => [
                    ['text' => 'Új kérdés?', 'answer_options' => [
                        ['text' => 'Igen', 'is_correct' => true],
                        ['text' => 'Nem', 'is_correct' => false],
                    ]],
                ],
            ])
            ->assertHasNoErrors();

        $lesson->refresh();
        $this->assertSame(1, $lesson->questions()->count());
        $this->assertSame('Új kérdés?', $lesson->questions()->first()->text);
        $this->assertSame(2, $lesson->questions()->first()->answerOptions()->count());
    }

    public function test_delete_item_removes_a_page(): void
    {
        $course = $this->courseWithContent();

        CourseServer::actingAs($this->manager())
            ->tool(DeleteItemTool::class, [
                'course_slug' => 'mcp-kurzus',
                'type' => 'page',
                'lesson_slug' => 'lecke-1',
                'page_slug' => 'oldal-1',
            ])
            ->assertHasNoErrors();

        $this->assertSame(0, $course->lessons()->first()->pages()->count());
    }

    public function test_update_course_meta_only_changes_given_fields(): void
    {
        $course = $this->courseWithContent();

        CourseServer::actingAs($this->manager())
            ->tool(UpdateCourseMetaTool::class, [
                'course_slug' => 'mcp-kurzus',
                'is_published' => true,
            ])
            ->assertHasNoErrors();

        $course->refresh();
        $this->assertTrue($course->is_published);
        $this->assertSame('MCP kurzus', $course->title, 'Nem küldött mező nem változhat.');
    }

    public function test_export_then_import_round_trip_via_base64(): void
    {
        $this->courseWithContent();
        $manager = $this->manager();

        $exportResponse = CourseServer::actingAs($manager)
            ->tool(ExportCourseTool::class, ['course_slug' => 'mcp-kurzus'])
            ->assertHasNoErrors();

        $payload = json_decode($this->toolText($exportResponse), true);
        $this->assertNotEmpty($payload['zip_base64']);

        Course::where('slug', 'mcp-kurzus')->first()->lessons()->each(fn ($l) => $l->delete());
        Course::where('slug', 'mcp-kurzus')->delete();

        $staged = CourseServer::actingAs($manager)
            ->tool(ImportCourseTool::class, [
                'zip_base64' => $payload['zip_base64'],
                'filename' => $payload['filename'],
            ])
            ->assertHasNoErrors();

        $stagedPayload = json_decode($this->toolText($staged), true);
        $this->assertSame('staged', $stagedPayload['status']);
        $this->assertNotEmpty($stagedPayload['token']);

        $confirmed = CourseServer::actingAs($manager)
            ->tool(ImportCourseTool::class, ['token' => $stagedPayload['token']])
            ->assertHasNoErrors();

        $confirmedPayload = json_decode($this->toolText($confirmed), true);
        $this->assertSame('imported', $confirmedPayload['status']);
        $this->assertSame('mcp-kurzus', $confirmedPayload['course_slug']);
        $this->assertSame(1, Course::where('slug', 'mcp-kurzus')->count());
    }

    private function toolText(TestResponse $response): string
    {
        $property = new ReflectionProperty(TestResponse::class, 'response');
        $jsonRpcResponse = $property->getValue($response);

        return $jsonRpcResponse->toArray()['result']['content'][0]['text'];
    }
}
