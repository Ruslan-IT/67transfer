<?php

namespace Tests\Feature;

use App\Models\LegalDocument;
use App\Models\Page;
use App\Models\User;
use App\Support\SiteFooter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteFooterTest extends TestCase
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

        Page::query()->create([
            'title' => 'Контакты',
            'slug' => 'contacts',
            'content' => [
                'phone' => '+34 600 000 000',
                'email' => 'hello@transferpoint.eu',
                'whatsapp' => '+34600000000',
                'telegram' => 'transferpoint',
                'address' => 'Barcelona, Catalonia, Spain',
            ],
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Информация',
            'slug' => 'information',
            'content' => [],
            'is_published' => true,
        ]);
    }

    public function test_home_footer_renders_defaults_and_keeps_system_actions(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('home-footer', $html);
        $this->assertStringContainsString('+34 600 000 000', $html);
        $this->assertStringContainsString('tel:+34600000000', $html);
        $this->assertStringContainsString('mailto:hello@transferpoint.eu', $html);
        $this->assertStringContainsString('https://wa.me/34600000000', $html);
        $this->assertStringContainsString('https://t.me/transferpoint', $html);
        $this->assertStringContainsString('images/icons/messengers/whatsapp.svg', $html);
        $this->assertStringContainsString('images/icons/messengers/telegram.svg', $html);
        $this->assertStringContainsString(route('legal.privacy'), $html);
        $this->assertStringContainsString(route('legal.consent'), $html);
        $this->assertStringContainsString('data-cookie-open', $html);
        $this->assertFileExists(public_path('images/icons/messengers/whatsapp.svg'));
        $this->assertFileExists(public_path('images/icons/messengers/telegram.svg'));
    }

    public function test_saved_footer_settings_replace_contacts_and_hide_disabled_items(): void
    {
        app(SiteFooter::class)->update([
            'contact_label' => 'Контакты сервиса',
            'phone' => [
                'enabled' => true,
                'display' => '+34 111 222 333',
                'tel' => '+34111222333',
            ],
            'phone_extra' => [
                'enabled' => false,
                'display' => '+34 999',
                'tel' => '+34999',
            ],
            'email' => [
                'enabled' => false,
                'display' => 'hidden@example.com',
                'mailto' => 'hidden@example.com',
            ],
            'whatsapp' => [
                'enabled' => true,
                'label' => 'Написать в WhatsApp',
                'phone' => '34611111111',
                'url' => '',
            ],
            'telegram' => [
                'enabled' => false,
                'label' => 'Telegram',
                'username' => 'hiddenbot',
                'url' => '',
            ],
            'socials' => [
                [
                    'name' => 'Instagram',
                    'url' => 'https://instagram.com/transfer',
                    'enabled' => true,
                ],
                [
                    'name' => 'Hidden',
                    'url' => 'https://example.com/hidden',
                    'enabled' => false,
                ],
            ],
            'legal' => [
                'privacy_label' => 'Наша политика',
                'consent_label' => 'Наше согласие',
                'cookie_label' => 'Cookie здесь',
            ],
            'copyright' => '© {year} Test Brand',
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Контакты сервиса', $html);
        $this->assertStringContainsString('+34 111 222 333', $html);
        $this->assertStringContainsString('tel:+34111222333', $html);
        $this->assertStringContainsString('Написать в WhatsApp', $html);
        $this->assertStringContainsString('https://wa.me/34611111111', $html);
        $this->assertStringContainsString('Instagram', $html);
        $this->assertStringContainsString('https://instagram.com/transfer', $html);
        $this->assertStringContainsString('Наша политика', $html);
        $this->assertStringContainsString('Cookie здесь', $html);
        $this->assertStringContainsString('© '.date('Y').' Test Brand', $html);

        $this->assertStringNotContainsString('hidden@example.com', $html);
        $this->assertStringNotContainsString('+34 999', $html);
        $this->assertStringNotContainsString('t.me/hiddenbot', $html);
        $this->assertStringNotContainsString('example.com/hidden', $html);
        $this->assertStringContainsString('data-cookie-open', $html);
    }

    public function test_administrator_can_open_footer_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/footer-settings')
            ->assertOk()
            ->assertSee('Настройки футера', false)
            ->assertSee('WhatsApp', false)
            ->assertSee('Telegram', false);
    }

    public function test_shared_footer_is_used_on_inner_pages(): void
    {
        LegalDocument::ensureDefaults();

        foreach ([
            route('legal.consent'),
            route('legal.privacy'),
            route('transfer.request'),
            route('contacts'),
            route('information'),
        ] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('home-footer', $html, "Missing home-footer on {$url}");
            $this->assertStringContainsString('css/home-footer.css', $html, "Missing home-footer.css on {$url}");
            $this->assertStringContainsString('data-cookie-open', $html, "Missing cookie button on {$url}");
            $this->assertStringNotContainsString('class="footer"', $html, "Old footer still present on {$url}");
            $this->assertSame(1, substr_count($html, 'class="home-footer"'), "Duplicate home-footer on {$url}");
        }
    }
}
