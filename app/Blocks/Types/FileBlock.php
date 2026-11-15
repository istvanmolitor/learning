<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Blocks\Concerns\HandlesMediaPaths;

class FileBlock extends BlockType
{
    use HandlesMediaPaths;

    public static function key(): string
    {
        return 'file';
    }

    public function rules(): array
    {
        return [
            // nullable: see ImageBlock::rules() — filled in after upload.
            'path' => ['nullable', 'string'],
            'label' => ['required', 'string', 'max:200'],
        ];
    }

    public function render(array $data): string
    {
        $url = $this->mediaUrl($data['path'] ?? '');

        return '<p class="content-file">📎 <a href="'.e($url).'" download>'.e($data['label'] ?? 'Letöltés').'</a></p>';
    }

    public function meta(): array
    {
        return ['label' => 'Letölthető fájl', 'icon' => 'paperclip', 'family' => 'media'];
    }

    protected function mediaPaths(): array
    {
        return ['path'];
    }
}
