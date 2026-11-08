<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Support\FirstScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FirstScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_the_default_first_screen_beside_the_form(): void
    {
        $this->homePage();

        $home = $this->get('/');

        $home->assertOk();
        $home->assertSeeInOrder([
            'class="header"',
            'class="first-screen"',
            'Комфортные поездки по Европе',
            'Трансферы в Европе — просто и надёжно',
            'Оставить заявку бесплатно',
            'Бесплатная заявка',
            'Подтверждение менеджером',
            'Удобно для всей семьи',
            'data-transfer-form',
            'id="how"',
        ], false);
        $home->assertDontSee('first-screen__art', false);
    }

    public function test_saved_copy_replaces_the_defaults_and_survives_a_reload(): void
    {
        $this->homePage();

        app(FirstScreen::class)->update([
            'eyebrow' => 'ПОЕЗДКИ БЕЗ СУЕТЫ',
            'title' => 'Свой заголовок экрана',
            'lead' => 'Своя строка',
            'note' => 'Своё пояснение для гостя.',
            'blocks' => [
                ['title' => 'Первый пункт', 'text' => 'Коротко о первом.'],
                ['title' => 'Второй пункт', 'text' => 'Коротко о втором.'],
                ['title' => 'Третий пункт', 'text' => 'Коротко о третьем.'],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('ПОЕЗДКИ БЕЗ СУЕТЫ', false)
            ->assertSee('Свой заголовок экрана', false)
            ->assertSee('Первый пункт', false)
            ->assertDontSee('Подтверждение менеджером', false);

        $this->get('/')
            ->assertOk()
            ->assertSee('Свой заголовок экрана', false);
    }

    public function test_script_inside_an_uploaded_icon_is_rejected(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('first-screen/icons/bad.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        try {
            app(FirstScreen::class)->update([
                'blocks' => [
                    ['icon' => 'first-screen/icons/bad.svg'],
                ],
            ]);
            $this->fail('Опасный SVG не должен сохраняться.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.blocks.0.icon', $exception->errors());
        }

        Storage::disk('public')->assertMissing('first-screen/icons/bad.svg');
        $this->assertNull(app(FirstScreen::class)->data()['blocks'][0]['icon']);
    }

    public function test_administrator_can_open_the_first_screen_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/first-screen')
            ->assertOk()
            ->assertSee('Первый экран', false)
            ->assertSee('Фоновое изображение', false)
            ->assertSee('Иконка SVG', false);
    }

    private function homePage(): void
    {
        Page::query()->create([
            'title' => 'Главная',
            'slug' => 'home',
            'content' => [],
            'is_published' => true,
        ]);
    }
}
