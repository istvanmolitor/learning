<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;

/**
 * Prose. `html` is the formatted-text output of RichTextEditor.vue (a fixed
 * tiptap feature set: paragraphs, headings, lists, blockquotes, bold,
 * italic, underline, strike, links) — never arbitrary HTML from the admin.
 */
class TextBlock extends BlockType
{
    public static function key(): string
    {
        return 'text';
    }

    public function rules(): array
    {
        return [
            'html' => ['required', 'string'],
        ];
    }

    public function render(array $data): string
    {
        return '<div class="content-text">'.($data['html'] ?? '').'</div>';
    }

    public function meta(): array
    {
        return ['label' => 'Szöveg', 'icon' => 'pilcrow', 'family' => 'structure'];
    }
}
