import { defineAsyncComponent, type Component } from 'vue';
import type { BlockType } from './types';

// Every viewer loads lazily except the few used on almost every page — no
// bundle-size cost for courses that never use, say, flashcards or charts.
export const VIEWER_REGISTRY: Record<BlockType, Component> = {
    text: defineAsyncComponent(
        () => import('@/components/blocks/viewers/TextBlock.vue'),
    ),
    heading: defineAsyncComponent(
        () => import('@/components/blocks/viewers/HeadingBlock.vue'),
    ),
    quote: defineAsyncComponent(
        () => import('@/components/blocks/viewers/QuoteBlock.vue'),
    ),
    divider: defineAsyncComponent(
        () => import('@/components/blocks/viewers/DividerBlock.vue'),
    ),
    table: defineAsyncComponent(
        () => import('@/components/blocks/viewers/TableBlock.vue'),
    ),
    accordion: defineAsyncComponent(
        () => import('@/components/blocks/viewers/AccordionBlock.vue'),
    ),
    tabs: defineAsyncComponent(
        () => import('@/components/blocks/viewers/TabsBlock.vue'),
    ),
    callout: defineAsyncComponent(
        () => import('@/components/blocks/viewers/CalloutBlock.vue'),
    ),
    image: defineAsyncComponent(
        () => import('@/components/blocks/viewers/ImageBlock.vue'),
    ),
    gallery: defineAsyncComponent(
        () => import('@/components/blocks/viewers/GalleryBlock.vue'),
    ),
    video: defineAsyncComponent(
        () => import('@/components/blocks/viewers/VideoBlock.vue'),
    ),
    audio: defineAsyncComponent(
        () => import('@/components/blocks/viewers/AudioBlock.vue'),
    ),
    file: defineAsyncComponent(
        () => import('@/components/blocks/viewers/FileBlock.vue'),
    ),
    chart: defineAsyncComponent(
        () => import('@/components/blocks/viewers/ChartBlock.vue'),
    ),
    flashcards: defineAsyncComponent(
        () => import('@/components/blocks/viewers/FlashcardsBlock.vue'),
    ),
    steps: defineAsyncComponent(
        () => import('@/components/blocks/viewers/StepsBlock.vue'),
    ),
};

// Minimal-but-valid starting payload per type — satisfies each BlockType's
// server-side rules() so a freshly inserted block never 422s before the
// admin has typed anything.
export const DEFAULT_BLOCK_DATA: Record<BlockType, Record<string, unknown>> = {
    text: { html: '<p>Új szöveg…</p>' },
    heading: { level: 2, text: 'Új szakaszcím' },
    quote: { html: '<p>Idézet szövege…</p>' },
    divider: { variant: 'line' },
    table: { headers: ['Fejléc 1', 'Fejléc 2'], rows: [['', '']] },
    accordion: { items: [{ title: 'Szakasz', html: '<p>…</p>' }] },
    tabs: { tabs: [{ title: 'Fül', html: '<p>…</p>' }] },
    callout: { variant: 'definicio', title: '', title_en: '', html: '<p>…</p>' },
    image: { path: '', alt: '', caption: '', size: 'normal' },
    gallery: { layout: 'grid', items: [{ path: '', alt: '', caption: '' }] },
    video: { source: 'youtube', video_id: '', title: '' },
    audio: { path: '', title: '' },
    file: { path: '', label: 'Letöltés' },
    chart: {
        variant: 'bar',
        title: '',
        labels: ['A', 'B'],
        series: [{ name: 'Sorozat', data: [0, 0] }],
    },
    flashcards: { cards: [{ front: '', back: '' }] },
    steps: { steps: [{ title: 'Lépés', html: '<p>…</p>' }] },
};

export const BLOCK_PALETTE: {
    type: BlockType;
    label: string;
    icon: string;
    family: 'structure' | 'media' | 'callout' | 'widget';
}[] = [
    { type: 'text', label: 'Szöveg', icon: '¶', family: 'structure' },
    { type: 'heading', label: 'Szakaszcím', icon: 'H', family: 'structure' },
    { type: 'quote', label: 'Idézet', icon: '❝', family: 'structure' },
    { type: 'table', label: 'Táblázat', icon: '▦', family: 'structure' },
    { type: 'divider', label: 'Elválasztó', icon: '—', family: 'structure' },
    { type: 'accordion', label: 'Lenyíló szakaszok', icon: '▾', family: 'structure' },
    { type: 'tabs', label: 'Fülek', icon: '▤', family: 'structure' },
    { type: 'callout', label: 'Kiemelő doboz', icon: '✦', family: 'callout' },
    { type: 'image', label: 'Kép', icon: '🖼', family: 'media' },
    { type: 'gallery', label: 'Galéria', icon: '🖼🖼', family: 'media' },
    { type: 'video', label: 'Videó', icon: '▶', family: 'media' },
    { type: 'audio', label: 'Hang', icon: '🔊', family: 'media' },
    { type: 'file', label: 'Letölthető fájl', icon: '📎', family: 'media' },
    { type: 'chart', label: 'Diagram', icon: '📊', family: 'widget' },
    { type: 'flashcards', label: 'Fogalomkártyák', icon: '🃏', family: 'widget' },
    { type: 'steps', label: 'Léptethető folyamat', icon: '➜', family: 'widget' },
];
