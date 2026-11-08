<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Enquiry;
use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use App\Models\Page;
use App\Models\Setting;
use App\Support\CookieConsent;
use App\Support\LegalDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalAndCookieTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_footer_and_cookie_banner_are_public(): void
    {
        Page::query()->create([
            'title' => 'Главная',
            'slug' => 'home',
            'content' => [],
            'is_published' => true,
        ]);

        $home = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Принять все', $home);
        $this->assertStringContainsString('Отклонить необязательные', $home);
        $this->assertStringContainsString('Настроить', $home);
        $this->assertStringContainsString('Настройки cookie', $home);
        $this->assertStringContainsString(route('legal.privacy'), $home);
        $this->assertStringContainsString(route('legal.consent'), $home);
        $this->assertStringNotContainsString('mc.yandex.ru/metrika/tag.js', $home);
        $this->assertStringNotContainsString('mc.yandex.ru/watch/', $home);

        $privacy = $this->get(route('legal.privacy'))->assertOk()->getContent();
        $this->assertStringContainsString('Политика обработки персональных данных', $privacy);
        $this->assertStringContainsString('ещё не опубликовал', $privacy);
        $this->assertStringContainsString('Роскомнадзор', $privacy);

        $consent = $this->get(route('legal.consent'))->assertOk()->getContent();
        $this->assertStringContainsString('Согласие на обработку персональных данных', $consent);
        $this->assertStringNotContainsString('Согласен с политикой', $consent);
    }

    public function test_new_consent_version_does_not_replace_an_accepted_text(): void
    {
        $first = LegalDocument::consentVersion();
        $this->assertNotNull($first);

        Enquiry::query()->create([
            'kind' => 'contact',
            'status' => 'new',
            'name' => 'Анна',
            'phone' => null,
            'email' => 'anna@example.com',
            'comment' => 'Вопрос',
            'payload' => ['message' => 'Вопрос'],
            'consent_version_id' => $first->id,
            'consented_at' => now(),
            'fingerprint' => hash('sha256', 'one'),
        ]);

        $document = LegalDocument::query()->where('key', LegalDocuments::CONSENT)->firstOrFail();
        $document->saveRevision([
            'title' => $document->title,
            'slug' => $document->slug,
            'body' => $document->body.'<p>Новая редакция.</p>',
            'is_published' => true,
        ]);

        $accepted = LegalDocumentVersion::query()->findOrFail($first->id);
        $this->assertSame($first->body, $accepted->body);
        $this->assertSame(1, $accepted->version);
        $this->assertSame(2, $document->fresh()->version);
        $this->assertSame($first->id, Enquiry::query()->firstOrFail()->consent_version_id);
    }

    public function test_legal_seo_fields_appear_in_page_head_without_bumping_consent_version(): void
    {
        LegalDocument::ensureDefaults();

        $privacy = LegalDocument::query()->where('key', LegalDocuments::PRIVACY)->firstOrFail();
        $consent = LegalDocument::query()->where('key', LegalDocuments::CONSENT)->firstOrFail();
        $versionBefore = $consent->version;

        $privacy->saveRevision([
            'title' => $privacy->title,
            'slug' => $privacy->slug,
            'body' => $privacy->body,
            'is_published' => true,
            'meta_title' => 'SEO политика',
            'meta_description' => 'Описание политики для поисковиков.',
        ]);

        $consent->saveRevision([
            'title' => $consent->title,
            'slug' => $consent->slug,
            'body' => $consent->body,
            'is_published' => true,
            'meta_title' => 'SEO согласие',
            'meta_description' => 'Описание согласия для поисковиков.',
        ]);

        $this->assertSame($versionBefore, $consent->fresh()->version);

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('<title>SEO политика</title>', false)
            ->assertSee('Описание политики для поисковиков.', false)
            ->assertSee('Политика обработки персональных данных', false);

        $this->get(route('legal.consent'))
            ->assertOk()
            ->assertSee('<title>SEO согласие</title>', false)
            ->assertSee('Описание согласия для поисковиков.', false);
    }

    public function test_contact_and_destination_forms_require_consent(): void
    {
        $this->postJson(route('contacts.request'), [
            'name' => 'Анна',
            'contact' => 'anna@example.com',
            'message' => 'Когда подача?',
        ])->assertStatus(422);

        $this->assertDatabaseCount('enquiries', 0);

        $this->postJson(route('contacts.request'), [
            'name' => 'Анна',
            'contact' => 'anna@example.com',
            'message' => 'Когда подача?',
            'personal_data_consent' => '1',
        ])->assertOk();

        $this->assertDatabaseHas('enquiries', [
            'kind' => 'contact',
            'email' => 'anna@example.com',
        ]);

        $destination = Destination::query()->create([
            'name' => 'Рим',
            'slug' => 'rome',
            'title' => 'Рим',
            'content' => '<p>Город</p>',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->post(route('destination.request', $destination), [
            'from' => 'Аэропорт',
            'to' => 'Отель',
            'date' => now()->addDay()->toDateString(),
            'email' => 'anna@example.com',
        ])->assertSessionHasErrors('personal_data_consent');

        $this->post(route('destination.request', $destination), [
            'from' => 'Аэропорт',
            'to' => 'Отель',
            'date' => now()->addDay()->toDateString(),
            'email' => 'anna@example.com',
            'personal_data_consent' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('enquiries', [
            'kind' => 'destination',
            'email' => 'anna@example.com',
        ]);
    }

    public function test_forms_show_separate_consent_links(): void
    {
        $destination = Destination::query()->create([
            'name' => 'Рим',
            'slug' => 'rome',
            'title' => 'Рим',
            'content' => '<p>Город</p>',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $html = $this->get(route('destination', $destination))->assertOk()->getContent();
        $this->assertStringContainsString('name="personal_data_consent"', $html);
        $this->assertStringContainsString(route('legal.consent'), $html);
        $this->assertStringContainsString(route('legal.privacy'), $html);
        $this->assertMatchesRegularExpression('/name="personal_data_consent"(?![^>]*\bchecked\b)/', $html);
    }

    public function test_metrika_stays_disabled_until_counter_and_consent_are_ready(): void
    {
        Page::query()->create([
            'title' => 'Главная',
            'slug' => 'home',
            'content' => [],
            'is_published' => true,
        ]);

        $consent = app(CookieConsent::class);

        $empty = $consent->publicConfig();
        $this->assertFalse($empty['analytics']['enabled']);
        $this->assertSame('', $empty['analytics']['counterId']);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('id="cookie-consent-config"', $html);
        $this->assertStringContainsString('cookie-consent.js', $html);
        $this->assertStringNotContainsString('mc.yandex.ru/metrika/tag.js', $html);
        $this->assertStringContainsString('data-cookie-panel', $html);
        $this->assertStringContainsString('cookie-panel__dialog', $html);

        Setting::put('analytics.enabled', '1');
        Setting::put('analytics.counter_id', '12345678');

        $ready = app(CookieConsent::class)->publicConfig();
        $this->assertTrue($ready['analytics']['enabled']);
        $this->assertSame('12345678', $ready['analytics']['counterId']);

        Setting::put('analytics.enabled', '0');

        $disabled = app(CookieConsent::class)->publicConfig();
        $this->assertFalse($disabled['analytics']['enabled']);
        $this->assertSame('', $disabled['analytics']['counterId']);
    }
}
