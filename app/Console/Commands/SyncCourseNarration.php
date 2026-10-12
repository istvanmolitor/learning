<?php

namespace App\Console\Commands;

use App\Services\CourseTransfer\CourseImporter;
use Illuminate\Console\Command;

class SyncCourseNarration extends Command
{
    protected $signature = 'course:sync-narration {path : A kurzus forrásmappája, ahol a course.json található}';

    protected $description = 'Felolvasandó szöveg (és meglévő mp3) beolvasása a forrásmappából egy már importált kurzus meglévő oldalaihoz';

    public function handle(CourseImporter $importer): int
    {
        $updated = $importer->syncNarration(rtrim($this->argument('path'), '/'));

        $this->info("{$updated} oldal narrációja frissült.");

        return self::SUCCESS;
    }
}
