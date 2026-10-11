<?php

namespace App\Concerns;

use App\Models\Completion;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Completable
{
    public function completions(): MorphMany
    {
        return $this->morphMany(Completion::class, 'completable');
    }

    public function isCompletedBy(User $user): bool
    {
        return $this->completions()->where('user_id', $user->id)->exists();
    }

    public function markCompletedBy(User $user): Completion
    {
        return $this->completions()->updateOrCreate(
            ['user_id' => $user->id],
            ['completed_at' => now()],
        );
    }
}
