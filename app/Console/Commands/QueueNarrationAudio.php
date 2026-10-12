<?php

namespace App\Console\Commands;

use App\Jobs\GenerateNarrationAudio;
use App\Models\Page;
use Illuminate\Console\Command;

class QueueNarrationAudio extends Command
{
    protected $signature = 'narration:queue-audio';

    protected $description = 'Queue-ba rakja azokat az oldalakat, amelyeknél van felolvasandó szöveg, de még nincs hozzá legenerált mp3';

    public function handle(): int
    {
        $pages = Page::whereNotNull('narration_text')
            ->where('narration_text', '!=', '')
            ->whereNull('narration_audio_path')
            ->get();

        foreach ($pages as $page) {
            GenerateNarrationAudio::dispatch($page);
        }

        $this->info("{$pages->count()} oldal felolvasásának generálása bekerült a queue-ba.");

        return self::SUCCESS;
    }
}
