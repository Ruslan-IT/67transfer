<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeThreeTest extends TestCase
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

    public function test_home_three_renders_porthole_cloud_deck_and_aerial_europe(): void
    {
        $response = $this->get('/home-3')->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $html = $response->getContent();

        foreach (['data-h3-flight', 'data-h3-cabin', 'data-h3-land', 'data-h3-deck', 'data-h3-fog', 'data-h3-runway'] as $hook) {
            $this->assertStringContainsString($hook, $html);
        }

        $this->assertStringContainsString('css/home-3.css', $html);
        $this->assertStringContainsString('js/home-3.js', $html);
        $this->assertStringNotContainsString('css/home-2.css', $html);
        $this->assertStringNotContainsString('js/home-2.js', $html);

        foreach (['cabin-wall.webp', 'window-pane.webp', 'sky.webp', 'clouds-strip.webp', 'sky-deep.avif', 'europe-aerial-2048.jpg', 'europe-aerial-3840.jpg'] as $file) {
            $this->assertStringContainsString('media/home-3/'.$file, $html);
            $this->assertFileExists(public_path('media/home-3/'.$file));
        }

        $this->assertStringNotContainsString('media/home-2/', $html);
    }

    public function test_home_three_embeds_the_existing_transfer_form(): void
    {
        $html = $this->get('/home-3')->assertOk()->getContent();

        $this->assertStringContainsString('data-transfer-form', $html);
        $this->assertStringContainsString('transfer-search--embedded', $html);
        $this->assertStringContainsString(route('transfer.continue'), $html);
        $this->assertStringContainsString(route('transfer.enquiry'), $html);
        $this->assertMatchesRegularExpression('/<button[^>]*data-continue[^>]*>/', $html);
        $this->assertStringContainsString('js/transfer-search.js', $html);
    }

    public function test_home_three_keeps_a_saved_transfer_draft(): void
    {
        $draft = ['trip' => 'oneway', 'outbound' => ['date' => '2026-11-21', 'time' => '10:15']];

        $html = $this->withSession(['transfer.draft' => $draft])->get('/home-3')->assertOk()->getContent();

        $this->assertStringContainsString('id="transfer-search-draft"', $html);
        $this->assertStringContainsString('2026-11-21', $html);
    }

    public function test_home_three_header_has_section_menu_with_real_targets(): void
    {
        $html = $this->get('/home-3')->assertOk()->getContent();

        $this->assertStringContainsString('class="h3-nav"', $html);
        $this->assertStringContainsString('data-h3-nav-mobile', $html);
        $this->assertStringContainsString('id="how"', $html);
        $this->assertStringContainsString('href="#how"', $html);
        $this->assertStringContainsString('href="'.route('information').'"', $html);
        $this->assertStringContainsString('href="'.route('contacts').'"', $html);

        foreach (['Как это работает', 'О сервисе', 'Вопросы'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_other_pages_do_not_load_home_three_assets(): void
    {
        foreach (['/', '/home-2'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString('home-3.css', $html);
            $this->assertStringNotContainsString('home-3.js', $html);
            $this->assertStringNotContainsString('data-h3-flight', $html);
            $this->assertStringNotContainsString('h3-nav', $html);
        }
    }

    public function test_home_three_slug_is_reserved_for_destinations(): void
    {
        $this->assertContains('home-3', Destination::RESERVED_SLUGS);
    }

    public function test_scene_script_is_scroll_scrubbed_and_reversible(): void
    {
        $script = file_get_contents(public_path('js/home-3.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('scrollTrigger', $script);
        $this->assertStringContainsString('scrub', $script);
        $this->assertStringContainsString('prefers-reduced-motion', $script);
        $this->assertStringNotContainsString('toggleActions', $script);
        $this->assertStringNotContainsString('once: true', $script);
    }
}
