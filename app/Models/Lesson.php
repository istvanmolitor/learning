<?php

namespace App\Models;

use App\Concerns\Completable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'title', 'slug', 'order'])]
class Lesson extends Model
{
    use Completable;

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class)->orderBy('order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }
}
