<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;

class AccordionBlock extends BlockType
{
    public static function key(): string
    {
        return 'accordion';
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:200'],
            'items.*.html' => ['required', 'string'],
        ];
    }

    public function render(array $data): string
    {
        $html = '<div class="content-accordion">';

        foreach ($data['items'] ?? [] as $item) {
            $html .= '<details><summary>'.e($item['title'] ?? '').'</summary>'.($item['html'] ?? '').'</details>';
        }

        return $html.'</div>';
    }

    public function meta(): array
    {
        return ['label' => 'Lenyíló szakaszok', 'icon' => 'chevrons-down-up', 'family' => 'structure'];
    }
}
