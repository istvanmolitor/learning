<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;

class StepsBlock extends BlockType
{
    public static function key(): string
    {
        return 'steps';
    }

    public function rules(): array
    {
        return [
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.title' => ['required', 'string', 'max:200'],
            'steps.*.html' => ['required', 'string'],
        ];
    }

    public function render(array $data): string
    {
        $html = '<ol class="content-steps">';

        foreach ($data['steps'] ?? [] as $step) {
            $html .= '<li><strong>'.e($step['title'] ?? '').'</strong>'.($step['html'] ?? '').'</li>';
        }

        return $html.'</ol>';
    }

    public function meta(): array
    {
        return ['label' => 'Léptethető folyamat', 'icon' => 'list-ordered', 'family' => 'widget'];
    }
}
