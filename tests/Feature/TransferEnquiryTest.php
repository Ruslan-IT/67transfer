<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Tests\TestCase;

class TransferEnquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.to.address' => 'desk@example.com']);
    }

    public function test_home_form_starts_as_an_empty_round_trip(): void
    {
        Page::query()->create([
            'title' => 'Главная',
            'slug' => 'home',
            'content' => [],
            'is_published' => true,
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-trip="return"', $html);
        $this->assertStringContainsString('value="return" checked', $html);
        $this->assertStringContainsString('Укажите пассажиров', $html);
        $this->assertStringContainsString('Укажите багаж', $html);
        $this->assertStringContainsString('Изменить маршрут', $html);
        $this->assertStringContainsString('Изменить пассажиров и багаж обратно', $html);
        $this->assertStringContainsString('Детские кресла', $html);
        $this->assertStringContainsString('Автолюлька', $html);
        $this->assertStringContainsString('Кресло-коляска', $html);
        $this->assertStringContainsString('Особые пожелания', $html);
        $this->assertStringContainsString('Продолжить', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*data-continue[^>]*>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*data-continue[^>]*\sdisabled(?:[\s=]|>)/', $html);
        $this->assertStringContainsString(route('transfer.continue'), $html);
        $this->assertStringContainsString('name="personal_data_consent"', $html);
        $this->assertStringNotContainsString('Отправить заявку', $html);
        $this->assertStringContainsString(route('transfer.enquiry'), $html);
        $this->assertStringNotContainsString('Аэропорт Барселоны', $html);
        $this->assertStringNotContainsString('Отель в Салоу', $html);
        $this->assertStringNotContainsString('value="12:00"', $html);
        $this->assertStringNotContainsString('value="12:30"', $html);

        $script = file_get_contents(public_path('js/transfer-search.js'));
        $this->assertIsString($script);
        $this->assertStringContainsString('if (submitting)', $script);
        $this->assertStringContainsString('function routeGateReady', $script);
        $this->assertStringContainsString('function refreshContinue', $script);
        $this->assertStringContainsString('dataset.continueUrl', $script);
        $this->assertStringContainsString('Без багажа', $script);
        $this->assertStringContainsString('Местное время аэропорта', $script);
        $this->assertStringContainsString('Укажите адрес.', $script);
        $this->assertStringNotContainsString('место нельзя подтвердить', $script);
        $this->assertStringNotContainsString('Аэропорт Барселоны', $script);
        $this->assertStringContainsString('Адрес можно ввести вручную', $html);
        $this->assertStringNotContainsString('mc.yandex.ru', $html);
    }

    public function test_round_trip_is_accepted_and_keeps_both_legs(): void
    {
        $outbound = now()->addDays(14)->toDateString();
        $return = now()->addDays(20)->toDateString();

        $this->postJson(route('transfer.enquiry'), $this->payload($outbound, $return))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $body = $this->sentMailText();
        $this->assertStringContainsString('Туда и обратно', $body);
        $this->assertStringContainsString('Поездка туда', $body);
        $this->assertStringContainsString('Обратная поездка', $body);
        $this->assertStringContainsString('Аэропорт', $body);
        $this->assertStringContainsString('Отель', $body);
        $this->assertStringContainsString('Время прилёта: 12:30', $body);
        $this->assertStringContainsString('Время подачи: 10:00', $body);
        $this->assertStringContainsString('Всего пассажирских мест: 4', $body);
        $this->assertStringContainsString('автолюлька 1', $body);
        $this->assertStringContainsString('согласовывается при обработке заявки', $body);
        $this->assertStringContainsString('Ручная кладь: 2', $body);
        $this->assertStringContainsString('Детские коляски: 1', $body);
        $this->assertStringContainsString('Рейс: IB123', $body);
        $this->assertStringContainsString('Питомец: кот в переноске', $body);
    }

    public function test_one_way_request_does_not_include_the_return_leg(): void
    {
        $outbound = now()->addDays(10)->toDateString();
        $payload = $this->payload($outbound, now()->addDays(12)->toDateString());
        $payload['trip'] = 'oneway';
        $payload['outbound']['pickup'] = $this->place('city-1', 'Город', false);
        $payload['contact']['flight_number'] = null;
        $payload['contact']['return_flight_number'] = 'SHOULD-NOT-APPEAR';

        $this->postJson(route('transfer.enquiry'), $payload)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $body = $this->sentMailText();
        $this->assertStringContainsString('В одну сторону', $body);
        $this->assertStringNotContainsString('Обратная поездка', $body);
        $this->assertStringNotContainsString('SHOULD-NOT-APPEAR', $body);
        $this->assertStringContainsString('Время подачи:', $body);
    }

    public function test_return_must_be_later_by_full_date_and_time(): void
    {
        $day = now()->addDays(10)->toDateString();
        $earlier = $this->payload($day, $day);
        $earlier['return']['time'] = '09:00';
        $earlier['outbound']['time'] = '15:00';

        $this->postJson(route('transfer.enquiry'), $earlier)
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->assertCount(0, $this->messages());

        $sameClockNextDay = $this->payload($day, now()->addDays(11)->toDateString());
        $sameClockNextDay['outbound']['time'] = '15:00';
        $sameClockNextDay['return']['time'] = '08:00';

        $this->postJson(route('transfer.enquiry'), $sameClockNextDay)->assertOk();
    }

    public function test_manual_address_without_google_place_is_accepted(): void
    {
        $payload = $this->payload(now()->addDays(5)->toDateString(), now()->addDays(6)->toDateString());

        foreach (['outbound', 'return'] as $leg) {
            foreach (['pickup', 'dropoff'] as $point) {
                $payload[$leg][$point]['place_id'] = '';
                $payload[$leg][$point]['latitude'] = null;
                $payload[$leg][$point]['longitude'] = null;
            }
        }

        $this->postJson(route('transfer.enquiry'), $payload)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertStringContainsString('Аэропорт', $this->sentMailText());
        $this->assertDatabaseCount('enquiries', 1);
    }

    public function test_empty_address_is_rejected(): void
    {
        $payload = $this->payload(now()->addDays(5)->toDateString(), now()->addDays(6)->toDateString());
        $payload['outbound']['pickup']['formatted_address'] = '';
        $payload['outbound']['pickup']['place_id'] = '';
        $payload['outbound']['pickup']['latitude'] = null;
        $payload['outbound']['pickup']['longitude'] = null;

        $this->postJson(route('transfer.enquiry'), $payload)
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->assertCount(0, $this->messages());
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_repeat_submission_does_not_create_a_duplicate(): void
    {
        $payload = $this->payload(now()->addDays(9)->toDateString(), now()->addDays(12)->toDateString());

        $this->postJson(route('transfer.enquiry'), $payload)->assertOk();
        $this->postJson(route('transfer.enquiry'), $payload)->assertOk();

        $this->assertDatabaseCount('enquiries', 1);
        $this->assertCount(1, $this->messages());
    }

    public function test_children_need_ages_and_infants_count_as_passengers(): void
    {
        $payload = $this->payload(now()->addDays(5)->toDateString(), now()->addDays(6)->toDateString());
        $payload['outbound']['passengers']['children'][0]['age_years'] = null;
        $payload['return']['passengers']['children'][0]['age_years'] = null;

        $this->postJson(route('transfer.enquiry'), $payload)->assertStatus(422);

        $infantOnly = $this->payload(now()->addDays(7)->toDateString(), now()->addDays(8)->toDateString());
        $infantOnly['outbound']['pickup'] = $this->place('hotel-2', 'Отель', false);
        $infantOnly['return']['dropoff'] = $infantOnly['outbound']['pickup'];
        $infantOnly['outbound']['passengers']['adults'] = 0;
        $infantOnly['outbound']['passengers']['children'] = [];
        $infantOnly['outbound']['passengers']['infants'] = [['age_months' => 3, 'birth_date' => null]];
        $infantOnly['return']['passengers'] = $infantOnly['outbound']['passengers'];
        $infantOnly['contact']['flight_number'] = null;

        $this->postJson(route('transfer.enquiry'), $infantOnly)->assertOk();
        $this->assertStringContainsString('Всего пассажирских мест: 1', $this->sentMailText());
    }

    public function test_explicit_empty_luggage_is_stored_as_no_luggage(): void
    {
        $payload = $this->payload(now()->addDays(4)->toDateString(), now()->addDays(5)->toDateString());

        foreach (['suitcases', 'cabin', 'strollers', 'sports', 'wheelchairs', 'other'] as $key) {
            $payload['outbound']['luggage'][$key] = 0;
            $payload['return']['luggage'][$key] = 0;
        }

        $this->postJson(route('transfer.enquiry'), $payload)->assertOk();
        $this->assertStringContainsString('Багаж: без багажа', $this->sentMailText());
    }

    public function test_unconfirmed_passengers_and_missing_contact_are_rejected(): void
    {
        $payload = $this->payload(now()->addDays(4)->toDateString(), now()->addDays(5)->toDateString());
        $payload['outbound']['passengers']['confirmed'] = false;
        $payload['contact']['name'] = '';

        $response = $this->postJson(route('transfer.enquiry'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('ok', false);
        $this->assertCount(0, $this->messages());
    }

    public function test_continue_rejects_an_unselected_place_and_an_earlier_return(): void
    {
        $outbound = now()->addDays(5)->toDateString();
        $payload = $this->tripPayload($outbound, now()->addDays(6)->toDateString());
        $payload['outbound']['pickup']['place_id'] = '';
        $payload['outbound']['pickup']['formatted_address'] = '';
        $payload['outbound']['pickup']['display_name'] = '';
        $payload['outbound']['pickup']['latitude'] = null;
        $payload['outbound']['pickup']['longitude'] = null;

        $this->postJson(route('transfer.continue'), $payload)
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->get(route('transfer.request'))
            ->assertOk()
            ->assertSee('Сначала укажите маршрут и параметры поездки.');

        $earlier = $this->tripPayload($outbound, now()->addDays(4)->toDateString());

        $this->postJson(route('transfer.continue'), $earlier)->assertStatus(422);

        $sameDay = $this->tripPayload($outbound, $outbound);
        $sameDay['outbound']['time'] = '15:00';
        $sameDay['return']['time'] = '09:00';

        $this->postJson(route('transfer.continue'), $sameDay)->assertStatus(422);
        $this->assertCount(0, $this->messages());
    }

    public function test_one_way_continue_does_not_require_a_return_date(): void
    {
        $payload = $this->tripPayload(now()->addDays(8)->toDateString(), now()->addDays(9)->toDateString());
        $payload['trip'] = 'oneway';
        $payload['outbound']['pickup'] = $this->place('city-1', 'Город', false);
        unset($payload['return']);

        $this->postJson(route('transfer.continue'), $payload)
            ->assertOk()
            ->assertJsonPath('redirect', route('transfer.request'));

        $this->get(route('transfer.request'))
            ->assertOk()
            ->assertSee('В одну сторону')
            ->assertSee('Город')
            ->assertSee('Как с вами связаться?')
            ->assertSee('Шаг 2 из 2 · Контакты')
            ->assertSee('Отправить заявку')
            ->assertSee('WhatsApp')
            ->assertSee('Telegram')
            ->assertDontSee('Дата обратной поездки')
            ->assertDontSee('Номер рейса')
            ->assertDontSee('E-mail')
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="comment"', false)
            ->assertSee('Назад');
    }

    public function test_request_page_shows_the_selected_trip_and_keeps_it_on_validation_error(): void
    {
        $outbound = now()->addDays(14)->toDateString();
        $return = now()->addDays(20)->toDateString();

        $this->postJson(route('transfer.continue'), $this->tripPayload($outbound, $return))
            ->assertOk();

        $page = $this->get(route('transfer.request'));
        $page->assertOk();
        $html = $page->getContent();
        $page->assertSee('Аэропорт');
        $page->assertSee('Отель');
        $page->assertSee('Туда и обратно');
        $page->assertSee('12:30');
        $page->assertSee('10:00');
        $page->assertSee('Время прилёта');
        $page->assertSee('2 взрослых');
        $page->assertSee('кот в переноске');
        $page->assertSee('Номер рейса');
        $page->assertDontSee('Комментарий к пустому полю', false);
        $routePos = strpos($html, 'transfer-request__route');
        $cardPos = strpos($html, 'transfer-request__card');
        $this->assertNotFalse($routePos);
        $this->assertNotFalse($cardPos);
        $this->assertLessThan($cardPos, $routePos);
        $this->assertStringContainsString('transfer-request__route-grid', $html);

        $this->post(route('transfer.request.store'), [
            'name' => '',
            'phone' => '123',
            'outbound' => ['pickup' => ['formatted_address' => 'ПОДМЕНА']],
        ])->assertRedirect(route('transfer.request'))
            ->assertSessionHasErrors(['name', 'phone', 'messenger', 'flight_number'])
            ->assertSessionDoesntHaveErrors(['email']);

        $this->assertCount(0, $this->messages());

        $this->get(route('transfer.request'))
            ->assertOk()
            ->assertSee('Аэропорт')
            ->assertSee('123')
            ->assertSee('Укажите имя.')
            ->assertSee('Выберите WhatsApp или Telegram.')
            ->assertSee('Проверьте поля с сообщениями')
            ->assertDontSee('Укажите email.')
            ->assertDontSee('ПОДМЕНА');
    }

    public function test_request_store_sends_the_existing_enquiry_and_ignores_posted_trip_fields(): void
    {
        $outbound = now()->addDays(14)->toDateString();
        $return = now()->addDays(20)->toDateString();
        $this->postJson(route('transfer.continue'), $this->tripPayload($outbound, $return))->assertOk();

        $this->post(route('transfer.request.store'), [
            'name' => 'Анна',
            'phone' => '+34 600 000 000',
            'messenger' => 'WhatsApp',
            'flight_number' => 'IB123',
            'outbound' => ['pickup' => ['formatted_address' => 'ПОДМЕНА']],
        ])->assertRedirect(route('transfer.request'));

        $body = $this->sentMailText();
        $this->assertStringContainsString('Туда и обратно', $body);
        $this->assertStringContainsString('Аэропорт', $body);
        $this->assertStringContainsString('Телефон: +34 600 000 000', $body);
        $this->assertStringContainsString('Мессенджер: WhatsApp', $body);
        $this->assertStringNotContainsString('Email:', $body);
        $this->assertStringContainsString('Рейс: IB123', $body);
        $this->assertStringContainsString('Питомец: кот в переноске', $body);
        $this->assertStringNotContainsString('ПОДМЕНА', $body);

        $this->assertDatabaseHas('enquiries', [
            'kind' => 'transfer',
            'name' => 'Анна',
            'phone' => '+34 600 000 000',
            'email' => null,
            'messenger' => 'WhatsApp',
        ]);

        $enquiry = \App\Models\Enquiry::query()->firstOrFail();
        $this->assertNotNull($enquiry->consented_at);
        $this->assertNotNull($enquiry->consent_version_id);
        $this->assertStringContainsString('Мессенджер: WhatsApp', $enquiry->detailsText());
        $this->assertSame('Аэропорт', $enquiry->payload['outbound']['pickup']['formatted_address'] ?? null);
        $this->assertSame('Отель', $enquiry->payload['outbound']['dropoff']['formatted_address'] ?? null);
        $this->assertSame('12:30', $enquiry->payload['outbound']['time'] ?? null);
        $this->assertSame(2, $enquiry->payload['outbound']['passengers']['adults'] ?? null);
        $this->assertSame(2, $enquiry->payload['outbound']['luggage']['suitcases'] ?? null);
        $this->assertSame('return', $enquiry->payload['trip'] ?? null);
        $this->assertSame('10:00', $enquiry->payload['return']['time'] ?? null);

        $resource = file_get_contents(app_path('Filament/Resources/Enquiries/EnquiryResource.php'));
        $this->assertIsString($resource);
        $this->assertStringContainsString("make('messenger')", $resource);

        $this->get(route('transfer.request'))
            ->assertOk()
            ->assertSee('Заявка успешно отправлена')
            ->assertDontSee('Аэропорт');
    }

    public function test_request_store_keeps_enquiry_when_mail_fails(): void
    {
        $outbound = now()->addDays(14)->toDateString();
        $return = now()->addDays(20)->toDateString();
        $this->postJson(route('transfer.continue'), $this->tripPayload($outbound, $return))->assertOk();

        \Illuminate\Support\Facades\Mail::shouldReceive('raw')
            ->once()
            ->andThrow(new \RuntimeException('SMTP unavailable'));

        $this->post(route('transfer.request.store'), [
            'name' => 'Мария',
            'phone' => '+34 600 333 444',
            'messenger' => 'Telegram',
            'flight_number' => 'VY100',
        ])->assertRedirect(route('transfer.request'))
            ->assertSessionHas('status', 'Заявка успешно отправлена. Мы свяжемся с вами в ближайшее время.')
            ->assertSessionHas('status_mail_sent', true);

        $this->assertDatabaseHas('enquiries', [
            'kind' => 'transfer',
            'name' => 'Мария',
            'phone' => '+34 600 333 444',
            'messenger' => 'Telegram',
        ]);

        $this->get(route('transfer.request'))
            ->assertOk()
            ->assertSee('Заявка успешно отправлена')
            ->assertDontSee('Мария');
    }

    public function test_request_store_keeps_enquiry_when_mail_recipient_is_missing(): void
    {
        config(['mail.to.address' => null]);

        $outbound = now()->addDays(15)->toDateString();
        $return = now()->addDays(21)->toDateString();
        $this->postJson(route('transfer.continue'), $this->tripPayload($outbound, $return))->assertOk();

        $this->post(route('transfer.request.store'), [
            'name' => 'Пётр',
            'phone' => '+34 600 555 666',
            'messenger' => 'WhatsApp',
            'flight_number' => 'SU200',
        ])->assertRedirect(route('transfer.request'))
            ->assertSessionHas('status_mail_sent', false);

        $this->assertDatabaseHas('enquiries', [
            'name' => 'Пётр',
            'phone' => '+34 600 555 666',
            'messenger' => 'WhatsApp',
        ]);
        $this->assertCount(0, $this->messages());
    }

    public function test_request_store_honeypot_does_not_claim_success_or_save(): void
    {
        $outbound = now()->addDays(14)->toDateString();
        $return = now()->addDays(20)->toDateString();
        $this->postJson(route('transfer.continue'), $this->tripPayload($outbound, $return))->assertOk();

        $this->post(route('transfer.request.store'), [
            'name' => 'Бот',
            'phone' => '+34 600 111 222',
            'messenger' => 'WhatsApp',
            'flight_number' => 'IB999',
            'website_confirm' => 'https://spam.example',
        ])->assertRedirect(route('transfer.request'))
            ->assertSessionMissing('status')
            ->assertSessionMissing('status_mail_sent');

        $this->assertDatabaseCount('enquiries', 0);
        $this->assertCount(0, $this->messages());
    }

    public function test_request_store_legacy_honeypot_field_does_not_claim_success_or_save(): void
    {
        $outbound = now()->addDays(14)->toDateString();
        $return = now()->addDays(20)->toDateString();
        $this->postJson(route('transfer.continue'), $this->tripPayload($outbound, $return))->assertOk();

        $this->post(route('transfer.request.store'), [
            'name' => 'Автозаполнение',
            'phone' => '+34 600 777 888',
            'messenger' => 'Telegram',
            'flight_number' => 'SU300',
            'company_website' => 'https://autofill.example',
        ])->assertRedirect(route('transfer.request'))
            ->assertSessionMissing('status');

        $this->assertDatabaseCount('enquiries', 0);
        $this->assertCount(0, $this->messages());
    }

    public function test_request_store_reports_error_when_persist_fails(): void
    {
        $outbound = now()->addDays(14)->toDateString();
        $return = now()->addDays(20)->toDateString();
        $this->postJson(route('transfer.continue'), $this->tripPayload($outbound, $return))->assertOk();

        $this->mock(\App\Support\SiteEnquiry::class, function ($mock): void {
            $mock->shouldReceive('storeTransfer')
                ->once()
                ->andThrow(new \RuntimeException('persist failed'));
        });

        $this->post(route('transfer.request.store'), [
            'name' => 'Ольга',
            'phone' => '+34 600 444 555',
            'messenger' => 'WhatsApp',
            'flight_number' => 'VY400',
        ])->assertRedirect(route('transfer.request'))
            ->assertSessionHas('transfer_error', 'Не удалось сохранить заявку. Попробуйте ещё раз.')
            ->assertSessionMissing('status');

        $this->assertDatabaseCount('enquiries', 0);
        $this->assertCount(0, $this->messages());
    }

    public function test_request_form_uses_safe_honeypot_field_name(): void
    {
        $outbound = now()->addDays(14)->toDateString();
        $return = now()->addDays(20)->toDateString();
        $this->postJson(route('transfer.continue'), $this->tripPayload($outbound, $return))->assertOk();

        $this->get(route('transfer.request'))
            ->assertOk()
            ->assertSee('name="website_confirm"', false)
            ->assertDontSee('name="company_website"', false)
            ->assertDontSee('>Сайт<', false);
    }

    public function test_json_enquiry_reports_mail_failure_without_dropping_record(): void
    {
        \Illuminate\Support\Facades\Mail::shouldReceive('raw')
            ->once()
            ->andThrow(new \RuntimeException('SMTP auth failed'));

        $payload = $this->payload(now()->addDays(11)->toDateString(), now()->addDays(13)->toDateString());

        $this->postJson(route('transfer.enquiry'), $payload)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('mail_sent', true)
            ->assertJsonPath(
                'message',
                'Заявка успешно отправлена. Мы свяжемся с вами в ближайшее время.'
            );

        $this->assertDatabaseCount('enquiries', 1);
    }

    public function test_request_page_submit_script_posts_via_native_form_submit(): void
    {
        $script = file_get_contents(resource_path('views/pages/transfer-request.blade.php'));
        $this->assertIsString($script);
        $this->assertStringContainsString('HTMLFormElement.prototype.submit.call(form)', $script);
        $this->assertStringContainsString('event.preventDefault()', $script);
        $this->assertStringNotContainsString('queueMicrotask', $script);
    }

    public function test_continue_requires_personal_data_consent(): void
    {
        $payload = $this->tripPayload(now()->addDays(8)->toDateString(), now()->addDays(9)->toDateString());
        unset($payload['personal_data_consent']);

        $this->postJson(route('transfer.continue'), $payload)
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->assertCount(0, $this->messages());
        $this->get(route('transfer.request'))
            ->assertOk()
            ->assertSee('Сначала укажите маршрут и параметры поездки.');
    }

    public function test_request_store_saves_telegram_without_email(): void
    {
        $outbound = now()->addDays(14)->toDateString();
        $return = now()->addDays(20)->toDateString();
        $this->postJson(route('transfer.continue'), $this->tripPayload($outbound, $return))->assertOk();

        $this->post(route('transfer.request.store'), [
            'name' => 'Иван',
            'phone' => '+79001234567',
            'messenger' => 'Telegram',
            'flight_number' => 'SU100',
            'email' => '',
        ])->assertRedirect(route('transfer.request'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('enquiries', [
            'name' => 'Иван',
            'phone' => '+79001234567',
            'email' => null,
            'messenger' => 'Telegram',
        ]);

        $this->assertStringContainsString('Мессенджер: Telegram', $this->sentMailText());
        $this->assertStringNotContainsString('Email:', $this->sentMailText());
    }

    public function test_request_page_without_a_draft_asks_to_return_to_the_form(): void
    {
        $this->get(route('transfer.request'))
            ->assertOk()
            ->assertSee('Сначала укажите маршрут и параметры поездки.')
            ->assertSee('Перейти к форме');

        $this->post(route('transfer.request.store'), [
            'name' => 'Анна',
            'phone' => '+34111222333',
            'messenger' => 'WhatsApp',
        ])->assertRedirect(route('transfer.request'));

        $this->assertCount(0, $this->messages());
    }

    /**
     * @return array<string, mixed>
     */
    private function tripPayload(string $outboundDate, string $returnDate): array
    {
        $payload = $this->payload($outboundDate, $returnDate);
        unset($payload['contact']);
        $payload['personal_data_consent'] = true;

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $outboundDate, string $returnDate): array
    {
        $passengers = [
            'confirmed' => true,
            'adults' => 2,
            'children' => [
                ['age_years' => 5, 'birth_date' => null],
            ],
            'infants' => [
                ['age_months' => 8, 'birth_date' => null],
            ],
            'seats' => [
                'carrier' => 1,
                'child' => 0,
                'booster' => 0,
                'own' => 0,
                'height_cm' => 70,
                'weight_kg' => 8,
            ],
        ];
        $luggage = [
            'confirmed' => true,
            'suitcases' => 2,
            'suitcase_size' => 'regular',
            'suitcase_dimensions' => '',
            'cabin' => 2,
            'strollers' => 1,
            'stroller_folds' => 'yes',
            'stroller_dimensions' => '60x40',
            'sports' => 0,
            'sports_type' => '',
            'sports_dimensions' => '',
            'wheelchairs' => 0,
            'wheelchair_folds' => null,
            'wheelchair_ride' => null,
            'wheelchair_dimensions' => '',
            'other' => 0,
            'other_description' => '',
            'other_dimensions' => '',
        ];
        $airport = $this->place('airport-1', 'Аэропорт', true);
        $hotel = $this->place('hotel-1', 'Отель', false);

        return [
            'trip' => 'return',
            'outbound' => [
                'pickup' => $airport,
                'dropoff' => $hotel,
                'date' => $outboundDate,
                'time' => '12:30',
                'passengers' => $passengers,
                'luggage' => $luggage,
            ],
            'return' => [
                'pickup' => $hotel,
                'dropoff' => $airport,
                'date' => $returnDate,
                'time' => '10:00',
                'route_custom' => false,
                'party_custom' => false,
                'passengers' => $passengers,
                'luggage' => $luggage,
            ],
            'wishes' => [
                'pet' => true,
                'pet_details' => 'кот в переноске',
                'boarding_help' => false,
                'boarding_help_details' => '',
                'extra_stop' => false,
                'extra_stop_details' => '',
                'other' => false,
                'other_details' => '',
            ],
            'contact' => [
                'name' => 'Анна',
                'method' => 'email',
                'value' => 'anna@example.com',
                'flight_number' => 'IB123',
                'return_flight_number' => '',
                'comment' => 'Встретить у выхода',
                'personal_data_consent' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function place(string $id, string $name, bool $airport): array
    {
        return [
            'place_id' => $id,
            'formatted_address' => $name,
            'display_name' => $name,
            'latitude' => 41.3,
            'longitude' => 2.08,
            'primary_type' => $airport ? 'airport' : 'lodging',
            'types' => $airport ? ['airport'] : ['lodging'],
            'country' => 'Испания',
            'locality' => 'Барселона',
        ];
    }

    private function sentMailText(): string
    {
        $message = $this->messages()->last();
        $this->assertNotNull($message);

        return (string) $message->getOriginalMessage()->getTextBody();
    }

    private function messages()
    {
        $transport = app('mailer')->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);

        return $transport->messages();
    }
}
