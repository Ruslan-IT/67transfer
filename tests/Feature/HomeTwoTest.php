<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTwoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Page::query()->create([
            'title' => 'Главная',
            'slug' => 'home',
            'content' => [],
            'is_published' => true,
        ]);
    }

    public function test_home_two_renders_the_porthole_scene_with_the_existing_form(): void
    {
        $response = $this->get('/home-2')->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $html = $response->getContent();

        $this->assertStringContainsString('data-h2-flight', $html);
        $this->assertStringContainsString('data-h2-cabin', $html);
        $this->assertStringContainsString('data-h2-runway', $html);
        $this->assertStringContainsString('css/home-2.css', $html);
        $this->assertStringContainsString('js/home-2.js', $html);
        $this->assertStringContainsString('css/transfer-search.css', $html);

        $this->assertStringContainsString('data-transfer-form', $html);
        $this->assertStringContainsString('transfer-search--embedded', $html);
        $this->assertStringContainsString(route('transfer.continue'), $html);
        $this->assertStringContainsString(route('transfer.enquiry'), $html);
        $this->assertMatchesRegularExpression('/<button[^>]*data-continue[^>]*>/', $html);
        $this->assertStringContainsString('js/transfer-search.js', $html);

        foreach (['cabin-wall.webp', 'window-pane.webp', 'sky.webp', 'clouds-strip.webp', 'sky-deep.avif', 'shade-handle.webp'] as $file) {
            $this->assertStringContainsString('media/home-2/'.$file, $html);
            $this->assertFileExists(public_path('media/home-2/'.$file));
        }

        $this->assertStringNotContainsString('ilu/', $html);
        $this->assertStringNotContainsString('website-files.com', $html);
    }

    public function test_home_two_keeps_a_saved_transfer_draft(): void
    {
        $draft = ['trip' => 'oneway', 'outbound' => ['date' => '2026-11-20', 'time' => '14:30']];

        $html = $this->withSession(['transfer.draft' => $draft])->get('/home-2')->assertOk()->getContent();

        $this->assertStringContainsString('id="transfer-search-draft"', $html);
        $this->assertStringContainsString('2026-11-20', $html);
    }

    public function test_original_home_page_does_not_load_home_two_assets(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-h2-flight', $html);
        $this->assertStringNotContainsString('home-2.css', $html);
        $this->assertStringNotContainsString('home-2.js', $html);
        $this->assertStringContainsString('first-screen', $html);
        $this->assertStringContainsString('data-transfer-form', $html);
    }

    public function test_home_two_slug_is_reserved_for_destinations(): void
    {
        $this->assertContains('home-2', Destination::RESERVED_SLUGS);
    }

    public function test_scene_script_is_scroll_scrubbed_and_reversible(): void
    {
        $script = file_get_contents(public_path('js/home-2.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('scrollTrigger', $script);
        $this->assertStringContainsString('scrub', $script);
        $this->assertStringContainsString('prefers-reduced-motion', $script);
        $this->assertStringNotContainsString('toggleActions', $script);
        $this->assertStringNotContainsString('once: true', $script);
    }
}
