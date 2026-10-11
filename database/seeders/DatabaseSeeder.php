<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        $student = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $course = Course::create([
            'title' => 'Bevezetés a webfejlesztésbe',
            'slug' => 'bevezetes-a-webfejlesztesbe',
            'description' => 'Egy rövid, demonstrációs kurzus a HTML, CSS és JavaScript alapjairól.',
            'is_published' => true,
            'order' => 0,
        ]);

        $lesson1 = $course->lessons()->create([
            'title' => 'A HTML alapjai',
            'slug' => 'a-html-alapjai',
            'order' => 0,
        ]);

        $page1 = $lesson1->pages()->create([
            'title' => 'Mi a HTML?',
            'slug' => 'mi-a-html',
            'order' => 0,
        ]);

        $page1->contentBlocks()->createMany([
            [
                'type' => 'heading',
                'data' => ['text' => 'Mi a HTML?', 'level' => 1],
                'order' => 0,
            ],
            [
                'type' => 'text',
                'data' => ['html' => '<p>A HTML (HyperText Markup Language) a weboldalak szerkezetét leíró jelölőnyelv.</p>'],
                'order' => 1,
            ],
            [
                'type' => 'youtube_video',
                'data' => ['video_id' => 'UB1O30fR-EE', 'title' => 'HTML Crash Course'],
                'order' => 2,
            ],
        ]);

        $page2 = $lesson1->pages()->create([
            'title' => 'Alapvető HTML elemek',
            'slug' => 'alapveto-html-elemek',
            'order' => 1,
        ]);

        $page2->contentBlocks()->createMany([
            [
                'type' => 'heading',
                'data' => ['text' => 'Gyakran használt elemek', 'level' => 2],
                'order' => 0,
            ],
            [
                'type' => 'text',
                'data' => ['html' => '<p>Néhány alapvető elem: <code>&lt;div&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;a&gt;</code>, <code>&lt;img&gt;</code>.</p>'],
                'order' => 1,
            ],
        ]);

        $question1 = $lesson1->questions()->create([
            'text' => 'Mit jelent a HTML rövidítés?',
            'order' => 0,
        ]);

        $question1->answerOptions()->createMany([
            ['text' => 'HyperText Markup Language', 'is_correct' => true, 'order' => 0],
            ['text' => 'High Tech Modern Language', 'is_correct' => false, 'order' => 1],
            ['text' => 'Home Tool Markup Language', 'is_correct' => false, 'order' => 2],
        ]);

        $lesson2 = $course->lessons()->create([
            'title' => 'A CSS alapjai',
            'slug' => 'a-css-alapjai',
            'order' => 1,
        ]);

        $page3 = $lesson2->pages()->create([
            'title' => 'Mi a CSS?',
            'slug' => 'mi-a-css',
            'order' => 0,
        ]);

        $page3->contentBlocks()->createMany([
            [
                'type' => 'heading',
                'data' => ['text' => 'Mi a CSS?', 'level' => 1],
                'order' => 0,
            ],
            [
                'type' => 'text',
                'data' => ['html' => '<p>A CSS (Cascading Style Sheets) a weboldalak megjelenését, stílusát írja le.</p>'],
                'order' => 1,
            ],
        ]);

        $question2 = $lesson2->questions()->create([
            'text' => 'Mire szolgál a CSS?',
            'order' => 0,
        ]);

        $question2->answerOptions()->createMany([
            ['text' => 'A weboldal megjelenésének (stílus) leírására', 'is_correct' => true, 'order' => 0],
            ['text' => 'Adatbázis-kezelésre', 'is_correct' => false, 'order' => 1],
        ]);

        $student->enrollments()->create([
            'course_id' => $course->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);
    }
}
