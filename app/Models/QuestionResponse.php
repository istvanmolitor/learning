<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'question_id', 'answer_option_id', 'answered_at'])]
class QuestionResponse extends Model
{
    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function answerOption(): BelongsTo
    {
        return $this->belongsTo(AnswerOption::class);
    }

    public function isCorrect(): bool
    {
        return $this->answerOption->is_correct;
    }
}
