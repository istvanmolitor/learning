<?php

namespace App\Models;

use App\Concerns\Completable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['lesson_id', 'title', 'slug', 'order', 'markdown', 'narration_text', 'narration_audio_path'])]
class Page extends Model
{
    use Completable;

    protected static function booted(): void
    {
        // body_html is a render cache. For hand-authored Markdown it is derived
        // from the markdown column; PageBlockSync (the blocks editor) instead
        // writes both columns explicitly with the same already-rendered HTML,
        // so the dirty-body_html guard here stops that from being overwritten.
        static::saving(function (Page $page) {
            if ($page->isDirty('markdown') && ! $page->isDirty('body_html')) {
                $page->body_html = Str::markdown($page->markdown ?? '');
            }
        });
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class)->orderBy('order');
    }
}
