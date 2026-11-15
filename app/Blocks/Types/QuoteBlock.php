<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;

class QuoteBlock extends BlockType
{
    public static function key(): string
    {
        return 'quote';
    }

    public function rules(): array
    {
        return [
            'html' => ['required', 'string'],
            'source' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function render(array $data): string
    {
        $html = '<blockquote class="content-quote">'.($data['html'] ?? '');

        if (! empty($data['source'])) {
            $html .= '<cite>— '.e($data['source']).'</cite>';
        }

        return $html.'</blockquote>';
    }

    public function meta(): array
    {
        return ['label' => 'Idézet', 'icon' => 'quote', 'family' => 'structure'];
    }
}
