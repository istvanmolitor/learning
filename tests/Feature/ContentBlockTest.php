<?php

namespace Tests\Feature;

use App\Blocks\BlockRegistry;
use App\Models\Course;
use App\Models\Group;
use App\Models\Page;
use App\Models\Permission;
use App\Models\User;
use App\Services\CourseTransfer\MarkdownToBlocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentBlockTest extends TestCase
{
    use RefreshDatabase;

    private function makeManager(): User
    {
        $group = Group::create(['name' => 'Kurzusmenedzser']);
        $permission = Permission::create(['name' => 'manage-courses', 'description' => 'x']);
        $group->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->groups()->attach($group);

        return $user;
    }

    private function makePage(): Page
    {
        $course = Course::create(['title' => 'Blokkos kurzus', 'slug' => 'blokkos', 'order' => 0]);
        $lesson = $course->lessons()->create(['title' => 'Lecke', 'slug' => 'lecke', 'order' => 0]);

        return $lesson->pages()->create(['title' => 'Oldal', 'slug' => 'oldal', 'order' => 0, 'markdown' => '']);
    }

    public function test_registry_resolves_and_validates_every_registered_type(): void
    {
        $registry = app(BlockRegistry::class);

        $samples = [
            'text' => ['html' => '<p>szöveg</p>'],
            'heading' => ['level' => 2, 'text' => 'Cím'],
            'quote' => ['html' => '<p>idézet</p>'],
            'divider' => ['variant' => 'line'],
            'table' => ['headers' => ['a'], 'rows' => [['1']]],
            'accordion' => ['items' => [['title' => 't', 'html' => '<p>b</p>']]],
            'tabs' => ['tabs' => [['title' => 't', 'html' => '<p>b</p>']]],
            'callout' => ['variant' => 'definicio', 'html' => '<p>def</p>'],
            'image' => ['path' => 'course-media/1/x.png'],
            'gallery' => ['layout' => 'grid', 'items' => [['path' => 'course-media/1/x.png']]],
            'video' => ['source' => 'youtube', 'video_id' => 'abc123'],
            'audio' => ['path' => 'course-media/1/x.mp3'],
            'file' => ['path' => 'course-media/1/x.pdf', 'label' => 'Letöltés'],
            'chart' => ['variant' => 'bar', 'labels' => ['a', 'b'], 'series' => [['name' => 's', 'data' => [1, 2]]]],
            'flashcards' => ['cards' => [['front' => 'f', 'back' => 'b']]],
            'steps' => ['steps' => [['title' => 't', 'html' => '<p>b</p>']]],
        ];

        $this->assertSame(array_keys($samples), array_keys($registry->all()), 'Minden regisztrált blokktípushoz tartozzon egy minta.');

        foreach ($samples as $type => $data) {
            $validated = $registry->validate($type, $data);
            $html = $registry->resolve($type)->render($validated);
            $this->assertNotSame('', trim($html), "{$type} render() ne legyen üres.");
        }
    }

    public function test_chart_rejects_series_with_mismatched_data_length(): void
    {
        $registry = app(BlockRegistry::class);

        $this->expectException(ValidationException::class);
        $registry->validate('chart', [
            'variant' => 'bar',
            'labels' => ['a', 'b'],
            'series' => [['name' => 's', 'data' => [1, 2, 3]]],
        ]);
    }

    public function test_unknown_type_degrades_instead_of_throwing(): void
    {
        $registry = app(BlockRegistry::class);
        $block = $registry->resolve('nem-letezo-tipus');

        $this->assertStringContainsString('nem-letezo-tipus', $block->render([]));
    }

    public function test_admin_can_create_update_delete_and_reorder_blocks(): void
    {
        $manager = $this->makeManager();
        $page = $this->makePage();

        $this->actingAs($manager)->post("/admin/pages/{$page->id}/blocks", [
            'type' => 'heading',
            'data' => ['level' => 2, 'text' => 'Első cím'],
        ])->assertRedirect();

        $this->actingAs($manager)->post("/admin/pages/{$page->id}/blocks", [
            'type' => 'text',
            'data' => ['html' => '<p>Szöveg</p>'],
        ])->assertRedirect();

        $page->refresh();
        $this->assertCount(2, $page->blocks);
        $this->assertStringContainsString('<h2>Első cím</h2>', $page->body_html);
        $this->assertStringContainsString('<p>Szöveg</p>', $page->body_html);
        $this->assertSame($page->markdown, $page->body_html, 'Blokkokból generált tartalomnál a két oszlopnak meg kell egyeznie.');

        $heading = $page->blocks()->where('type', 'heading')->first();
        $this->actingAs($manager)->put("/admin/blocks/{$heading->id}", [
            'data' => ['level' => 3, 'text' => 'Módosított cím'],
        ])->assertRedirect();

        $this->assertSame(3, $heading->fresh()->data['level']);
        $this->assertStringContainsString('<h3>Módosított cím</h3>', $page->fresh()->body_html);

        $text = $page->blocks()->where('type', 'text')->first();

        $this->actingAs($manager)->post("/admin/pages/{$page->id}/blocks/reorder", [
            'order' => [$text->id, $heading->id],
        ])->assertRedirect();

        $this->assertSame(0, $text->fresh()->order);
        $this->assertSame(1, $heading->fresh()->order);

        $this->actingAs($manager)->delete("/admin/blocks/{$text->id}")->assertRedirect();

        $page->refresh();
        $this->assertCount(1, $page->blocks);
        $this->assertStringNotContainsString('Szöveg', $page->body_html);
    }

    public function test_store_rejects_an_invalid_payload_without_creating_a_block(): void
    {
        $manager = $this->makeManager();
        $page = $this->makePage();

        $this->actingAs($manager)->post("/admin/pages/{$page->id}/blocks", [
            'type' => 'callout',
            'data' => ['variant' => 'nem-letezo-variáns', 'html' => '<p>x</p>'],
        ])->assertSessionHasErrors();

        $this->assertCount(0, $page->fresh()->blocks);
    }

    public function test_convert_turns_legacy_markdown_into_blocks_once(): void
    {
        $manager = $this->makeManager();
        $page = $this->makePage();
        $page->update(['markdown' => "## Alcím\n\nSzöveg **kiemelve**."]);

        $this->actingAs($manager)->post("/admin/pages/{$page->id}/blocks/convert")->assertRedirect();

        $page->refresh();
        $this->assertSame('heading', $page->blocks[0]->type);
        $this->assertSame('text', $page->blocks[1]->type);

        // A second call is a no-op (does not duplicate blocks) once blocks exist.
        $this->actingAs($manager)->post("/admin/pages/{$page->id}/blocks/convert")->assertRedirect();
        $this->assertCount(2, $page->fresh()->blocks);
    }

    public function test_markdown_to_blocks_extracts_an_iframe_video_despite_commonmarks_html_filter(): void
    {
        $markdown = <<<'MD'
            Szöveg előtte.

            <iframe width="560" height="315" src="https://www.youtube.com/embed/abc123" title="Videó cím" frameborder="0" allowfullscreen></iframe>

            Szöveg utána.
            MD;

        $blocks = MarkdownToBlocks::convert($markdown);
        $types = array_column($blocks, 'type');

        $this->assertSame(['text', 'video', 'text'], $types);
        $this->assertSame('youtube', $blocks[1]['data']['source']);
        $this->assertSame('abc123', $blocks[1]['data']['video_id']);
    }
}
