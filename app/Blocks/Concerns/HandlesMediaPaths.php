<?php

namespace App\Blocks\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Shared export/import path-rewriting for block types that reference a
 * stored file (image, gallery, video, audio, file). The block only
 * declares *where* in its payload a path lives (mediaPaths()); the
 * exporter/importer decide *how* to rewrite it.
 */
trait HandlesMediaPaths
{
    /**
     * Dot-paths into the data array that hold a storage-relative media
     * path. Use "*" for a list segment, e.g. ["path"] or ["items.*.path"].
     *
     * @return string[]
     */
    abstract protected function mediaPaths(): array;

    /**
     * A public-disk-relative path resolves through the disk as usual; a
     * value that is already an absolute URL/path (e.g. one the Markdown
     * converter carried over verbatim from hand-written content) is used
     * as-is instead of being mangled by another Storage::url() pass.
     */
    protected function mediaUrl(?string $path): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        return preg_match('#^(https?:)?//|^/#', $path) === 1
            ? $path
            : Storage::disk('public')->url($path);
    }

    public function exportData(array $data, callable $rewrite): array
    {
        return $this->walkMediaPaths($data, $rewrite);
    }

    public function importData(array $data, callable $rewrite): array
    {
        return $this->walkMediaPaths($data, $rewrite);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  callable(string): string  $rewrite
     * @return array<string, mixed>
     */
    private function walkMediaPaths(array $data, callable $rewrite): array
    {
        foreach ($this->mediaPaths() as $path) {
            $data = $this->applyAtPath($data, explode('.', $path), $rewrite);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $segments
     * @param  callable(string): string  $rewrite
     * @return array<string, mixed>
     */
    private function applyAtPath(array $data, array $segments, callable $rewrite): array
    {
        $segment = array_shift($segments);

        if ($segment === '*') {
            foreach ($data as $index => $item) {
                if ($segments === []) {
                    if (is_string($item) && $item !== '') {
                        $data[$index] = $rewrite($item);
                    }

                    continue;
                }

                if (is_array($item)) {
                    $data[$index] = $this->applyAtPath($item, $segments, $rewrite);
                }
            }

            return $data;
        }

        // Every current mediaPaths() entry is either a single named segment
        // ("path") or ends in a "*" list ("items.*.path") — a named segment
        // is therefore always the terminal one, never itself a nested object
        // to recurse into.
        if ($segments !== [] || ! array_key_exists($segment, $data) || ! is_string($data[$segment]) || $data[$segment] === '') {
            return $data;
        }

        $data[$segment] = $rewrite($data[$segment]);

        return $data;
    }
}
