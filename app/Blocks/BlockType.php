<?php

namespace App\Blocks;

use Illuminate\Validation\Validator;

/**
 * One content-block type's complete logic: validation and HTML rendering.
 * A block never stores color/CSS — only type, data, and at most a named
 * display mode (e.g. callout `variant`, image `size`). The actual look is
 * owned entirely by the frontend's design tokens, so a redesign never
 * touches stored content. See BlockRegistry for how a `type` key resolves
 * to an instance of this class.
 */
abstract class BlockType
{
    abstract public static function key(): string;

    /**
     * Laravel validation rules for this block's `data` payload. Keys are
     * relative to the payload root (i.e. without a "data." prefix).
     *
     * @return array<string, mixed>
     */
    abstract public function rules(): array;

    /**
     * Optional hook for cross-field checks rules() can't express (e.g.
     * ChartBlock: every series' data length must match labels' length).
     * No-op by default.
     *
     * @param  array<string, mixed>  $data
     */
    public function withValidator(Validator $validator, array $data): void {}

    /**
     * Degradated, self-contained HTML for this block. Used to render the
     * block server-side (page.body_html cache, search, PDF/PPTX export via
     * the existing PageHtml/CoursePptxExporter pipelines, and as the
     * legacy-fallback view for browsers with JS disabled).
     *
     * @param  array<string, mixed>  $data
     */
    abstract public function render(array $data): string;

    /**
     * Human label, icon key (a @lucide/vue icon name) and family grouping
     * for the editor's insert palette. The palette's colors come from the
     * frontend, not from here.
     *
     * @return array{label: string, icon: string, family: string}
     */
    abstract public function meta(): array;

    /**
     * Plain-text search surface for this block (defaults to a tag-stripped
     * render()). Not wired into search yet — App\Http\Controllers\SearchController
     * only searches page titles today — but kept on the contract so adding
     * body search later does not require touching every block class.
     *
     * @param  array<string, mixed>  $data
     */
    public function searchText(array $data): string
    {
        return trim(strip_tags($this->render($data)));
    }

    /**
     * Rewrite this block's media references for the export package: $rewrite
     * turns a public-storage-relative path into a package-relative one
     * (and copies the file into the zip as a side effect). Only the media
     * block types (see HandlesMediaPaths) have anything to rewrite.
     *
     * @param  array<string, mixed>  $data
     * @param  callable(string): string  $rewrite
     * @return array<string, mixed>
     */
    public function exportData(array $data, callable $rewrite): array
    {
        return $data;
    }

    /**
     * The inverse of exportData(): $rewrite turns a package-relative media
     * path into a freshly imported public-storage path.
     *
     * @param  array<string, mixed>  $data
     * @param  callable(string): string  $rewrite
     * @return array<string, mixed>
     */
    public function importData(array $data, callable $rewrite): array
    {
        return $data;
    }
}
