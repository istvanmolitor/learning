<?php

/**
 * Valid values for named block display modes. These are *not* style
 * definitions — no colors, no CSS — only the jegyzék of allowed keys used
 * for validation and for building the editor's insert palette. The actual
 * color/icon/typography for each key lives in the frontend design layer
 * (resources/js/lib/blocks/calloutVariants.ts) so that re-theming never
 * touches stored content.
 */
return [

    /*
     * Callout block variants: key => [label, default English label, icon].
     * Add a new box type by adding an entry here (plus the matching frontend
     * token) — no new PHP class needed, CalloutBlock handles every variant.
     */
    'callout_variants' => [
        'definicio' => ['label' => 'Definíció', 'label_en' => 'definition', 'icon' => 'book-open'],
        'tetel' => ['label' => 'Tétel', 'label_en' => 'theorem', 'icon' => 'star'],
        'keplet' => ['label' => 'Kulcsképlet', 'label_en' => 'key formula', 'icon' => 'key'],
        'pelda' => ['label' => 'Példa', 'label_en' => 'example', 'icon' => 'pencil'],
        'szemleletesen' => ['label' => 'Szemléletesen', 'label_en' => 'intuition', 'icon' => 'eye'],
        'vigyazz' => ['label' => 'Vigyázz!', 'label_en' => 'watch out', 'icon' => 'alert-triangle'],
        'roviden' => ['label' => 'Röviden', 'label_en' => 'in short', 'icon' => 'arrow-right'],
        'kiegeszites' => ['label' => 'Kiegészítés', 'label_en' => 'extra', 'icon' => 'sparkles'],
        'tipp' => ['label' => 'Tipp', 'label_en' => 'tip', 'icon' => 'lightbulb'],
        'cel' => ['label' => 'Miről szól?', 'label_en' => 'goal', 'icon' => 'target'],
    ],

    /*
     * Allowed `video` block sources. "file" means an uploaded video stored
     * like any other media path; the others are embed providers identified
     * by their video ID only (never a raw iframe/URL).
     */
    'video_sources' => ['youtube', 'vimeo', 'file'],

    /*
     * Allowed named display modes for other block types.
     */
    'image_sizes' => ['small', 'normal', 'wide'],
    'gallery_layouts' => ['grid', 'carousel'],
    'divider_variants' => ['line', 'space'],
    'chart_variants' => ['bar', 'line', 'pie'],

];
