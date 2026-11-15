<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;

class TableBlock extends BlockType
{
    public static function key(): string
    {
        return 'table';
    }

    public function rules(): array
    {
        return [
            'headers' => ['required', 'array', 'min:1'],
            'headers.*' => ['nullable', 'string', 'max:200'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*' => ['array'],
            'rows.*.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function render(array $data): string
    {
        $headers = $data['headers'] ?? [];
        $rows = $data['rows'] ?? [];

        $html = '<table class="content-table"><thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th>'.e($header).'</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';

            foreach ($row as $cell) {
                $html .= '<td>'.e($cell).'</td>';
            }

            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }

    public function meta(): array
    {
        return ['label' => 'Táblázat', 'icon' => 'table', 'family' => 'structure'];
    }
}
