<?php

namespace Tests\Feature;

use App\Models\HomeBlock;
use App\Models\Page;
use App\Models\User;
use App\Support\HomeBlockRegistry;
use App\Support\HomeBlocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeBlocksTest extends TestCase
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

    public function test_home_renders_default_sections_without_database_rows(): void
    {
        $this->assertSame(0, HomeBlock::query()->count());

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="how"', $html);
        $this->assertStringContainsString('id="about"', $html);
        $this->assertStringContainsString('id="questions"', $html);
        $this->assertStringContainsString('От заявки до поездки', $html);
        $this->assertStringContainsString('Когда я узнаю стоимость?', $html);
        $this->assertStringContainsString('Начнём с вашего маршрута', $html);
        $this->assertStringContainsString('data-transfer-form', $html);
    }

    public function test_disabled_block_is_hidden_and_nav_updates(): void
    {
        app(HomeBlocks::class)->ensureSystemBlocks();

        HomeBlock::query()
            ->where('type', HomeBlockRegistry::FAQ)
            ->update(['is_enabled' => false]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="how"', $html);
        $this->assertStringContainsString('id="about"', $html);
        $this->assertStringNotContainsString('id="questions"', $html);
        $this->assertStringNotContainsString('Когда я узнаю стоимость?', $html);
        $this->assertStringContainsString('href="#how"', $html);
        $this->assertStringContainsString('href="#about"', $html);
        $this->assertStringNotContainsString('href="#questions"', $html);
    }

    public function test_custom_payload_and_sort_order_are_applied(): void
    {
        app(HomeBlocks::class)->ensureSystemBlocks();

        $how = HomeBlock::query()->where('type', HomeBlockRegistry::HOW_IT_WORKS)->firstOrFail();
        $about = HomeBlock::query()->where('type', HomeBlockRegistry::ABOUT)->firstOrFail();
        $faq = HomeBlock::query()->where('type', HomeBlockRegistry::FAQ)->firstOrFail();
        $cta = HomeBlock::query()->where('type', HomeBlockRegistry::CTA)->firstOrFail();

        $how->update([
            'sort_order' => 30,
            'payload' => [
                'kicker' => 'Процесс',
                'heading' => 'Свой заголовок шагов',
                'note' => 'Своя сноска.',
                'steps' => [
                    ['title' => 'Шаг А', 'description' => 'Описание А'],
                ],
            ],
        ]);
        $about->update(['sort_order' => 10]);
        $faq->update(['sort_order' => 20]);
        $cta->update(['sort_order' => 40]);

        $html = $this->get('/')->assertOk()->getContent();

        $aboutPos = strpos($html, 'id="about"');
        $faqPos = strpos($html, 'id="questions"');
        $howPos = strpos($html, 'id="how"');
        $ctaPos = strpos($html, 'class="home-cta"');

        $this->assertNotFalse($aboutPos);
        $this->assertNotFalse($faqPos);
        $this->assertNotFalse($howPos);
        $this->assertNotFalse($ctaPos);
        $this->assertTrue($aboutPos < $faqPos);
        $this->assertTrue($faqPos < $howPos);
        $this->assertTrue($howPos < $ctaPos);
        $this->assertStringContainsString('Свой заголовок шагов', $html);
        $this->assertStringContainsString('Шаг А', $html);
        $this->assertStringContainsString('Своя сноска.', $html);
    }

    public function test_broken_payload_falls_back_to_defaults(): void
    {
        app(HomeBlocks::class)->ensureSystemBlocks();

        HomeBlock::query()
            ->where('type', HomeBlockRegistry::HOW_IT_WORKS)
            ->update(['payload' => ['steps' => 'broken']]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Оставьте заявку', $html);
        $this->assertStringContainsString('Согласуйте поездку', $html);
    }

    public function test_system_blocks_cannot_be_deleted(): void
    {
        app(HomeBlocks::class)->ensureSystemBlocks();

        $block = HomeBlock::query()->where('is_system', true)->firstOrFail();

        $this->assertFalse($block->delete());
        $this->assertDatabaseHas('home_blocks', ['id' => $block->id]);
    }

    public function test_non_system_block_can_be_deleted(): void
    {
        $block = HomeBlock::query()->create([
            'type' => HomeBlockRegistry::CTA,
            'name' => 'Доп. CTA',
            'is_enabled' => true,
            'is_system' => false,
            'sort_order' => 99,
            'payload' => HomeBlockRegistry::defaults(HomeBlockRegistry::CTA),
        ]);

        $this->assertTrue((bool) $block->delete());
        $this->assertDatabaseMissing('home_blocks', ['id' => $block->id]);
    }

    public function test_administrator_can_open_home_blocks_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/home-blocks')
            ->assertOk()
            ->assertSee('Блоки главной страницы', false);
    }
}
