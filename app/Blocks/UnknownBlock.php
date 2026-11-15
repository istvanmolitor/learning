<?php

namespace App\Blocks;

/**
 * Null-object fallback for a `type` the registry doesn't recognize (e.g. an
 * export produced by a newer version of the app). Renders a visible but
 * harmless placeholder instead of throwing, and accepts any payload so an
 * import never fails because of it.
 */
class UnknownBlock extends BlockType
{
    public function __construct(private readonly ?string $originalType = null) {}

    public static function key(): string
    {
        return 'unknown';
    }

    public function rules(): array
    {
        return [];
    }

    public function render(array $data): string
    {
        $type = $this->originalType !== null ? e($this->originalType) : 'ismeretlen';

        return '<div class="content-unknown" data-type="'.$type.'">Nem támogatott tartalomblokk ('.$type.').</div>';
    }

    public function meta(): array
    {
        return ['label' => 'Ismeretlen blokk', 'icon' => 'help-circle', 'family' => 'other'];
    }
}
