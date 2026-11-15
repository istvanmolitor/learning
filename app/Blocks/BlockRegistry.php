<?php

namespace App\Blocks;

use App\Blocks\Types\AccordionBlock;
use App\Blocks\Types\AudioBlock;
use App\Blocks\Types\CalloutBlock;
use App\Blocks\Types\ChartBlock;
use App\Blocks\Types\DividerBlock;
use App\Blocks\Types\FileBlock;
use App\Blocks\Types\FlashcardsBlock;
use App\Blocks\Types\GalleryBlock;
use App\Blocks\Types\HeadingBlock;
use App\Blocks\Types\ImageBlock;
use App\Blocks\Types\QuoteBlock;
use App\Blocks\Types\StepsBlock;
use App\Blocks\Types\TableBlock;
use App\Blocks\Types\TabsBlock;
use App\Blocks\Types\TextBlock;
use App\Blocks\Types\VideoBlock;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Resolves a block's `type` string to its BlockType instance. Every caller
 * that needs to do something type-specific (render, validate, export,
 * import, build the editor palette) goes through here instead of switching
 * on the type string itself — adding a block type never touches a caller.
 */
class BlockRegistry
{
    /** @var array<string, class-string<BlockType>> */
    private const MAP = [
        'text' => TextBlock::class,
        'heading' => HeadingBlock::class,
        'quote' => QuoteBlock::class,
        'divider' => DividerBlock::class,
        'table' => TableBlock::class,
        'accordion' => AccordionBlock::class,
        'tabs' => TabsBlock::class,
        'callout' => CalloutBlock::class,
        'image' => ImageBlock::class,
        'gallery' => GalleryBlock::class,
        'video' => VideoBlock::class,
        'audio' => AudioBlock::class,
        'file' => FileBlock::class,
        'chart' => ChartBlock::class,
        'flashcards' => FlashcardsBlock::class,
        'steps' => StepsBlock::class,
    ];

    public function has(string $type): bool
    {
        return isset(self::MAP[$type]);
    }

    public function resolve(string $type): BlockType
    {
        $class = self::MAP[$type] ?? null;

        return $class !== null ? app($class) : new UnknownBlock($type);
    }

    /**
     * @return array<string, BlockType> keyed by type
     */
    public function all(): array
    {
        $instances = [];

        foreach (self::MAP as $key => $class) {
            $instances[$key] = app($class);
        }

        return $instances;
    }

    /**
     * Palette entries for the editor, grouped by family.
     *
     * @return array<int, array{type: string, label: string, icon: string, family: string}>
     */
    public function palette(): array
    {
        $entries = [];

        foreach ($this->all() as $type => $block) {
            $entries[] = ['type' => $type, ...$block->meta()];
        }

        return $entries;
    }

    /**
     * Validate a block's payload against its type's rules (plus any
     * cross-field withValidator() check), throwing the same way a Form
     * Request would. Used by both the block CRUD controller and
     * course:validate, so a package and a live edit are held to the exact
     * same standard.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed> the validated (and defaulted) data
     *
     * @throws ValidationException
     */
    public function validate(string $type, array $data): array
    {
        $block = $this->resolve($type);

        $validator = Validator::make($data, $block->rules());
        $block->withValidator($validator, $data);

        return $validator->validate();
    }
}
