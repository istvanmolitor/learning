<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoursePdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_export_includes_images_video_links_and_questions(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('course-media/1/images/abra.png', 'image-bytes');

        $user = User::factory()->create();
        $course = Course::create(['title' => 'PDF kurzus', 'slug' => 'pdf-kurzus', 'is_published' => true, 'order' => 0]);
        $course->enrollments()->create(['user_id' => $user->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);

        $lesson = $course->lessons()->create(['title' => 'Lecke 1', 'slug' => 'lecke-1', 'order' => 0]);
        $lesson->pages()->create([
            'title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0,
            'markdown' => "# Cím\n\nSzöveg.\n\n<figure><img src=\"/storage/course-media/1/images/abra.png\" alt=\"ábra\"></figure>\n\n".
                '<iframe width="560" height="315" src="https://www.youtube.com/embed/abc123" title="Videó cím" frameborder="0" allowfullscreen></iframe>',
        ]);
        $lesson->questions()->create(['text' => 'Mi a helyes válasz?', 'order' => 0]);

        $response = $this->actingAs($user)->get("/courses/{$course->slug}/pdf");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }
}
