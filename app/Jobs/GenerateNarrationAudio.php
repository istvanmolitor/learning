<?php

namespace App\Jobs;

use App\Models\Page;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateNarrationAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public function __construct(public Page $page) {}

    public function handle(): void
    {
        if ($this->page->narration_audio_path || ! $this->page->narration_text) {
            return;
        }

        $workDir = storage_path('app/tmp/narration-'.Str::random(12));
        File::ensureDirectoryExists($workDir);

        try {
            $txtPath = $workDir.'/narration.txt';
            $mp3Path = $workDir.'/narration.mp3';

            File::put($txtPath, $this->page->narration_text);

            $result = Process::path(base_path())
                ->timeout($this->timeout - 10)
                ->run(['python3', 'scripts/txt_to_mp3.py', $txtPath, '-o', $mp3Path]);

            if (! $result->successful() || ! File::exists($mp3Path)) {
                throw new RuntimeException(
                    "A felolvasás generálása sikertelen a {$this->page->id} azonosítójú oldalhoz: ".$result->errorOutput()
                );
            }

            $storedPath = "course-media/{$this->page->lesson->course_id}/narration/".Str::random(12).'.mp3';
            Storage::disk('public')->put($storedPath, File::get($mp3Path));

            $this->page->update(['narration_audio_path' => $storedPath]);
        } finally {
            File::deleteDirectory($workDir);
        }
    }
}
