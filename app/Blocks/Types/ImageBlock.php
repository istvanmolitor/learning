<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Blocks\Concerns\HandlesMediaPaths;
use Illuminate\Validation\Rule;

class ImageBlock extends BlockType
{
    use HandlesMediaPaths;

    public static function key(): string
    {
        return 'image';
    }

    public function rules(): array
    {
        return [
            // nullable: a freshly inserted block has no file yet — the admin
            // uploads one afterwards via MediaPicker, which PATCHes this field in.
            'path' => ['nullable', 'string'],
            'alt' => ['nullable', 'string', 'max:300'],
            'caption' => ['nullable', 'string', 'max:300'],
            'size' => ['nullable', 'string', Rule::in(config('blocks.image_sizes'))],
        ];
    }

    public function render(array $data): string
    {
        $url = $this->mediaUrl($data['path'] ?? '');
        $alt = e($data['alt'] ?? '');

        $html = '<figure class="content-image" data-size="'.e($data['size'] ?? 'normal').'">';
        $html .= '<img src="'.e($url).'" alt="'.$alt.'">';

        if (! empty($data['caption'])) {
            $html .= '<figcaption>'.e($data['caption']).'</figcaption>';
        }

        return $html.'</figure>';
    }

    public function meta(): array
    {
        return ['label' => 'Kép', 'icon' => 'image', 'family' => 'media'];
    }

    protected function mediaPaths(): array
    {
        return ['path'];
    }
}
