<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Blocks\Concerns\HandlesMediaPaths;
use Illuminate\Validation\Rule;

class GalleryBlock extends BlockType
{
    use HandlesMediaPaths;

    public static function key(): string
    {
        return 'gallery';
    }

    public function rules(): array
    {
        return [
            'layout' => ['required', 'string', Rule::in(config('blocks.gallery_layouts'))],
            'items' => ['required', 'array', 'min:1'],
            // nullable: see ImageBlock::rules() — filled in after upload.
            'items.*.path' => ['nullable', 'string'],
            'items.*.alt' => ['nullable', 'string', 'max:300'],
            'items.*.caption' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function render(array $data): string
    {
        $html = '<div class="content-gallery" data-layout="'.e($data['layout'] ?? 'grid').'">';

        foreach ($data['items'] ?? [] as $item) {
            $url = $this->mediaUrl($item['path'] ?? '');
            $html .= '<figure><img src="'.e($url).'" alt="'.e($item['alt'] ?? '').'">';

            if (! empty($item['caption'])) {
                $html .= '<figcaption>'.e($item['caption']).'</figcaption>';
            }

            $html .= '</figure>';
        }

        return $html.'</div>';
    }

    public function meta(): array
    {
        return ['label' => 'Galéria', 'icon' => 'images', 'family' => 'media'];
    }

    protected function mediaPaths(): array
    {
        return ['items.*.path'];
    }
}
