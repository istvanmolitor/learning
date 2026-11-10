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
        $response->assertRedirect("/admin/courses/{$course->id}/edit");
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
            ->assertRedirect("/admin/courses/{$course->id}/edit");

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
}
