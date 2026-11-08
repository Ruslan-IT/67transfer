<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
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

    public function test_home_has_brief_sections_and_keeps_the_working_form(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('css/home.css', $html);
        $this->assertStringContainsString('css/home-footer.css', $html);
        $this->assertStringContainsString('data-transfer-form', $html);
        $this->assertStringContainsString('id="request"', $html);
        $this->assertStringContainsString('id="how"', $html);
        $this->assertStringContainsString('id="about"', $html);
        $this->assertStringContainsString('id="questions"', $html);

        $this->assertStringContainsString('Как это работает', $html);
        $this->assertStringContainsString('От заявки до поездки', $html);
        $this->assertStringContainsString('Оставьте заявку', $html);
        $this->assertStringContainsString('Согласуйте поездку', $html);
        $this->assertStringContainsString('Оплатите после поездки', $html);

        $this->assertStringContainsString('О сервисе', $html);
        $this->assertStringContainsString('Прямое согласование', $html);

        $this->assertStringContainsString('Когда я узнаю стоимость?', $html);
        $this->assertStringContainsString('Заявка уже подтверждает поездку?', $html);
        $this->assertStringContainsString('Начнём с вашего маршрута', $html);
        $this->assertStringContainsString('href="#request"', $html);

        $this->assertStringContainsString('home-footer', $html);
        $this->assertStringContainsString('images/brand/transfer-i-tochka_compact_white.svg', $html);
        $this->assertFileExists(public_path('images/home/hero-europe.webp'));
        $this->assertFileExists(public_path('images/brand/transfer-i-tochka_compact_white.svg'));
        $this->assertStringContainsString(route('legal.privacy'), $html);
        $this->assertStringContainsString(route('legal.consent'), $html);
        $this->assertStringContainsString('data-cookie-open', $html);

        $this->assertStringNotContainsString('Для визуализации', $html);
        $this->assertStringNotContainsString('class="hero"', $html);
        $this->assertStringNotContainsString('id="destinations"', $html);
        $this->assertStringNotContainsString('[Телефон]', $html);
        $this->assertStringNotContainsString('[Email]', $html);
    }

    public function test_home_two_and_home_three_do_not_load_home_blocks(): void
    {
        foreach (['/home-2', '/home-3'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString('css/home.css', $html);
            $this->assertStringNotContainsString('class="home-page"', $html);
            $this->assertStringContainsString('home-footer', $html);
            $this->assertStringContainsString('css/home-footer.css', $html);
            $this->assertStringNotContainsString('class="footer"', $html);
        }
    }
}
