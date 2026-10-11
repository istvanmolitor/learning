<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['lesson_id', 'text', 'order'])]
class Question extends Model
{
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function answerOptions(): HasMany
    {
        return $this->hasMany(AnswerOption::class)->orderBy('order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class);
    }
}
