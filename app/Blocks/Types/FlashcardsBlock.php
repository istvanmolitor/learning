<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;

class FlashcardsBlock extends BlockType
{
    public static function key(): string
    {
        return 'flashcards';
    }

    public function rules(): array
    {
        return [
            'cards' => ['required', 'array', 'min:1'],
            'cards.*.front' => ['required', 'string', 'max:300'],
            'cards.*.back' => ['required', 'string', 'max:500'],
        ];
    }

    public function render(array $data): string
    {
        $html = '<dl class="content-flashcards">';

        foreach ($data['cards'] ?? [] as $card) {
            $html .= '<div><dt>'.e($card['front'] ?? '').'</dt><dd>'.e($card['back'] ?? '').'</dd></div>';
        }

        return $html.'</dl>';
    }

    public function meta(): array
    {
        return ['label' => 'Fogalomkártyák', 'icon' => 'layers', 'family' => 'widget'];
    }
}
