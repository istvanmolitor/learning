<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The degraded render() is a plain data table — the live page instead draws
 * an animated SVG chart (resources/js/components/blocks/ChartBlock.vue),
 * there is no server-side charting dependency.
 */
class ChartBlock extends BlockType
{
    public static function key(): string
    {
        return 'chart';
    }

    public function rules(): array
    {
        return [
            'variant' => ['required', 'string', Rule::in(config('blocks.chart_variants'))],
            'title' => ['nullable', 'string', 'max:200'],
            'labels' => ['required', 'array', 'min:1'],
            'labels.*' => ['required', 'string', 'max:100'],
            'series' => ['required', 'array', 'min:1'],
            'series.*.name' => ['required', 'string', 'max:100'],
            'series.*.data' => ['required', 'array'],
            'series.*.data.*' => ['numeric'],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Chart needs a cross-field check (each series' data length must match
     * labels' length) that plain Laravel rules can't express cleanly, so it
     * overrides validation via an after-hook instead of a rules()-only check.
     */
    public function withValidator(Validator $validator, array $data): void
    {
        $validator->after(function (Validator $validator) use ($data) {
            $labelCount = count($data['labels'] ?? []);

            foreach ($data['series'] ?? [] as $index => $series) {
                if (count($series['data'] ?? []) !== $labelCount) {
                    $validator->errors()->add("series.{$index}.data", 'A sorozat adatpontjainak száma nem egyezik a címkék számával.');
                }
            }
        });
    }

    public function render(array $data): string
    {
        $html = '<table class="content-chart" data-variant="'.e($data['variant'] ?? 'bar').'">';

        if (! empty($data['title'])) {
            $html .= '<caption>'.e($data['title']).'</caption>';
        }

        $html .= '<thead><tr><th></th>';

        foreach ($data['labels'] ?? [] as $label) {
            $html .= '<th>'.e($label).'</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($data['series'] ?? [] as $series) {
            $html .= '<tr><th>'.e($series['name'] ?? '').'</th>';

            foreach ($series['data'] ?? [] as $value) {
                $html .= '<td>'.e((string) $value).'</td>';
            }

            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }

    public function meta(): array
    {
        return ['label' => 'Diagram', 'icon' => 'bar-chart-3', 'family' => 'widget'];
    }
}
