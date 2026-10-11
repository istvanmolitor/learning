<?php

namespace App\Models;

use App\Enums\ContentBlockType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['page_id', 'type', 'data', 'order'])]
class ContentBlock extends Model
{
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    protected function casts(): array
    {
        return [
            'type' => ContentBlockType::class,
            'data' => 'array',
        ];
    }
}
