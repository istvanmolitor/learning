<?php

namespace App\Console\Commands;

use App\Services\CourseTransfer\CourseImporter;
use App\Services\CourseTransfer\ImportPlanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ValidateCoursePackage extends Command
{
    protected $signature = 'course:validate {path : A kurzuscsomag zip, course.json vagy kicsomagolt mappa útvonala}';

    protected $description = 'Kurzuscsomag ellenőrzése import nélkül (dry-run): szerkezet, hiányzó fájlok, túl hosszú oldalak.';

    public function handle(CourseImporter $importer, ImportPlanner $planner): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("Nem található: {$path}");

            return self::FAILURE;
        }

        $tmpDir = null;

        try {
            if (is_dir($path)) {
                $packageDir = $path;
            } elseif (Str::endsWith(strtolower($path), '.json')) {
                $tmpDir = storage_path('app/tmp/course-validate-'.Str::random(12));
                File::makeDirectory($tmpDir, recursive: true);
                File::copy($path, $tmpDir.'/course.json');
                $packageDir = $tmpDir;
            } else {
                $tmpDir = storage_path('app/tmp/course-validate-'.Str::random(12));
                $importer->extractZip($path, $tmpDir);
                $packageDir = $tmpDir;
            }

            $plan = $planner->plan($packageDir, pathinfo($path, PATHINFO_FILENAME));
        } catch (Throwable $exception) {
            $this->error('A csomag nem dolgozható fel: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            if ($tmpDir !== null) {
                File::deleteDirectory($tmpDir);
            }
        }

        $course = $plan['course'];

        $this->info("Kurzus: {$course['title']} (slug: {$course['slug']})");
        $this->line("Formátum: {$plan['format']}");
        $this->line("Leckék: {$course['lesson_count']}, oldalak: {$course['page_count']}, kérdések: {$course['question_count']}");

        if ($plan['existing_course'] !== null) {
            $this->warn("Figyelem: már létezik kurzus ezzel a slug-gal (#{$plan['existing_course']['id']}: {$plan['existing_course']['title']}) — importkor frissülne.");

            $changes = $plan['changes'];
            $deletes = [...$changes['lessons']['delete'], ...$changes['pages']['delete']];

            foreach ($deletes as $deleted) {
                $this->warn("  Törlődne: {$deleted}");
            }
        }

        foreach ($plan['long_pages'] as $longPage) {
            $hint = $longPage['splittable'] ? 'importkor bontható ## címsorok mentén' : 'nem bontható automatikusan';
            $this->warn("Túl hosszú oldal: {$longPage['label']} ({$hint})");
        }

        foreach ($plan['warnings'] as $warning) {
            $this->error($warning);
        }

        if ($plan['warnings'] !== []) {
            $this->newLine();
            $this->error('A csomag hibákat tartalmaz.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('A csomag érvényes, importálható.');

        return self::SUCCESS;
    }
}
