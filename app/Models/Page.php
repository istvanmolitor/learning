<?php

namespace App\Models;

use App\Concerns\Completable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['lesson_id', 'title', 'slug', 'order', 'markdown', 'narration_text', 'narration_audio_path'])]
class Page extends Model
{
    use Completable;

    protected static function booted(): void
    {
        // body_html is a render cache: the markdown column is the single source
        // of truth, so it is never written directly.
        static::saving(function (Page $page) {
            if ($page->isDirty('markdown')) {
                $page->body_html = Str::markdown($page->markdown ?? '');
            }
        });
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
