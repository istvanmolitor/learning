<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;

class TabsBlock extends BlockType
{
    public static function key(): string
    {
        return 'tabs';
    }

    public function rules(): array
    {
        return [
            'tabs' => ['required', 'array', 'min:1'],
            'tabs.*.title' => ['required', 'string', 'max:200'],
            'tabs.*.html' => ['required', 'string'],
        ];
    }

    public function render(array $data): string
    {
        // Degraded render has no interactivity, so every tab is shown as its
        // own labelled section rather than hidden behind a tab strip.
        $html = '<div class="content-tabs">';

        foreach ($data['tabs'] ?? [] as $tab) {
            $html .= '<section><h4>'.e($tab['title'] ?? '').'</h4>'.($tab['html'] ?? '').'</section>';
        }

        return $html.'</div>';
    }

    public function meta(): array
    {
        return ['label' => 'Fülek', 'icon' => 'panels-top-left', 'family' => 'structure'];
    }
}
