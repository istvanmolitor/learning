<?php

namespace App\Services\CourseTransfer;

use App\Models\Course;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * The import wizard's dry-run step: stages the uploaded package in a temp
 * directory, resolves (or synthesizes) its manifest, and computes what an
 * actual import would create, update, and — crucially — delete, without
 * touching the database. The confirm step replays the staged package through
 * CourseImporter using the returned token.
 */
class ImportPlanner
{
    private const STAGING_DIR = 'app/tmp/course-import-wizard';

    private const STAGING_TTL_HOURS = 24;

    public function __construct(private readonly CourseImporter $importer) {}

    /**
     * Stage an uploaded package (zip, loose .md files, or bare course.json) and
     * return its wizard token plus the computed plan.
     *
     * @param  UploadedFile[]  $files
     * @return array{token: string, plan: array<string, mixed>}
     */
    public function stage(array $files): array
    {
        $this->pruneStaleStagings();

        $token = Str::random(24);
        $root = $this->stagingPath($token);
        $packageDir = $root.'/package';
        File::makeDirectory($packageDir, recursive: true);

        $fallbackTitle = 'Importált kurzus';
        $manifestOnly = false;

        if (count($files) === 1 && strtolower($files[0]->getClientOriginalExtension()) === 'zip') {
            $fallbackTitle = pathinfo($files[0]->getClientOriginalName(), PATHINFO_FILENAME);
            $this->importer->extractZip($files[0]->getRealPath(), $packageDir);
        } elseif (count($files) === 1 && strtolower($files[0]->getClientOriginalExtension()) === 'json') {
            $manifestOnly = true;
            $files[0]->move($packageDir, 'course.json');
        } else {
            foreach ($files as $file) {
                if (strtolower($file->getClientOriginalExtension()) !== 'md') {
                    throw new RuntimeException('Vegyes feltöltésnél csak .md fájlok adhatók meg.');
                }

                $file->move($packageDir, $file->getClientOriginalName());
            }
        }

        $plan = $this->plan($packageDir, $fallbackTitle, $manifestOnly);

        File::put($root.'/plan.json', json_encode([
            'fallback_title' => $fallbackTitle,
            'manifest_only' => $manifestOnly,
            'plan' => $plan,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return ['token' => $token, 'plan' => $plan];
    }

    /**
     * Compute the plan for an already staged/extracted package directory.
     *
     * @return array<string, mixed>
     */
    public function plan(string $packageDir, string $fallbackTitle, bool $manifestOnly = false): array
    {
        [$manifest, $root, $format] = $this->resolveManifest($packageDir, $fallbackTitle, $manifestOnly);

        $warnings = [];
        $longPages = [];
        $pageCount = 0;
        $questionCount = 0;

        foreach ($manifest['lessons'] ?? [] as $lessonData) {
            $questionCount += count($lessonData['questions'] ?? []);

            foreach ($lessonData['pages'] ?? [] as $pageData) {
                $pageCount++;
                $label = ($lessonData['title'] ?? '?').' / '.($pageData['title'] ?? '?');

                if ($root === null) {
                    continue;
                }

                $file = $root.'/'.($pageData['file'] ?? '');

                if (! is_file($file)) {
                    $warnings[] = "Hiányzó oldalfájl: {$pageData['file']} ({$label})";

                    continue;
                }

                $markdown = (string) file_get_contents($file);

                if (trim($markdown) === '') {
                    $warnings[] = "Üres oldal: {$label}";
                }

                if (MarkdownSplitter::isTooLong($markdown)) {
                    $longPages[] = [
                        'lesson_slug' => $lessonData['slug'],
                        'page_slug' => $pageData['slug'],
                        'label' => $label,
                        'splittable' => count(MarkdownSplitter::split($markdown)) > 1,
                    ];
                }

                foreach ($this->referencedImages($markdown) as $image) {
                    if (! is_file($root.'/images/'.$image)) {
                        $warnings[] = "Hiányzó kép: images/{$image} ({$label})";
                    }
                }
            }
        }

        $existing = Course::query()
            ->where('slug', $manifest['slug'] ?? Str::slug($manifest['title']))
            ->withCount('lessons')
            ->first();

        return [
            'format' => $format,
            'course' => [
                'title' => $manifest['title'],
                'slug' => $manifest['slug'] ?? Str::slug($manifest['title']),
                'description' => $manifest['description'] ?? null,
                'lesson_count' => count($manifest['lessons'] ?? []),
                'page_count' => $pageCount,
                'question_count' => $questionCount,
            ],
            'existing_course' => $existing ? [
                'id' => $existing->id,
                'title' => $existing->title,
            ] : null,
            'changes' => $existing ? $this->diffAgainst($existing, $manifest) : null,
            'warnings' => $warnings,
            'long_pages' => $longPages,
        ];
    }

    /**
     * Run the real import for a staged package.
     *
     * @param  array{mode?: string, new_slug?: ?string, split_long_pages?: bool}  $options
     */
    public function confirm(string $token, array $options, ?int $createdBy): Course
    {
        $root = $this->stagingPath($token);

        if (! is_dir($root) || ! is_file($root.'/plan.json')) {
            throw new RuntimeException('Az import munkamenet lejárt vagy nem található. Töltsd fel újra a fájlokat.');
        }

        $staged = json_decode((string) File::get($root.'/plan.json'), true, flags: JSON_THROW_ON_ERROR);
        $packageDir = $root.'/package';

        $changes = $staged['plan']['changes'] ?? null;
        $hasDeletes = $changes !== null
            && (($changes['lessons']['delete'] ?? []) !== [] || ($changes['pages']['delete'] ?? []) !== []);

        if ($hasDeletes && ($options['mode'] ?? 'auto') !== 'new' && ! ($options['confirm_deletes'] ?? false)) {
            throw ValidationException::withMessages([
                'confirm_deletes' => 'A frissítés meglévő tartalmat törölne — ezt külön meg kell erősíteni.',
            ]);
        }

        try {
            [$manifest, $contentRoot] = $this->resolveManifest($packageDir, $staged['fallback_title'], $staged['manifest_only'] ?? false);

            if (($options['split_long_pages'] ?? false) && $contentRoot !== null) {
                $manifest = $this->splitLongPages($manifest, $contentRoot);
            }

            if (($options['mode'] ?? 'auto') === 'new' && ! empty($options['new_slug'])) {
                $manifest['slug'] = Str::slug($options['new_slug']);
            }

            return $contentRoot === null
                ? $this->importer->importManifestData($manifest, $createdBy)
                : $this->importer->importResolvedManifest($manifest, $contentRoot, $createdBy);
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function discard(string $token): void
    {
        File::deleteDirectory($this->stagingPath($token));
    }

    /**
     * @return array{0: array<string, mixed>, 1: ?string, 2: string} manifest, content root (null for manifest-only), format
     */
    private function resolveManifest(string $packageDir, string $fallbackTitle, bool $manifestOnly = false): array
    {
        $manifestRoot = $this->importer->locateManifestRoot($packageDir);

        if ($manifestRoot !== null) {
            $manifest = json_decode((string) file_get_contents($manifestRoot.'/course.json'), true, flags: JSON_THROW_ON_ERROR);

            // A bare course.json upload is a skeleton import by design; a zip
            // with a manifest is a full package, where missing page files are
            // real problems the plan must surface.
            return $manifestOnly
                ? [$manifest, null, 'manifest-only']
                : [$manifest, $manifestRoot, 'zip-manifest'];
        }

        $markdownRoot = $this->importer->resolveMarkdownRoot($packageDir);

        return [$this->importer->synthesizeManifest($markdownRoot, $fallbackTitle), $markdownRoot, 'markdown'];
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, array{create: string[], update: string[], delete: string[]}>
     */
    private function diffAgainst(Course $course, array $manifest): array
    {
        $course->load('lessons.pages');

        $manifestLessons = collect($manifest['lessons'] ?? [])->keyBy('slug');

        $lessonChanges = ['create' => [], 'update' => [], 'delete' => []];
        $pageChanges = ['create' => [], 'update' => [], 'delete' => []];

        foreach ($course->lessons as $lesson) {
            $manifestLesson = $manifestLessons->get($lesson->slug);

            if ($manifestLesson === null) {
                $lessonChanges['delete'][] = $lesson->title;

                foreach ($lesson->pages as $page) {
                    $pageChanges['delete'][] = "{$lesson->title} / {$page->title}";
                }

                continue;
            }

            $lessonChanges['update'][] = $manifestLesson['title'];

            $manifestPages = collect($manifestLesson['pages'] ?? [])->keyBy('slug');

            foreach ($lesson->pages as $page) {
                if (! $manifestPages->has($page->slug)) {
                    $pageChanges['delete'][] = "{$lesson->title} / {$page->title}";
                }
            }

            foreach ($manifestPages as $slug => $manifestPage) {
                $existingPage = $lesson->pages->firstWhere('slug', $slug);
                $label = "{$manifestLesson['title']} / {$manifestPage['title']}";

                $existingPage === null
                    ? $pageChanges['create'][] = $label
                    : $pageChanges['update'][] = $label;
            }
        }

        foreach ($manifestLessons as $slug => $manifestLesson) {
            if ($course->lessons->firstWhere('slug', $slug) === null) {
                $lessonChanges['create'][] = $manifestLesson['title'];

                foreach ($manifestLesson['pages'] ?? [] as $manifestPage) {
                    $pageChanges['create'][] = "{$manifestLesson['title']} / {$manifestPage['title']}";
                }
            }
        }

        return ['lessons' => $lessonChanges, 'pages' => $pageChanges];
    }

    /**
     * Rewrite the staged package so that every over-long page is split into
     * H2-bounded pages: new .md files are written next to the original and the
     * manifest's page list is expanded accordingly.
     *
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    private function splitLongPages(array $manifest, string $root): array
    {
        foreach ($manifest['lessons'] ?? [] as $lessonIndex => $lessonData) {
            $newPages = [];
            $order = 0;

            foreach ($lessonData['pages'] ?? [] as $pageData) {
                $file = $root.'/'.($pageData['file'] ?? '');
                $markdown = is_file($file) ? (string) file_get_contents($file) : '';

                if (! MarkdownSplitter::isTooLong($markdown)) {
                    $pageData['order'] = $order++;
                    $newPages[] = $pageData;

                    continue;
                }

                $chunks = MarkdownSplitter::split($markdown);

                if (count($chunks) < 2) {
                    $pageData['order'] = $order++;
                    $newPages[] = $pageData;

                    continue;
                }

                $usedSlugs = [];

                foreach ($chunks as $index => $chunk) {
                    $slug = MarkdownSplitter::chunkSlug($chunk['title'], $pageData['slug'], $index);

                    while (in_array($slug, $usedSlugs, true)) {
                        $slug .= '-'.($index + 1);
                    }

                    $usedSlugs[] = $slug;

                    $chunkFile = preg_replace('/\.md$/i', '', $pageData['file'])."--{$slug}.md";
                    File::put($root.'/'.$chunkFile, $chunk['markdown']);

                    $newPages[] = [
                        'title' => $chunk['title'] ?? $pageData['title'],
                        'slug' => $slug,
                        'order' => $order++,
                        'file' => $chunkFile,
                    ];
                }
            }

            $manifest['lessons'][$lessonIndex]['pages'] = $newPages;
        }

        return $manifest;
    }

    /**
     * @return string[] image file names referenced relative to the package's images/ dir
     */
    private function referencedImages(string $markdown): array
    {
        preg_match_all('/src="images\/([^"]+)"/i', $markdown, $htmlRefs);
        preg_match_all('/\]\(images\/([^)\s]+)\)/', $markdown, $mdRefs);

        return array_unique([...$htmlRefs[1], ...$mdRefs[1]]);
    }

    private function stagingPath(string $token): string
    {
        if (! preg_match('/^[A-Za-z0-9]{24}$/', $token)) {
            throw new RuntimeException('Érvénytelen import token.');
        }

        return storage_path(self::STAGING_DIR.'/'.$token);
    }

    private function pruneStaleStagings(): void
    {
        $base = storage_path(self::STAGING_DIR);

        if (! is_dir($base)) {
            return;
        }

        foreach (File::directories($base) as $dir) {
            if (File::lastModified($dir) < now()->subHours(self::STAGING_TTL_HOURS)->getTimestamp()) {
                File::deleteDirectory($dir);
            }
        }
    }
}
