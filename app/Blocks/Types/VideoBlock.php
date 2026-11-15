<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Blocks\Concerns\HandlesMediaPaths;
use Illuminate\Validation\Rule;

/**
 * Embedded (YouTube/Vimeo, by video ID only — never a raw iframe/URL from
 * the admin) or an uploaded file. The YouTube markup intentionally matches
 * what App\Services\Publishing\PageHtml::forPdf() already looks for, so a
 * PDF export turns it into a "▶ watch on YouTube" link card for free — a
 * video obviously can't play inside a static PDF/PPTX page.
 */
class VideoBlock extends BlockType
{
    use HandlesMediaPaths;

    public static function key(): string
    {
        return 'video';
    }

    public function rules(): array
    {
        return [
            'source' => ['required', 'string', Rule::in(config('blocks.video_sources'))],
            // Both nullable, not required_if: see ImageBlock::rules() — the
            // embed ID / uploaded file is filled in after the block exists.
            'video_id' => ['nullable', 'string', 'max:100'],
            'path' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:200'],
            'poster' => ['nullable', 'string'],
        ];
    }

    public function render(array $data): string
    {
        $title = e($data['title'] ?? 'Videó');

        return match ($data['source'] ?? null) {
            'youtube' => '<iframe width="560" height="315" src="https://www.youtube.com/embed/'.e($data['video_id'] ?? '').'" title="'.$title.'" frameborder="0" allowfullscreen></iframe>',
            'vimeo' => '<iframe width="560" height="315" src="https://player.vimeo.com/video/'.e($data['video_id'] ?? '').'" title="'.$title.'" frameborder="0" allowfullscreen></iframe>',
            'file' => '<video controls'.($this->poster($data)).' src="'.e($this->mediaUrl($data['path'] ?? '')).'"></video>',
            default => '',
        };
    }

    public function meta(): array
    {
        return ['label' => 'Videó', 'icon' => 'video', 'family' => 'media'];
    }

    protected function mediaPaths(): array
    {
        return ['path', 'poster'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function poster(array $data): string
    {
        return empty($data['poster']) ? '' : ' poster="'.e($this->mediaUrl($data['poster'])).'"';
    }
}
