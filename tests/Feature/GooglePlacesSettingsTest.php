<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\GooglePlaces;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GooglePlacesSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_secrets_are_encrypted_and_hidden_from_the_browser_config(): void
    {
        $browserKey = 'browser-key-test-67transfer';
        $serverKey = 'server-key-test-67transfer';

        app(GooglePlaces::class)->update([
            'enabled' => false,
            'language' => 'ru',
            'browser_api_key' => $browserKey,
            'server_api_key' => $serverKey,
        ]);

        $storedBrowser = Setting::query()->where('key', 'google_places.browser_api_key')->first();
        $storedServer = Setting::query()->where('key', 'google_places.server_api_key')->first();

        $this->assertNotNull($storedBrowser);
        $this->assertTrue($storedBrowser->is_secret);
        $this->assertStringNotContainsString($browserKey, (string) $storedBrowser->value);
        $this->assertStringNotContainsString($serverKey, (string) $storedServer->value);

        $disabled = app(GooglePlaces::class)->clientConfig();
        $this->assertFalse($disabled['active']);
        $this->assertNull($disabled['browserKey']);
        $this->assertArrayNotHasKey('serverKey', $disabled);

        app(GooglePlaces::class)->update([
            'enabled' => true,
            'language' => 'not a language',
            'browser_api_key' => '',
            'server_api_key' => '',
        ]);

        $enabled = app(GooglePlaces::class)->clientConfig();

        $this->assertTrue($enabled['active']);
        $this->assertSame($browserKey, $enabled['browserKey']);
        $this->assertSame('ru', $enabled['language']);
        $this->assertStringNotContainsString($serverKey, json_encode($enabled, JSON_THROW_ON_ERROR));

        app(GooglePlaces::class)->update([
            'enabled' => true,
            'language' => 'ru',
            'browser_api_key' => '',
            'clear_browser_key' => true,
            'server_api_key' => '',
            'clear_server_key' => true,
        ]);

        $this->assertFalse(app(GooglePlaces::class)->hasBrowserKey());
        $this->assertFalse(app(GooglePlaces::class)->hasServerKey());
    }

    public function test_home_page_shows_the_transfer_form_without_a_server_key(): void
    {
        $serverKey = 'server-key-must-stay-private';

        Page::query()->create([
            'title' => 'Главная',
            'slug' => 'home',
            'content' => [],
            'is_published' => true,
        ]);
        Page::query()->create([
            'title' => 'Направления',
            'slug' => 'destinations',
            'content' => [],
            'is_published' => true,
        ]);

        app(GooglePlaces::class)->update([
            'enabled' => true,
            'language' => 'ru',
            'browser_api_key' => 'browser-key-visible-on-home',
            'server_api_key' => $serverKey,
        ]);

        $home = $this->get('/');

        $home->assertOk();
        $home->assertSeeInOrder(['class="header"', 'data-transfer-form', 'id="how"'], false);
        $home->assertSee('data-trip="return"', false);
        $home->assertSee('Туда и обратно', false);
        $home->assertSee('В одну сторону', false);
        $home->assertSee('Откуда', false);
        $home->assertSee('Куда', false);
        $home->assertSee('Обратно', false);
        $home->assertSee('Дата обратной поездки', false);
        $home->assertSee('Чемоданы и сумки', false);
        $home->assertSee('Ручная кладь', false);
        $home->assertSee('Подача заявки бесплатна', false);
        $home->assertDontSee('value="12:00"', false);
        $home->assertDontSee('Аэропорт Барселоны', false);
        $home->assertSee('browser-key-visible-on-home', false);
        $home->assertDontSee($serverKey, false);
        $home->assertSee('transfer-search.js', false);
        $home->assertSee('theme-switch', false);
        $home->assertSee('Светлая тема', false);
        $home->assertSee('Тёмная тема', false);
        $home->assertSee('transfer-theme', false);

        $this->get('/destinations')
            ->assertOk()
            ->assertSee('theme-switch', false)
            ->assertDontSee('transfer-search.js', false)
            ->assertDontSee($serverKey, false);
    }

    public function test_missing_key_keeps_the_form_and_explains_why_search_is_off(): void
    {
        Page::query()->create([
            'title' => 'Главная',
            'slug' => 'home',
            'content' => [],
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Автоподсказки адресов сейчас выключены', false)
            ->assertSee('data-transfer-form', false);
    }

    public function test_admin_page_is_available_to_an_administrator(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/google-places')
            ->assertOk()
            ->assertSee('Places API (New)', false)
            ->assertSee('67transfer.local', false);
    }
}
