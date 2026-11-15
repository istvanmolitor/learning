<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;

class HeadingBlock extends BlockType
{
    public static function key(): string
    {
        return 'heading';
    }

    public function rules(): array
    {
        return [
            'level' => ['required', 'integer', 'between:2,4'],
            'text' => ['required', 'string', 'max:200'],
        ];
    }

    public function render(array $data): string
    {
        $level = max(2, min(4, (int) ($data['level'] ?? 2)));

        return "<h{$level}>".e($data['text'] ?? '')."</h{$level}>";
    }

    public function meta(): array
    {
        return ['label' => 'Szakaszcím', 'icon' => 'heading', 'family' => 'structure'];
    }
}
