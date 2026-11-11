<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = collect([
            ['name' => 'manage-courses', 'description' => 'Kurzusok, leckék és tartalmak kezelése'],
            ['name' => 'create-courses', 'description' => 'Új kurzus létrehozása'],
            ['name' => 'manage-users', 'description' => 'Felhasználók és kurzus hozzárendelések kezelése'],
            ['name' => 'manage-groups', 'description' => 'Felhasználói csoportok és jogosultságok kezelése'],
        ])->map(fn (array $permission) => Permission::create($permission));

        $adminGroup = Group::create(['name' => 'admin']);
        $adminGroup->permissions()->attach($permissions->pluck('id'));

        $userGroup = Group::create(['name' => 'Felhasználó']);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->groups()->attach($adminGroup);

        $student = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $student->groups()->attach($userGroup);

        $course = Course::create([
            'title' => 'Bevezetés a webfejlesztésbe',
            'slug' => 'bevezetes-a-webfejlesztesbe',
            'description' => 'Egy rövid, demonstrációs kurzus a HTML, CSS és JavaScript alapjairól.',
            'is_published' => true,
            'order' => 0,
            'created_by' => $admin->id,
        ]);

        $lesson1 = $course->lessons()->create([
            'title' => 'A HTML alapjai',
            'slug' => 'a-html-alapjai',
            'order' => 0,
        ]);

        $lesson1->pages()->create([
            'title' => 'Mi a HTML?',
            'slug' => 'mi-a-html',
            'order' => 0,
            'markdown' => <<<'MD'
            # Mi a HTML?

            A HTML (HyperText Markup Language) a weboldalak szerkezetét leíró jelölőnyelv.

            <iframe width="560" height="315" src="https://www.youtube.com/embed/UB1O30fR-EE" title="HTML Crash Course" frameborder="0" allowfullscreen></iframe>
            MD,
        ]);

        $lesson1->pages()->create([
            'title' => 'Alapvető HTML elemek',
            'slug' => 'alapveto-html-elemek',
            'order' => 1,
            'markdown' => <<<'MD'
            ## Gyakran használt elemek

            Néhány alapvető elem: `<div>`, `<p>`, `<a>`, `<img>`.
            MD,
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

        $lesson2->pages()->create([
            'title' => 'Mi a CSS?',
            'slug' => 'mi-a-css',
            'order' => 0,
            'markdown' => <<<'MD'
            # Mi a CSS?

            A CSS (Cascading Style Sheets) a weboldalak megjelenését, stílusát írja le.
            MD,
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
