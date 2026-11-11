<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CourseEditorTest extends TestCase
{
    use RefreshDatabase;

    private function makeManager(): User
    {
        $group = Group::create(['name' => 'Kurzusmenedzser']);
        $permission = Permission::create(['name' => 'manage-courses', 'description' => 'x']);
        $group->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->groups()->attach($group);

        return $user;
    }

    private function makeCourse(): Course
    {
        $course = Course::create(['title' => 'Szerkesztős kurzus', 'slug' => 'szerkesztos', 'order' => 0]);
        $lesson = $course->lessons()->create(['title' => 'Lecke', 'slug' => 'lecke', 'order' => 0]);
        $lesson->pages()->create(['title' => 'Oldal', 'slug' => 'oldal', 'order' => 0, 'markdown' => '# Hello']);

        return $course;
    }

    public function test_editor_screen_renders_with_full_course_tree(): void
    {
        $manager = $this->makeManager();
        $course = $this->makeCourse();

        $this->actingAs($manager)
            ->get("/admin/courses/{$course->id}/lessons")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/courses/Editor')
                ->where('course.lessons.0.pages.0.markdown', '# Hello'));
    }

    public function test_lessons_can_be_reordered(): void
    {
        $manager = $this->makeManager();
        $course = $this->makeCourse();
        $second = $course->lessons()->create(['title' => 'Második', 'slug' => 'masodik', 'order' => 1]);

        $this->actingAs($manager)
            ->post("/admin/courses/{$course->id}/lessons/reorder", [
                'lessons' => [
                    ['id' => $second->id, 'order' => 0],
                    ['id' => $course->lessons()->where('slug', 'lecke')->first()->id, 'order' => 1],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(['masodik', 'lecke'], $course->lessons()->orderBy('order')->pluck('slug')->all());
    }

    public function test_lesson_store_autogenerates_unique_slug_and_appends(): void
    {
        $manager = $this->makeManager();
        $course = $this->makeCourse();

        $this->actingAs($manager)->post("/admin/courses/{$course->id}/lessons", ['title' => 'Új lecke']);
        $this->actingAs($manager)->post("/admin/courses/{$course->id}/lessons", ['title' => 'Új lecke']);

        $slugs = $course->lessons()->orderBy('order')->pluck('slug')->all();

        $this->assertSame(['lecke', 'uj-lecke', 'uj-lecke-1'], $slugs);
    }

    public function test_page_update_rerenders_body_html(): void
    {
        $manager = $this->makeManager();
        $course = $this->makeCourse();
        $page = $course->lessons()->first()->pages()->first();

        $this->actingAs($manager)
            ->patch("/admin/pages/{$page->id}", [
                'title' => 'Oldal',
                'markdown' => '## Új szakasz',
            ])
            ->assertRedirect();

        $page->refresh();
        $this->assertSame('## Új szakasz', $page->markdown);
        $this->assertStringContainsString('<h2>Új szakasz</h2>', $page->body_html);
    }

    public function test_markdown_upload_appends_pages_to_lesson(): void
    {
        $manager = $this->makeManager();
        $course = $this->makeCourse();
        $lesson = $course->lessons()->first();

        $file = UploadedFile::fake()->createWithContent('extra-anyag.md', '# Extra');

        $this->actingAs($manager)
            ->post("/admin/lessons/{$lesson->id}/pages/upload", ['files' => [$file]])
            ->assertRedirect();

        $this->assertSame(2, $lesson->pages()->count());
        $this->assertSame(['oldal', 'extra-anyag'], $lesson->pages()->pluck('slug')->all());
    }
}
