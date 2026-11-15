// Mirrors App\Blocks\Concerns\HandlesMediaPaths::mediaUrl() — a path already
// looking like a URL is used as-is, otherwise it is disk-relative and gets
// the public storage prefix the rest of the app already uses verbatim
// (e.g. Editor.vue's thumbnail/narration previews).
export function mediaUrl(path: string | null | undefined): string {
    if (!path) return '';
    if (/^(https?:)?\/\//.test(path) || path.startsWith('/')) return path;
    return `/storage/${path}`;
}
