<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use Illuminate\Validation\Rule;

/**
 * One block type, many "boxes" (definíció, tétel, vigyázz, …). Which colors
 * belong to which variant is a *view* decision: the inline palette below is
 * this class's own degraded-HTML theme (for PDF/PPTX/no-JS fallback) and is
 * free to change without touching stored data; the live web page instead
 * uses the frontend's own token map (resources/js/lib/blocks/calloutVariants.ts).
 */
class CalloutBlock extends BlockType
{
    private const COLORS = [
        'definicio' => '#2563eb',
        'tetel' => '#059669',
        'keplet' => '#d97706',
        'pelda' => '#ea580c',
        'szemleletesen' => '#0d9488',
        'vigyazz' => '#dc2626',
        'roviden' => '#64748b',
        'kiegeszites' => '#7c3aed',
        'tipp' => '#0d9488',
        'cel' => '#4f46e5',
    ];

    public static function key(): string
    {
        return 'callout';
    }

    public function rules(): array
    {
        return [
            'variant' => ['required', 'string', Rule::in(array_keys(config('blocks.callout_variants')))],
            'title' => ['nullable', 'string', 'max:200'],
            'title_en' => ['nullable', 'string', 'max:200'],
            'html' => ['required', 'string'],
        ];
    }

    public function render(array $data): string
    {
        $variant = $data['variant'] ?? 'definicio';
        $color = self::COLORS[$variant] ?? '#64748b';
        $label = config("blocks.callout_variants.{$variant}.label", $variant);

        $heading = e($label);

        if (! empty($data['title'])) {
            $heading .= ' · '.e($data['title']);
        }

        if (! empty($data['title_en'])) {
            $heading .= ' <em>('.e($data['title_en']).')</em>';
        }

        return <<<HTML
        <div class="content-callout" data-variant="{$variant}" style="border:1.5px solid {$color};border-radius:10px;margin:1rem 0;overflow:hidden">
            <div style="background:{$color};color:#fff;font-size:.75rem;font-weight:700;padding:.2rem .7rem;display:inline-block;border-radius:0 0 8px 0">{$heading}</div>
            <div style="padding:.25rem 1rem .75rem">{$data['html']}</div>
        </div>
        HTML;
    }

    public function meta(): array
    {
        return ['label' => 'Kiemelő doboz', 'icon' => 'square-asterisk', 'family' => 'callout'];
    }
}
