<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Services\CourseTransfer\CourseExporter;
use App\Services\CourseTransfer\CourseImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class CourseTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_packages_markdown_images_and_thumbnail(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('course-media/1/images/abra.png', 'image-bytes');
        Storage::disk('public')->put('course-thumbnails/borito.png', 'thumb-bytes');

        $course = Course::create([
            'title' => 'Eredeti kurzus', 'slug' => 'eredeti-kurzus', 'is_published' => true, 'order' => 0,
            'thumbnail' => 'course-thumbnails/borito.png',
        ]);
        $lesson = $course->lessons()->create(['title' => 'Lecke 1', 'slug' => 'lecke-1', 'order' => 0]);
        $lesson->pages()->create([
            'title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0,
            'markdown' => "## Alcím\n\nSzöveg **kiemelve**.\n\n<figure><img src=\"/storage/course-media/1/images/abra.png\" alt=\"leírás\"></figure>",
        ]);

        $zipPath = app(CourseExporter::class)->export($course);

        $zip = new ZipArchive;
        $zip->open($zipPath);
        $markdown = $zip->getFromName('eredeti-kurzus/pages/lecke-1/oldal-1.md');
        $manifest = json_decode($zip->getFromName('eredeti-kurzus/course.json'), true);
        $image = $zip->getFromName('eredeti-kurzus/images/abra.png');
        $thumbnail = $zip->getFromName('eredeti-kurzus/thumbnail.png');
        $zip->close();
        @unlink($zipPath);

        $this->assertStringContainsString('## Alcím', $markdown);
        $this->assertStringContainsString('**kiemelve**', $markdown);
        $this->assertStringContainsString('src="images/abra.png"', $markdown, 'A kép hivatkozásnak csomag-relatívvá kell válnia.');
        $this->assertSame('image-bytes', $image);
        $this->assertSame('thumb-bytes', $thumbnail);
        $this->assertSame(CourseImporter::FORMAT_VERSION, $manifest['format_version']);
        $this->assertSame('thumbnail.png', $manifest['thumbnail']);
    }

    public function test_import_rewrites_package_relative_images_and_renders_html(): void
    {
        Storage::fake('public');

        $zipPath = $this->makeZip([
            'kurzus/course.json' => json_encode([
                'format_version' => 1,
                'title' => 'Képes kurzus', 'slug' => 'kepes-kurzus', 'thumbnail' => 'thumbnail.png',
                'lessons' => [[
                    'title' => 'Lecke 1', 'slug' => 'lecke-1', 'order' => 0,
                    'pages' => [['title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0, 'file' => 'pages/lecke-1/oldal-1.md']],
                    'questions' => [],
                ]],
            ]),
            'kurzus/pages/lecke-1/oldal-1.md' => "## Alcím\n\nSzöveg **kiemelve**.\n\n<figure><img src=\"images/abra.png\" alt=\"leírás\"></figure>\n\n![inline kép](images/masik.png)",
            'kurzus/images/abra.png' => 'image-bytes',
            'kurzus/images/masik.png' => 'other-bytes',
            'kurzus/thumbnail.png' => 'thumb-bytes',
        ]);

        $uploaded = new UploadedFile($zipPath, 'kepes.zip', 'application/zip', null, true);
        $course = app(CourseImporter::class)->import($uploaded);
        @unlink($zipPath);

        $course->load('lessons.pages');
        $page = $course->lessons->first()->pages->first();

        $this->assertStringContainsString("src=\"/storage/course-media/{$course->id}/images/abra.png\"", $page->markdown);
        $this->assertStringContainsString("](/storage/course-media/{$course->id}/images/masik.png)", $page->markdown);
        $this->assertSame('image-bytes', Storage::disk('public')->get("course-media/{$course->id}/images/abra.png"));

        $this->assertStringContainsString('<h2>Alcím</h2>', $page->body_html);
        $this->assertStringContainsString('<strong>kiemelve</strong>', $page->body_html);

        $this->assertSame('course-thumbnails/kepes-kurzus.png', $course->thumbnail);
        $this->assertSame('thumb-bytes', Storage::disk('public')->get($course->thumbnail));
    }

    public function test_export_import_export_round_trip_yields_identical_packages(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('course-media/1/images/abra.png', 'image-bytes');
        Storage::disk('public')->put('course-thumbnails/rt.png', 'thumb-bytes');
        Storage::disk('public')->put('narration/rt.mp3', 'audio-bytes');

        $course = Course::create([
            'title' => 'Roundtrip kurzus', 'slug' => 'roundtrip-kurzus',
            'description' => 'Veszteségmentes kör.', 'is_published' => true, 'order' => 3,
            'thumbnail' => 'course-thumbnails/rt.png',
        ]);
        $lesson = $course->lessons()->create(['title' => 'Lecke 1', 'slug' => 'lecke-1', 'order' => 0]);
        $lesson->pages()->create([
            'title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0,
            'markdown' => "# Cím\n\nTartalom képpel.\n\n<figure><img src=\"/storage/course-media/1/images/abra.png\" alt=\"ábra\"></figure>",
            'narration_text' => 'Felolvasott szöveg.',
            'narration_audio_path' => 'narration/rt.mp3',
        ]);
        $question = $lesson->questions()->create(['text' => 'Kérdés?', 'order' => 0]);
        $question->answerOptions()->createMany([
            ['text' => 'Igen', 'is_correct' => true, 'order' => 0],
            ['text' => 'Nem', 'is_correct' => false, 'order' => 1],
        ]);

        $firstZip = app(CourseExporter::class)->export($course);
        $firstEntries = $this->zipEntries($firstZip);

        // "Üres adatbázis": az eredeti kurzus (és médiája) törlése után importálunk.
        $course->lessons()->get()->each(fn ($l) => $l->delete());
        $course->delete();

        $uploaded = new UploadedFile($firstZip, 'roundtrip-kurzus.zip', 'application/zip', null, true);
        $imported = app(CourseImporter::class)->import($uploaded);
        @unlink($firstZip);

        $secondZip = app(CourseExporter::class)->export($imported->fresh());
        $secondEntries = $this->zipEntries($secondZip);
        @unlink($secondZip);

        $this->assertSame(array_keys($firstEntries), array_keys($secondEntries), 'A két export fájllistájának azonosnak kell lennie.');

        foreach ($firstEntries as $name => $contents) {
            $this->assertSame($contents, $secondEntries[$name], "Eltérő tartalom a csomagban: {$name}");
        }
    }

    public function test_importer_still_accepts_legacy_html_page_files(): void
    {
        $extractPath = storage_path('app/tmp/legacy-course-test');
        @mkdir($extractPath.'/pages/lecke-1', recursive: true);

        file_put_contents($extractPath.'/pages/lecke-1/oldal-1.html', <<<'HTML'
        <!doctype html>
        <html lang="hu">
        <head><meta charset="utf-8"><title>Oldal 1</title></head>
        <body><h2>Régi formátum</h2><p>Ez egy HTML oldal.</p></body>
        </html>
        HTML);

        file_put_contents($extractPath.'/course.json', json_encode([
            'title' => 'Régi kurzus',
            'slug' => 'regi-kurzus',
            'lessons' => [[
                'title' => 'Lecke 1',
                'slug' => 'lecke-1',
                'order' => 0,
                'pages' => [[
                    'title' => 'Oldal 1',
                    'slug' => 'oldal-1',
                    'order' => 0,
                    'file' => 'pages/lecke-1/oldal-1.html',
                ]],
                'questions' => [],
            ]],
        ]));

        $zipPath = tempnam(sys_get_temp_dir(), 'legacy-course-');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        $zip->addFile($extractPath.'/course.json', 'regi-kurzus/course.json');
        $zip->addFile($extractPath.'/pages/lecke-1/oldal-1.html', 'regi-kurzus/pages/lecke-1/oldal-1.html');
        $zip->close();

        $uploaded = new UploadedFile($zipPath, 'legacy.zip', 'application/zip', null, true);
        $imported = app(CourseImporter::class)->import($uploaded);

        $imported->load('lessons.pages');
        $page = $imported->lessons->first()->pages->first();

        $this->assertStringContainsString('<h2>Régi formátum</h2>', $page->body_html);
        $this->assertStringContainsString('Ez egy HTML oldal.', $page->body_html);

        @unlink($zipPath);
        File::deleteDirectory($extractPath);
    }

    public function test_reimporting_a_zip_with_the_same_slug_resyncs_the_existing_course(): void
    {
        $zipPath = $this->makeZip([
            'kurzus/course.json' => json_encode([
                'title' => 'Verzió 1',
                'slug' => 'verzios-kurzus',
                'lessons' => [[
                    'title' => 'Lecke A', 'slug' => 'lecke-a', 'order' => 0,
                    'pages' => [
                        ['title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0, 'file' => 'pages/lecke-a/oldal-1.md'],
                        ['title' => 'Oldal 2', 'slug' => 'oldal-2', 'order' => 1, 'file' => 'pages/lecke-a/oldal-2.md'],
                    ],
                    'questions' => [],
                ]],
            ]),
            'kurzus/pages/lecke-a/oldal-1.md' => 'Első verzió szövege.',
            'kurzus/pages/lecke-a/oldal-2.md' => 'Ez a lecke később törlődik majd.',
        ]);

        $uploaded = new UploadedFile($zipPath, 'v1.zip', 'application/zip', null, true);
        $first = app(CourseImporter::class)->import($uploaded);
        @unlink($zipPath);

        $courseId = $first->id;
        $firstPage = $first->lessons()->where('slug', 'lecke-a')->first()->pages()->where('slug', 'oldal-1')->first();
        $firstPage->update(['narration_text' => 'Kézzel felvett narráció.']);

        $zipPath = $this->makeZip([
            'kurzus/course.json' => json_encode([
                'title' => 'Verzió 2',
                'slug' => 'verzios-kurzus',
                'lessons' => [[
                    'title' => 'Lecke A frissítve', 'slug' => 'lecke-a', 'order' => 0,
                    'pages' => [
                        ['title' => 'Oldal 1 frissítve', 'slug' => 'oldal-1', 'order' => 0, 'file' => 'pages/lecke-a/oldal-1.md'],
                    ],
                    'questions' => [],
                ]],
            ]),
            'kurzus/pages/lecke-a/oldal-1.md' => 'Frissített szöveg.',
        ]);

        $uploaded = new UploadedFile($zipPath, 'v2.zip', 'application/zip', null, true);
        $second = app(CourseImporter::class)->import($uploaded);
        @unlink($zipPath);

        $this->assertSame($courseId, $second->id, 'Ugyanazt a kurzust (azonos slug) kell frissítenie, nem újat létrehoznia.');
        $this->assertSame('Verzió 2', $second->fresh()->title);

        $second->load('lessons.pages');
        $this->assertCount(1, $second->lessons, 'A zipből kikerült "oldal-2" oldalnak (és a vele járó leckének, ha az is kikerül) törlődnie kell.');

        $lesson = $second->lessons->first();
        $this->assertSame('Lecke A frissítve', $lesson->title);
        $this->assertCount(1, $lesson->pages);

        $page = $lesson->pages->first();
        $this->assertStringContainsString('Frissített szöveg.', $page->markdown);
        $this->assertSame('Kézzel felvett narráció.', $page->narration_text, 'A zipben nem szereplő narrációt nem szabad törölni frissítéskor.');
    }

    public function test_import_accepts_a_zip_of_md_files_without_course_json(): void
    {
        $zipPath = $this->makeZip([
            'folder-import/lecke-egy/01-eleje.md' => '# Eleje',
            'folder-import/lecke-egy/02-folytatas.md' => '# Folytatás',
            'folder-import/lecke-ketto/01-mas-oldal.md' => '# Más lecke oldala',
        ]);

        $uploaded = new UploadedFile($zipPath, 'folder-import.zip', 'application/zip', null, true);
        $course = app(CourseImporter::class)->import($uploaded, null);
        @unlink($zipPath);

        $course->load('lessons.pages');
        $this->assertCount(2, $course->lessons);

        $lessonOne = $course->lessons->firstWhere('slug', 'lecke-egy');
        $this->assertNotNull($lessonOne);
        $this->assertCount(2, $lessonOne->pages);

        $lessonTwo = $course->lessons->firstWhere('slug', 'lecke-ketto');
        $this->assertNotNull($lessonTwo);
        $this->assertCount(1, $lessonTwo->pages);
    }

    public function test_import_markdown_files_creates_one_lesson_per_file(): void
    {
        $path1 = tempnam(sys_get_temp_dir(), 'md-').'.md';
        $path2 = tempnam(sys_get_temp_dir(), 'md-').'.md';
        file_put_contents($path1, '# Első fájl');
        file_put_contents($path2, '# Második fájl');

        $files = [
            new UploadedFile($path1, 'elso-oldal.md', 'text/markdown', null, true),
            new UploadedFile($path2, 'masodik-oldal.md', 'text/markdown', null, true),
        ];

        $course = app(CourseImporter::class)->importMarkdownFiles($files);

        $course->load('lessons.pages');
        $this->assertCount(2, $course->lessons);
        $this->assertTrue($course->lessons->every(fn ($lesson) => $lesson->pages->count() === 1));

        @unlink($path1);
        @unlink($path2);
    }

    public function test_append_markdown_pages_adds_to_lesson_without_deleting(): void
    {
        $course = Course::create(['title' => 'Bővített', 'slug' => 'bovitett', 'order' => 0]);
        $lesson = $course->lessons()->create(['title' => 'Lecke', 'slug' => 'lecke', 'order' => 0]);
        $lesson->pages()->create(['title' => 'Meglévő', 'slug' => 'meglevo', 'order' => 0, 'markdown' => 'Régi tartalom.']);

        $path = tempnam(sys_get_temp_dir(), 'md-').'.md';
        file_put_contents($path, '# Új oldal tartalma');

        $created = app(CourseImporter::class)->appendMarkdownPages($lesson, [
            new UploadedFile($path, 'uj-oldal.md', 'text/markdown', null, true),
        ]);
        @unlink($path);

        $this->assertSame(1, $created);

        $lesson->refresh()->load('pages');
        $this->assertCount(2, $lesson->pages);
        $this->assertSame('Régi tartalom.', $lesson->pages->firstWhere('slug', 'meglevo')->markdown);

        $newPage = $lesson->pages->firstWhere('slug', 'uj-oldal');
        $this->assertNotNull($newPage);
        $this->assertSame(1, $newPage->order);
        $this->assertStringContainsString('Új oldal tartalma', $newPage->markdown);
    }

    public function test_importing_a_course_enrolls_the_importer(): void
    {
        $importer = User::factory()->create();

        $zipPath = $this->makeZip([
            'kurzus/course.json' => json_encode([
                'title' => 'Beiratkozás teszt', 'slug' => 'beiratkozas-teszt',
                'lessons' => [[
                    'title' => 'Lecke 1', 'slug' => 'lecke-1', 'order' => 0,
                    'pages' => [['title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0, 'file' => 'pages/lecke-1/oldal-1.md']],
                    'questions' => [],
                ]],
            ]),
            'kurzus/pages/lecke-1/oldal-1.md' => 'Tartalom.',
        ]);

        $uploaded = new UploadedFile($zipPath, 'beiratkozas.zip', 'application/zip', null, true);
        $course = app(CourseImporter::class)->import($uploaded, $importer->id);
        @unlink($zipPath);

        $this->assertTrue($course->isEnrolledBy($importer));

        $otherImporter = User::factory()->create();

        $zipPath = $this->makeZip([
            'kurzus/course.json' => json_encode([
                'title' => 'Beiratkozás teszt frissítve', 'slug' => 'beiratkozas-teszt',
                'lessons' => [[
                    'title' => 'Lecke 1', 'slug' => 'lecke-1', 'order' => 0,
                    'pages' => [['title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0, 'file' => 'pages/lecke-1/oldal-1.md']],
                    'questions' => [],
                ]],
            ]),
            'kurzus/pages/lecke-1/oldal-1.md' => 'Frissített tartalom.',
        ]);

        $uploaded = new UploadedFile($zipPath, 'beiratkozas-v2.zip', 'application/zip', null, true);
        $resynced = app(CourseImporter::class)->import($uploaded, $otherImporter->id);
        @unlink($zipPath);

        $this->assertSame($course->id, $resynced->id);
        $this->assertTrue($resynced->isEnrolledBy($importer), 'Az eredeti importáló ne veszítse el a beiratkozást egy újraimportálásnál.');
        $this->assertTrue($resynced->isEnrolledBy($otherImporter), 'Aki újraimportálja a kurzust, az is jelenjen meg a saját kurzusai között.');
    }

    public function test_import_manifest_file_alone_creates_an_empty_course_skeleton(): void
    {
        $manifestPath = tempnam(sys_get_temp_dir(), 'manifest-').'.json';
        file_put_contents($manifestPath, json_encode([
            'title' => 'Vázlat kurzus',
            'slug' => 'vazlat-kurzus',
            'description' => 'Csak a szerkezet.',
            'is_published' => false,
            'order' => 0,
            'lessons' => [[
                'title' => 'Lecke 1', 'slug' => 'lecke-1', 'order' => 0,
                'pages' => [
                    ['title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0, 'file' => 'pages/lecke-1/oldal-1.md'],
                    ['title' => 'Oldal 2', 'slug' => 'oldal-2', 'order' => 1, 'file' => 'pages/lecke-1/oldal-2.md'],
                ],
                'questions' => [
                    ['text' => 'Kérdés?', 'order' => 0, 'answer_options' => [
                        ['text' => 'Igen', 'is_correct' => true, 'order' => 0],
                        ['text' => 'Nem', 'is_correct' => false, 'order' => 1],
                    ]],
                ],
            ]],
        ]));

        $uploaded = new UploadedFile($manifestPath, 'course.json', 'application/json', null, true);
        $course = app(CourseImporter::class)->importManifestFile($uploaded);
        @unlink($manifestPath);

        $this->assertSame('vazlat-kurzus', $course->slug);

        $course->load('lessons.pages', 'lessons.questions.answerOptions');
        $this->assertCount(1, $course->lessons);

        $lesson = $course->lessons->first();
        $this->assertCount(2, $lesson->pages);
        $this->assertCount(1, $lesson->questions);
        $this->assertCount(2, $lesson->questions->first()->answerOptions);

        foreach ($lesson->pages as $page) {
            $this->assertSame('', $page->markdown);
        }
    }

    public function test_reimporting_a_manifest_file_resyncs_the_existing_course_skeleton(): void
    {
        $first = $this->importManifestOnly([
            'title' => 'V1', 'slug' => 'skeleton-kurzus',
            'lessons' => [[
                'title' => 'Lecke A', 'slug' => 'lecke-a', 'order' => 0,
                'pages' => [
                    ['title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0, 'file' => 'o1.md'],
                    ['title' => 'Oldal 2', 'slug' => 'oldal-2', 'order' => 1, 'file' => 'o2.md'],
                ],
                'questions' => [],
            ]],
        ]);

        $second = $this->importManifestOnly([
            'title' => 'V2', 'slug' => 'skeleton-kurzus',
            'lessons' => [[
                'title' => 'Lecke A', 'slug' => 'lecke-a', 'order' => 0,
                'pages' => [
                    ['title' => 'Oldal 1', 'slug' => 'oldal-1', 'order' => 0, 'file' => 'o1.md'],
                ],
                'questions' => [],
            ]],
        ]);

        $this->assertSame($first->id, $second->id);
        $second->load('lessons.pages');
        $this->assertCount(1, $second->lessons->first()->pages, 'A manifestből kikerült oldalnak törlődnie kell frissítéskor is.');
    }

    private function importManifestOnly(array $manifest): Course
    {
        $path = tempnam(sys_get_temp_dir(), 'manifest-').'.json';
        file_put_contents($path, json_encode($manifest));

        $uploaded = new UploadedFile($path, 'course.json', 'application/json', null, true);
        $course = app(CourseImporter::class)->importManifestFile($uploaded);
        @unlink($path);

        return $course;
    }

    /**
     * @return array<string, string> entry name => contents, name-sorted
     */
    private function zipEntries(string $zipPath): array
    {
        $zip = new ZipArchive;
        $zip->open($zipPath);

        $entries = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $entries[$name] = $zip->getFromIndex($i);
        }

        $zip->close();
        ksort($entries);

        return $entries;
    }

    /**
     * @param  array<string, string>  $entries  relative zip path => file contents
     */
    private function makeZip(array $entries): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'course-test-');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::OVERWRITE);

        foreach ($entries as $path => $contents) {
            $zip->addFromString($path, $contents);
        }

        $zip->close();

        return $zipPath;
    }
}
