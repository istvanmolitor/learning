// Mirrors the payload shapes app/Blocks/Types/*.php validate server-side.
// A block never carries color/CSS — only type, data, and at most a named
// display mode (variant/size/layout) — the look lives entirely in
// calloutVariants.ts and the viewer components themselves.

export type CalloutVariant =
    | 'definicio'
    | 'tetel'
    | 'keplet'
    | 'pelda'
    | 'szemleletesen'
    | 'vigyazz'
    | 'roviden'
    | 'kiegeszites'
    | 'tipp'
    | 'cel';

export interface TextData {
    html: string;
}

export interface HeadingData {
    level: 2 | 3 | 4;
    text: string;
}

export interface QuoteData {
    html: string;
    source?: string | null;
}

export interface DividerData {
    variant: 'line' | 'space';
}

export interface TableData {
    headers: string[];
    rows: string[][];
}

export interface CalloutData {
    variant: CalloutVariant;
    title?: string | null;
    title_en?: string | null;
    html: string;
}

export interface ImageData {
    path: string;
    alt?: string | null;
    caption?: string | null;
    size?: 'small' | 'normal' | 'wide';
}

export interface GalleryItem {
    path: string;
    alt?: string | null;
    caption?: string | null;
}

export interface GalleryData {
    layout: 'grid' | 'carousel';
    items: GalleryItem[];
}

export interface VideoData {
    source: 'youtube' | 'vimeo' | 'file';
    video_id?: string | null;
    path?: string | null;
    title?: string | null;
    poster?: string | null;
}

export interface AudioData {
    path: string;
    title?: string | null;
    transcript?: string | null;
}

export interface FileData {
    path: string;
    label: string;
}

export interface ChartSeries {
    name: string;
    data: number[];
}

export interface ChartData {
    variant: 'bar' | 'line' | 'pie';
    title?: string | null;
    labels: string[];
    series: ChartSeries[];
}

export interface FlashcardsData {
    cards: { front: string; back: string }[];
}

export interface StepsData {
    steps: { title: string; html: string }[];
}

export interface AccordionData {
    items: { title: string; html: string }[];
}

export interface TabsData {
    tabs: { title: string; html: string }[];
}

export type BlockType =
    | 'text'
    | 'heading'
    | 'quote'
    | 'divider'
    | 'table'
    | 'accordion'
    | 'tabs'
    | 'callout'
    | 'image'
    | 'gallery'
    | 'video'
    | 'audio'
    | 'file'
    | 'chart'
    | 'flashcards'
    | 'steps';

export type BlockData =
    | TextData
    | HeadingData
    | QuoteData
    | DividerData
    | TableData
    | CalloutData
    | ImageData
    | GalleryData
    | VideoData
    | AudioData
    | FileData
    | ChartData
    | FlashcardsData
    | StepsData
    | AccordionData
    | TabsData;

export interface ContentBlock {
    id: number;
    type: BlockType;
    order?: number;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    data: any;
}
