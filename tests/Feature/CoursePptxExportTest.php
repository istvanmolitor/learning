<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class CoursePptxExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pptx_export_contains_one_slide_per_section_plus_quiz(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put(
            'course-media/1/images/abra.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );

        $user = User::factory()->create();
        $course = Course::create(['title' => 'PPTX kurzus', 'slug' => 'pptx-kurzus', 'is_published' => true, 'order' => 0]);
        $course->enrollments()->create(['user_id' => $user->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);

        $lesson = $course->lessons()->create(['title' => 'Lecke 1', 'slug' => 'lecke-1', 'order' => 0]);
        $lesson->pages()->create([
            'title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0,
            'markdown' => "## Első szakasz\n\nSzöveg.\n\n<figure><img src=\"/storage/course-media/1/images/abra.png\" alt=\"ábra\"></figure>\n\n".
                "## Második szakasz\n\nMég több szöveg.\n\n".
                '<iframe width="560" height="315" src="https://www.youtube.com/embed/abc123" title="Videó cím" frameborder="0" allowfullscreen></iframe>',
        ]);
        $lesson->questions()->create(['text' => 'Mi a helyes válasz?', 'order' => 0]);

        $response = $this->actingAs($user)->get("/courses/{$course->slug}/pptx");

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            $response->baseResponse->headers->get('Content-Type')
        );

        $path = tempnam(sys_get_temp_dir(), 'pptx-test-');
        file_put_contents($path, $response->streamedContent());

        $zip = new ZipArchive;
        $zip->open($path);
        $slideCount = 0;
        $hasImage = false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^ppt/slides/slide\d+\.xml$#', $name)) {
                $slideCount++;
            }
            if (str_starts_with($name, 'ppt/media/')) {
                $hasImage = true;
            }
        }

        $zip->close();
        @unlink($path);

        // title + toc + lesson divider + 2 section slides + quiz = 6
        $this->assertSame(6, $slideCount);
        $this->assertTrue($hasImage, 'A beágyazott képnek szerepelnie kell a pptx-ben.');
    }
}
