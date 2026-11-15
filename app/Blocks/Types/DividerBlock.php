<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use Illuminate\Validation\Rule;

class DividerBlock extends BlockType
{
    public static function key(): string
    {
        return 'divider';
    }

    public function rules(): array
    {
        return [
            'variant' => ['required', 'string', Rule::in(config('blocks.divider_variants'))],
        ];
    }

    public function render(array $data): string
    {
        return ($data['variant'] ?? 'line') === 'space'
            ? '<div class="content-divider content-divider--space"></div>'
            : '<hr class="content-divider">';
    }

    public function meta(): array
    {
        return ['label' => 'Elválasztó', 'icon' => 'minus', 'family' => 'structure'];
    }
}
