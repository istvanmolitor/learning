<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Blocks\Concerns\HandlesMediaPaths;

/**
 * A section-level audio clip — distinct from Page::narration_audio_path,
 * which is the whole page's TTS narration. Renders a plain link alongside
 * the <audio> tag on purpose: dompdf (PDF export) cannot play audio, so the
 * link is what survives there.
 */
class AudioBlock extends BlockType
{
    use HandlesMediaPaths;

    public static function key(): string
    {
        return 'audio';
    }

    public function rules(): array
    {
        return [
            // nullable: see ImageBlock::rules() — filled in after upload.
            'path' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:200'],
            'transcript' => ['nullable', 'string'],
        ];
    }

    public function render(array $data): string
    {
        $url = $this->mediaUrl($data['path'] ?? '');
        $title = e($data['title'] ?? 'Hanganyag');

        $html = '<div class="content-audio"><p>🔊 <a href="'.e($url).'">'.$title.'</a></p>';
        $html .= '<audio controls src="'.e($url).'"></audio>';

        if (! empty($data['transcript'])) {
            $html .= '<details><summary>Átirat</summary><p>'.e($data['transcript']).'</p></details>';
        }

        return $html.'</div>';
    }

    public function meta(): array
    {
        return ['label' => 'Hang', 'icon' => 'volume-2', 'family' => 'media'];
    }

    protected function mediaPaths(): array
    {
        return ['path'];
    }
}
