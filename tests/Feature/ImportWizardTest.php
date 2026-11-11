<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Services\CourseTransfer\ImportPlanner;
use App\Services\CourseTransfer\MarkdownSplitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use ZipArchive;

class ImportWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_and_confirm_imports_a_zip_package(): void
    {
        $staged = app(ImportPlanner::class)->stage([$this->zipUpload('wizard-kurzus', [
            'wizard-kurzus/course.json' => json_encode([
                'title' => 'Wizard kurzus', 'slug' => 'wizard-kurzus',
                'lessons' => [[
                    'title' => 'Lecke 1', 'slug' => 'lecke-1', 'order' => 0,
                    'pages' => [['title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0, 'file' => 'pages/lecke-1/oldal-1.md']],
                    'questions' => [],
                ]],
            ]),
            'wizard-kurzus/pages/lecke-1/oldal-1.md' => '# Tartalom',
        ])]);

        $this->assertSame('zip-manifest', $staged['plan']['format']);
        $this->assertSame('wizard-kurzus', $staged['plan']['course']['slug']);
        $this->assertNull($staged['plan']['existing_course']);
        $this->assertSame([], $staged['plan']['warnings']);
        $this->assertSame(0, Course::count(), 'Az elemzés nem írhat az adatbázisba.');

        $course = app(ImportPlanner::class)->confirm($staged['token'], ['mode' => 'auto'], null);

        $this->assertSame('wizard-kurzus', $course->slug);
        $this->assertSame(1, Course::count());
        $this->assertDirectoryDoesNotExist(storage_path('app/tmp/course-import-wizard/'.$staged['token']));
    }

    public function test_plan_reports_deletions_and_confirm_requires_acknowledgement(): void
    {
        $course = Course::create(['title' => 'Meglévő', 'slug' => 'meglevo-kurzus', 'order' => 0]);
        $lesson = $course->lessons()->create(['title' => 'Marad', 'slug' => 'marad', 'order' => 0]);
        $lesson->pages()->create(['title' => 'Törlendő oldal', 'slug' => 'torlendo', 'order' => 0, 'markdown' => 'x']);
        $course->lessons()->create(['title' => 'Törlendő lecke', 'slug' => 'torlendo-lecke', 'order' => 1]);

        $staged = app(ImportPlanner::class)->stage([$this->zipUpload('meglevo-kurzus', [
            'meglevo-kurzus/course.json' => json_encode([
                'title' => 'Meglévő v2', 'slug' => 'meglevo-kurzus',
                'lessons' => [[
                    'title' => 'Marad', 'slug' => 'marad', 'order' => 0,
                    'pages' => [['title' => 'Új oldal', 'slug' => 'uj-oldal', 'order' => 0, 'file' => 'pages/marad/uj-oldal.md']],
                    'questions' => [],
                ]],
            ]),
            'meglevo-kurzus/pages/marad/uj-oldal.md' => 'Új tartalom',
        ])]);

        $plan = $staged['plan'];
        $this->assertNotNull($plan['existing_course']);
        $this->assertContains('Törlendő lecke', $plan['changes']['lessons']['delete']);
        $this->assertContains('Marad / Törlendő oldal', $plan['changes']['pages']['delete']);
        $this->assertContains('Marad / Új oldal', $plan['changes']['pages']['create']);

        try {
            app(ImportPlanner::class)->confirm($staged['token'], ['mode' => 'auto'], null);
            $this->fail('Törléssel járó frissítéshez megerősítés kell.');
        } catch (ValidationException) {
            // expected
        }

        $imported = app(ImportPlanner::class)->confirm($staged['token'], ['mode' => 'auto', 'confirm_deletes' => true], null);

        $this->assertSame($course->id, $imported->id);
        $imported->load('lessons.pages');
        $this->assertCount(1, $imported->lessons);
        $this->assertSame('uj-oldal', $imported->lessons->first()->pages->first()->slug);
    }

    public function test_confirm_can_create_a_new_course_under_a_different_slug(): void
    {
        Course::create(['title' => 'Eredeti', 'slug' => 'utkozo-kurzus', 'order' => 0]);

        $staged = app(ImportPlanner::class)->stage([$this->zipUpload('utkozo-kurzus', [
            'utkozo-kurzus/course.json' => json_encode([
                'title' => 'Ütköző', 'slug' => 'utkozo-kurzus',
                'lessons' => [],
            ]),
        ])]);

        $course = app(ImportPlanner::class)->confirm($staged['token'], ['mode' => 'new', 'new_slug' => 'utkozo-kurzus-v2'], null);

        $this->assertSame('utkozo-kurzus-v2', $course->slug);
        $this->assertSame(2, Course::count());
    }

    public function test_plan_warns_about_missing_files_and_long_pages(): void
    {
        $longMarkdown = "## Első rész\n\n".str_repeat('szó ', 2000)."\n\n## Második rész\n\n".str_repeat('szó ', 2000);

        $staged = app(ImportPlanner::class)->stage([$this->zipUpload('hianyos', [
            'hianyos/course.json' => json_encode([
                'title' => 'Hiányos', 'slug' => 'hianyos',
                'lessons' => [[
                    'title' => 'Lecke', 'slug' => 'lecke', 'order' => 0,
                    'pages' => [
                        ['title' => 'Hiányzó', 'slug' => 'hianyzo', 'order' => 0, 'file' => 'pages/lecke/nincs-ilyen.md'],
                        ['title' => 'Hosszú', 'slug' => 'hosszu', 'order' => 1, 'file' => 'pages/lecke/hosszu.md'],
                    ],
                    'questions' => [],
                ]],
            ]),
            'hianyos/pages/lecke/hosszu.md' => $longMarkdown,
        ])]);

        $plan = $staged['plan'];

        $this->assertNotEmpty($plan['warnings']);
        $this->assertStringContainsString('Hiányzó oldalfájl', $plan['warnings'][0]);
        $this->assertCount(1, $plan['long_pages']);
        $this->assertTrue($plan['long_pages'][0]['splittable']);

        app(ImportPlanner::class)->discard($staged['token']);
    }

    public function test_confirm_splits_long_pages_on_request(): void
    {
        $longMarkdown = "Bevezető sorok.\n\n## Első rész\n\n".str_repeat('szó ', 2000)."\n\n## Második rész\n\n".str_repeat('szó ', 2000);

        $staged = app(ImportPlanner::class)->stage([$this->zipUpload('bonthato', [
            'bonthato/course.json' => json_encode([
                'title' => 'Bontható', 'slug' => 'bonthato',
                'lessons' => [[
                    'title' => 'Lecke', 'slug' => 'lecke', 'order' => 0,
                    'pages' => [['title' => 'Hosszú oldal', 'slug' => 'hosszu-oldal', 'order' => 0, 'file' => 'pages/lecke/hosszu.md']],
                    'questions' => [],
                ]],
            ]),
            'bonthato/pages/lecke/hosszu.md' => $longMarkdown,
        ])]);

        $course = app(ImportPlanner::class)->confirm($staged['token'], ['mode' => 'auto', 'split_long_pages' => true], null);

        $course->load('lessons.pages');
        $pages = $course->lessons->first()->pages;

        $this->assertCount(2, $pages, 'A hosszú oldalt két ## szekcióra kellett bontani.');
        $this->assertSame(['Első rész', 'Második rész'], $pages->pluck('title')->all());
        $this->assertStringContainsString('Bevezető sorok.', $pages->first()->markdown, 'A ## előtti bevezető az első szakaszhoz kerül.');
    }

    public function test_markdown_splitter_keeps_code_fences_intact(): void
    {
        $markdown = "## Egy\n\n```md\n## ez nem címsor\n```\n\n## Kettő\n\ntartalom";

        $chunks = MarkdownSplitter::split($markdown);

        $this->assertCount(2, $chunks);
        $this->assertStringContainsString('## ez nem címsor', $chunks[0]['markdown']);
    }

    public function test_validate_command_reports_package_problems(): void
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'validate-').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('k/course.json', json_encode([
            'title' => 'Hibás', 'slug' => 'hibas',
            'lessons' => [[
                'title' => 'Lecke', 'slug' => 'lecke', 'order' => 0,
                'pages' => [['title' => 'Oldal', 'slug' => 'oldal', 'order' => 0, 'file' => 'pages/lecke/nincs.md']],
                'questions' => [],
            ]],
        ]));
        $zip->close();

        $this->artisan('course:validate', ['path' => $zipPath])
            ->expectsOutputToContain('Hiányzó oldalfájl')
            ->assertFailed();

        @unlink($zipPath);
    }

    public function test_validate_command_accepts_a_valid_package(): void
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'validate-').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('k/course.json', json_encode([
            'title' => 'Rendben', 'slug' => 'rendben',
            'lessons' => [[
                'title' => 'Lecke', 'slug' => 'lecke', 'order' => 0,
                'pages' => [['title' => 'Oldal', 'slug' => 'oldal', 'order' => 0, 'file' => 'pages/lecke/oldal.md']],
                'questions' => [],
            ]],
        ]));
        $zip->addFromString('k/pages/lecke/oldal.md', '# Rendben');
        $zip->close();

        $this->artisan('course:validate', ['path' => $zipPath])
            ->expectsOutputToContain('importálható')
            ->assertSuccessful();

        @unlink($zipPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/tmp/course-import-wizard'));

        parent::tearDown();
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function zipUpload(string $name, array $entries): UploadedFile
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'wizard-test-');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $path => $contents) {
            $zip->addFromString($path, $contents);
        }

        $zip->close();

        return new UploadedFile($zipPath, "{$name}.zip", 'application/zip', null, true);
    }
}
