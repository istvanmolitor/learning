<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseFlowTest extends TestCase
{
    use RefreshDatabase;

    private function creatorUser(): User
    {
        $group = Group::create(['name' => 'Kurzuskeszito']);
        $permission = Permission::create(['name' => 'create-courses', 'description' => 'x']);
        $group->permissions()->attach($permission);

        $creator = User::factory()->create();
        $creator->groups()->attach($group);

        return $creator;
    }

    private function manageCoursesUser(): User
    {
        $group = Group::create(['name' => 'Kurzusmenedzser']);
        $permission = Permission::create(['name' => 'manage-courses', 'description' => 'x']);
        $group->permissions()->attach($permission);

        $manager = User::factory()->create();
        $manager->groups()->attach($group);

        return $manager;
    }

    public function test_create_courses_only_user_can_create_a_course_and_it_records_the_creator(): void
    {
        $creator = $this->creatorUser();

        $response = $this->actingAs($creator)->post('/admin/courses', [
            'title' => 'Flow teszt',
            'slug' => 'flow-teszt',
            'is_published' => '0',
            'order' => 0,
        ]);

        $course = Course::where('slug', 'flow-teszt')->first();

        $this->assertNotNull($course);
        $this->assertSame($creator->id, $course->created_by);
        $this->assertTrue($course->isEnrolledBy($creator), 'A létrehozónak automatikusan megjelennie a saját kurzusai között.');
        $response->assertRedirect("/admin/courses/{$course->id}/lessons");
    }

    public function test_created_course_appears_on_the_creators_own_courses_page(): void
    {
        $creator = $this->creatorUser();

        $this->actingAs($creator)->post('/admin/courses', [
            'title' => 'Saját lista teszt',
            'slug' => 'sajat-lista-teszt',
            'is_published' => '0',
            'order' => 0,
        ]);

        $this->actingAs($creator)
            ->get('/courses')
            ->assertInertia(fn ($page) => $page->has('courses', 1));
    }

    public function test_create_courses_only_user_cannot_update_a_course_they_did_not_create(): void
    {
        $creator = $this->creatorUser();

        $otherCourse = Course::create([
            'title' => 'Masvalakie', 'slug' => 'masvalakie', 'is_published' => false, 'order' => 0,
        ]);

        $this->actingAs($creator)
            ->put("/admin/courses/{$otherCourse->id}", ['title' => 'x', 'slug' => 'masvalakie', 'is_published' => '0', 'order' => 0])
            ->assertForbidden();
    }

    public function test_course_creator_can_assign_enrollments_on_their_own_course_without_manage_users(): void
    {
        $creator = $this->creatorUser();
        $student = User::factory()->create();

        $course = Course::create([
            'title' => 'Sajat kurzus', 'slug' => 'sajat-kurzus', 'is_published' => false, 'order' => 0, 'created_by' => $creator->id,
        ]);

        $this->actingAs($creator)
            ->post("/admin/courses/{$course->id}/enrollments", ['user_id' => $student->id])
            ->assertRedirect("/admin/courses/{$course->id}/enrollments");

        $this->assertTrue($course->enrollments()->where('user_id', $student->id)->exists());
    }

    public function test_create_courses_only_user_cannot_assign_enrollments_on_a_course_they_did_not_create(): void
    {
        $creator = $this->creatorUser();
        $student = User::factory()->create();

        $otherCourse = Course::create([
            'title' => 'Masvalakie', 'slug' => 'masvalakie', 'is_published' => false, 'order' => 0,
        ]);

        $this->actingAs($creator)
            ->post("/admin/courses/{$otherCourse->id}/enrollments", ['user_id' => $student->id])
            ->assertForbidden();
    }

    public function test_create_courses_only_user_can_view_the_import_page(): void
    {
        $creator = $this->creatorUser();

        $this->actingAs($creator)
            ->get('/admin/courses/import')
            ->assertOk();
    }

    public function test_create_courses_only_user_cannot_view_the_export_page(): void
    {
        $creator = $this->creatorUser();

        $this->actingAs($creator)
            ->get('/admin/courses/export')
            ->assertForbidden();
    }

    public function test_lessons_page_shows_lessons_and_their_pages(): void
    {
        $manager = $this->manageCoursesUser();

        $course = Course::create([
            'title' => 'Lecke teszt', 'slug' => 'lecke-teszt', 'is_published' => false, 'order' => 0,
        ]);
        $lesson = $course->lessons()->create(['title' => 'Elso lecke', 'slug' => 'elso-lecke', 'order' => 0]);
        $lesson->pages()->create(['title' => 'Elso oldal', 'slug' => 'elso-oldal', 'order' => 0]);

        $this->actingAs($manager)
            ->get("/admin/courses/{$course->id}/lessons")
            ->assertInertia(fn ($page) => $page
                ->where('course.lessons.0.title', 'Elso lecke')
                ->where('course.lessons.0.pages.0.title', 'Elso oldal'));
    }

    public function test_reordering_pages_can_move_a_page_to_another_lesson_in_the_same_course(): void
    {
        $manager = $this->manageCoursesUser();

        $course = Course::create([
            'title' => 'Reorder teszt', 'slug' => 'reorder-teszt', 'is_published' => false, 'order' => 0,
        ]);
        $lessonA = $course->lessons()->create(['title' => 'A lecke', 'slug' => 'a-lecke', 'order' => 0]);
        $lessonB = $course->lessons()->create(['title' => 'B lecke', 'slug' => 'b-lecke', 'order' => 1]);
        $page = $lessonA->pages()->create(['title' => 'Oldal', 'slug' => 'oldal', 'order' => 0]);

        $this->actingAs($manager)
            ->post("/admin/courses/{$course->id}/pages/reorder", [
                'pages' => [
                    ['id' => $page->id, 'lesson_id' => $lessonB->id, 'order' => 0],
                ],
            ])
            ->assertRedirect();

        $this->assertSame($lessonB->id, $page->refresh()->lesson_id);
    }

    public function test_reordering_pages_rejects_a_page_from_another_course(): void
    {
        $manager = $this->manageCoursesUser();

        $course = Course::create([
            'title' => 'Kurzus A', 'slug' => 'kurzus-a', 'is_published' => false, 'order' => 0,
        ]);
        $lesson = $course->lessons()->create(['title' => 'A lecke', 'slug' => 'a-lecke', 'order' => 0]);

        $otherCourse = Course::create([
            'title' => 'Kurzus B', 'slug' => 'kurzus-b', 'is_published' => false, 'order' => 0,
        ]);
        $otherLesson = $otherCourse->lessons()->create(['title' => 'B lecke', 'slug' => 'b-lecke', 'order' => 0]);
        $otherPage = $otherLesson->pages()->create(['title' => 'Oldal', 'slug' => 'oldal', 'order' => 0]);

        $this->actingAs($manager)
            ->post("/admin/courses/{$course->id}/pages/reorder", [
                'pages' => [
                    ['id' => $otherPage->id, 'lesson_id' => $lesson->id, 'order' => 0],
                ],
            ])
            ->assertInvalid('pages.0.id');

        $this->assertSame($otherLesson->id, $otherPage->refresh()->lesson_id);
    }
}
