<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Page;
use App\Support\HomeBlocks;
use App\Support\SiteEnquiry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        $page = $this->page('home');
        $homeBlocks = app(HomeBlocks::class);

        return view('pages.home', [
            'page' => $page,
            'destinations' => $this->publishedDestinations(),
            'title' => $page->seoTitle(),
            'description' => $page->seoDescription(),
            'keywords' => $page->seoKeywords(),
            'draft' => $this->transferDraft(),
            'homeSections' => $homeBlocks->visible(),
            'homeNav' => $homeBlocks->navItems(),
        ]);
    }


    public function destinations(): View
    {
        $page = $this->page('destinations');

        return view('pages.destinations', [
            'page' => $page,
            'destinations' => $this->publishedDestinations(),
            'title' => $page->seoTitle(),
            'description' => $page->seoDescription(),
            'keywords' => $page->seoKeywords(),
        ]);
    }

    public function information(): View
    {
        $page = $this->page('information');

        return view('pages.information', [
            'page' => $page,
            'title' => $page->seoTitle(),
            'description' => $page->seoDescription(),
            'keywords' => $page->seoKeywords(),
        ]);
    }

    public function contacts(): View
    {
        $page = $this->page('contacts');

        return view('pages.contacts', [
            'page' => $page,
            'title' => $page->seoTitle(),
            'description' => $page->seoDescription(),
            'keywords' => $page->seoKeywords(),
        ]);
    }

    public function contactRequest(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->filled('company_website')) {
            return $this->contactAccepted($request);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact' => ['required', 'string', 'max:180', function (string $attribute, mixed $value, \Closure $fail): void {
                $value = is_string($value) ? $value : '';
                $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
                $digits = preg_replace('/\D+/', '', $value) ?? '';

                if (! $isEmail && (strlen($digits) < 10 || strlen($digits) > 15)) {
                    $fail('Укажите email или телефон: в номере должно быть от 10 до 15 цифр.');
                }
            }],
            'message' => ['required', 'string', 'max:2000'],
            'personal_data_consent' => ['accepted'],
        ], [
            'name.required' => 'Укажите имя.',
            'contact.required' => 'Укажите email или телефон.',
            'message.required' => 'Напишите вопрос.',
            'personal_data_consent.accepted' => 'Нужно отдельное согласие на обработку персональных данных.',
        ]);

        $stored = app(SiteEnquiry::class)->storeContact(
            $validated['name'],
            $validated['contact'],
            $validated['message'],
        );

        if (! $stored['duplicate']) {
            $body = "Имя: {$validated['name']}\n"
                ."Контакт: {$validated['contact']}\n\n"
                ."Сообщение: {$validated['message']}";

            try {
                $this->sendSiteMail('Вопрос с сайта Transfer Point', $body, filter_var($validated['contact'], FILTER_VALIDATE_EMAIL) ? $validated['contact'] : null);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        logger()->info('Contact form stored', [
            'enquiry_id' => $stored['enquiry']->id,
            'duplicate' => $stored['duplicate'],
        ]);

        return $this->contactAccepted($request);
    }

    private function contactAccepted(Request $request): JsonResponse|RedirectResponse
    {
        $status = 'Сообщение успешно отправлено';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $status,
            ]);
        }

        return back()->with('status', $status);
    }

    public function transferEnquiry(Request $request): JsonResponse
    {
        $data = $this->normalizeEnquiryPayload($request->all());
        $validator = Validator::make($data, $this->enquiryRules($data), $this->enquiryMessages());
        $validator->after(function ($validator) use ($data): void {
            $this->validateEnquiryConsistency($validator, $data);
        });

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Проверьте поля с сообщениями. Заявка не отправлена.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if ($validated['trip'] === 'oneway') {
            unset($validated['return']);
        }

        $stored = app(SiteEnquiry::class)->storeTransfer($validated);

        if (! $stored['duplicate']) {
            try {
                $this->sendSiteMail(
                    'Новая заявка на трансфер',
                    $this->enquiryBody($validated),
                    $this->replyAddress($validated),
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        logger()->info('Transfer enquiry stored', [
            'enquiry_id' => $stored['enquiry']->id,
            'duplicate' => $stored['duplicate'],
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Заявка принята. Мы свяжемся с вами, чтобы согласовать трансфер.',
        ]);
    }

    public function transferContinue(Request $request): JsonResponse
    {
        $data = $this->normalizeEnquiryPayload($request->all());
        unset($data['contact']);
        $rules = $this->enquiryRules($data, false);
        $rules['personal_data_consent'] = ['accepted'];
        $validator = Validator::make($data, $rules, $this->enquiryMessages());
        $validator->after(function ($validator) use ($data): void {
            $this->validateEnquiryConsistency($validator, $data, false);
        });

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Проверьте поля с сообщениями. Переход к оформлению не выполнен.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (($validated['trip'] ?? null) === 'oneway') {
            unset($validated['return']);
        }

        session(['transfer.draft' => $validated]);

        return response()->json([
            'ok' => true,
            'redirect' => route('transfer.request'),
        ]);
    }

    public function transferRequest(): View
    {
        $draft = $this->transferDraft();
        $storedErrors = session('transfer_contact_errors', []);

        return view('pages.transfer-request', [
            'draft' => $draft,
            'summary' => is_array($draft) ? $this->transferSummary($draft) : [],
            'needsOutboundFlight' => is_array($draft) && $this->placeIsAirport($this->legPlace($draft, 'outbound', 'pickup')),
            'needsReturnFlight' => is_array($draft)
                && ($draft['trip'] ?? null) === 'return'
                && $this->placeIsAirport($this->legPlace($draft, 'return', 'pickup')),
            'formErrors' => is_array($storedErrors) ? $storedErrors : [],
            'title' => 'Оформление заявки',
            'description' => 'Проверьте параметры поездки и оставьте контакты, чтобы запросить стоимость трансфера.',
        ]);
    }

    public function transferRequestStore(Request $request): RedirectResponse
    {
        $draft = $this->transferDraft();

        if ($draft === null) {
            return redirect()->route('transfer.request');
        }

        if ($request->filled('company_website')) {
            session()->forget('transfer.draft');

            return redirect()
                ->route('transfer.request')
                ->with('status', 'Заявка принята. Мы свяжемся с вами, чтобы согласовать трансфер.');
        }

        $payload = $draft;
        $payload['contact'] = [
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'messenger' => $request->input('messenger'),
            'flight_number' => $request->input('flight_number'),
            'return_flight_number' => $request->input('return_flight_number'),
            'personal_data_consent' => $request->boolean('personal_data_consent')
                || filter_var($draft['personal_data_consent'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
        $data = $this->normalizeEnquiryPayload($payload);
        $validator = Validator::make($data, $this->enquiryRules($data), $this->enquiryMessages());
        $validator->after(function ($validator) use ($data): void {
            $this->validateEnquiryConsistency($validator, $data);
        });

        if ($validator->fails()) {
            $errors = $this->enquiryErrorBag($validator);

            return redirect()
                ->route('transfer.request')
                ->withErrors($errors)
                ->with('transfer_contact_errors', $errors)
                ->withInput();
        }

        $validated = $validator->validated();

        if ($validated['trip'] === 'oneway') {
            unset($validated['return']);
        }

        $stored = app(SiteEnquiry::class)->storeTransfer($validated);

        if (! $stored['duplicate']) {
            $this->sendSiteMail(
                'Новая заявка на трансфер',
                $this->enquiryBody($validated),
                $this->replyAddress($validated),
            );
        }

        logger()->info('Transfer enquiry stored', [
            'enquiry_id' => $stored['enquiry']->id,
            'duplicate' => $stored['duplicate'],
        ]);

        session()->forget(['transfer.draft', 'transfer_contact_errors']);

        return redirect()
            ->route('transfer.request')
            ->with('status', 'Заявка принята. Мы свяжемся с вами, чтобы согласовать трансфер.');
    }

    public function destination(Destination $destination): View
    {
        return view('pages.destination', [
            'destination' => $destination,
            'title' => $destination->seoTitle(),
            'description' => $destination->seoDescription(),
            'keywords' => $destination->seoKeywords(),
        ]);
    }

    public function request(Request $request, Destination $destination): RedirectResponse
    {
        if ($request->filled('company_website')) {
            return back()->with('status', 'Запрос получен. Мы свяжем вас с независимыми местными перевозчиками.');
        }

        $validated = $request->validate([
            'from' => ['required', 'string', 'max:180'],
            'to' => ['required', 'string', 'max:180'],
            'date' => ['required', 'date'],
            'email' => ['required', 'email', 'max:180'],
            'personal_data_consent' => ['accepted'],
        ], [
            'personal_data_consent.accepted' => 'Нужно отдельное согласие на обработку персональных данных.',
        ]);

        $stored = app(SiteEnquiry::class)->storeDestination([
            'destination' => $destination->name,
            'from' => $validated['from'],
            'to' => $validated['to'],
            'date' => $validated['date'],
            'email' => $validated['email'],
        ]);

        if (! $stored['duplicate']) {
            $body = "Новый запрос на трансфер\n\n"
                ."Откуда: {$validated['from']}\n"
                ."Куда: {$validated['to']}\n"
                ."Дата: {$validated['date']}\n"
                ."Email: {$validated['email']}";

            try {
                $this->sendSiteMail('Новый запрос на трансфер', $body, $validated['email']);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        logger()->info('Destination request stored', [
            'enquiry_id' => $stored['enquiry']->id,
            'destination' => $destination->slug,
            'duplicate' => $stored['duplicate'],
        ]);

        return back()->with('status', 'Запрос получен. Мы свяжем вас с независимыми местными перевозчиками.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeEnquiryPayload(array $data): array
    {
        foreach (['outbound', 'return'] as $leg) {
            if (! isset($data[$leg]) || ! is_array($data[$leg])) {
                continue;
            }

            $passengers = $data[$leg]['passengers'] ?? null;

            if (is_array($passengers)) {
                $seats = $passengers['seats'] ?? null;

                if (is_array($seats)) {
                    foreach (['height_cm', 'weight_kg'] as $key) {
                        if (($seats[$key] ?? null) === '') {
                            $data[$leg]['passengers']['seats'][$key] = null;
                        }
                    }
                }

                foreach (['children', 'infants'] as $kind) {
                    if (! isset($passengers[$kind]) || ! is_array($passengers[$kind])) {
                        continue;
                    }

                    foreach ($passengers[$kind] as $index => $item) {
                        if (is_array($item) && ($item['birth_date'] ?? null) === '') {
                            $data[$leg]['passengers'][$kind][$index]['birth_date'] = null;
                        }
                    }
                }
            }

            foreach (['pickup', 'dropoff'] as $point) {
                if (! isset($data[$leg][$point]) || ! is_array($data[$leg][$point])) {
                    continue;
                }

                foreach (['place_id', 'display_name', 'latitude', 'longitude', 'primary_type', 'country', 'locality'] as $key) {
                    if (($data[$leg][$point][$key] ?? null) === '') {
                        $data[$leg][$point][$key] = null;
                    }
                }
            }

            if (isset($data[$leg]['luggage']) && is_array($data[$leg]['luggage'])) {
                foreach (['suitcase_size', 'stroller_folds', 'wheelchair_folds'] as $key) {
                    if (($data[$leg]['luggage'][$key] ?? null) === '') {
                        $data[$leg]['luggage'][$key] = null;
                    }
                }

                if (($data[$leg]['luggage']['wheelchair_ride'] ?? null) === '') {
                    $data[$leg]['luggage']['wheelchair_ride'] = null;
                }
            }
        }

        if (isset($data['contact']) && is_array($data['contact'])) {
            foreach (['flight_number', 'return_flight_number', 'comment', 'phone', 'email'] as $key) {
                if (($data['contact'][$key] ?? null) === '') {
                    $data['contact'][$key] = null;
                }
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function enquiryRules(array $data, bool $withContact = true): array
    {
        $rules = [
            'trip' => ['required', 'in:return,oneway'],
            'wishes' => ['required', 'array'],
            'wishes.pet' => ['required', 'boolean'],
            'wishes.pet_details' => ['nullable', 'string', 'max:180'],
            'wishes.boarding_help' => ['required', 'boolean'],
            'wishes.boarding_help_details' => ['nullable', 'string', 'max:180'],
            'wishes.extra_stop' => ['required', 'boolean'],
            'wishes.extra_stop_details' => ['nullable', 'string', 'max:180'],
            'wishes.other' => ['required', 'boolean'],
            'wishes.other_details' => ['nullable', 'string', 'max:500'],
        ];

        if ($withContact) {
            $contact = is_array($data['contact'] ?? null) ? $data['contact'] : [];
            $splitContact = array_key_exists('phone', $contact) || array_key_exists('email', $contact);
            $rules['contact'] = ['required', 'array'];
            $rules['contact.name'] = ['required', 'string', 'max:120'];
            $rules['contact.flight_number'] = ['nullable', 'string', 'max:20'];
            $rules['contact.return_flight_number'] = ['nullable', 'string', 'max:20'];
            $rules['contact.comment'] = ['nullable', 'string', 'max:2000'];

            $rules['contact.personal_data_consent'] = ['accepted'];

            if ($splitContact) {
                $rules['contact.phone'] = ['required', 'string', 'max:40'];
                $rules['contact.email'] = ['nullable', 'email', 'max:180'];
                $rules['contact.messenger'] = ['required', 'in:WhatsApp,Telegram,whatsapp,telegram'];
                $rules['contact.method'] = ['nullable', 'string', 'max:40'];
                $rules['contact.value'] = ['nullable', 'string', 'max:180'];
            } else {
                $rules['contact.method'] = ['required', 'in:phone,email,telegram,whatsapp'];
                $rules['contact.value'] = ['required', 'string', 'max:180'];
            }
        }

        $rules = array_merge($rules, $this->legRules('outbound', now()->subDay()->toDateString()));

        if (($data['trip'] ?? null) === 'return') {
            $outboundDate = $data['outbound']['date'] ?? null;
            $returnMin = is_string($outboundDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $outboundDate) === 1
                ? $outboundDate
                : now()->subDay()->toDateString();
            $rules = array_merge($rules, $this->legRules('return', $returnMin));
            $rules['return.route_custom'] = ['required', 'boolean'];
            $rules['return.party_custom'] = ['required', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function legRules(string $leg, string $minDate): array
    {
        return array_merge(
            $this->placeRules("{$leg}.pickup"),
            $this->placeRules("{$leg}.dropoff"),
            [
                "{$leg}.date" => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$minDate],
                "{$leg}.time" => ['required', 'date_format:H:i'],
            ],
            $this->passengerRules("{$leg}.passengers"),
            $this->luggageRules("{$leg}.luggage"),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function placeRules(string $prefix): array
    {
        return [
            $prefix => ['required', 'array'],
            "{$prefix}.place_id" => ['nullable', 'string', 'max:255'],
            "{$prefix}.formatted_address" => ['required', 'string', 'min:2', 'max:500'],
            "{$prefix}.display_name" => ['nullable', 'string', 'max:255'],
            "{$prefix}.latitude" => ['nullable', 'numeric', 'between:-90,90'],
            "{$prefix}.longitude" => ['nullable', 'numeric', 'between:-180,180'],
            "{$prefix}.primary_type" => ['nullable', 'string', 'max:80'],
            "{$prefix}.types" => ['nullable', 'array', 'max:30'],
            "{$prefix}.types.*" => ['string', 'max:80'],
            "{$prefix}.country" => ['nullable', 'string', 'max:120'],
            "{$prefix}.locality" => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function passengerRules(string $prefix): array
    {
        return [
            $prefix => ['required', 'array'],
            "{$prefix}.confirmed" => ['accepted'],
            "{$prefix}.adults" => ['required', 'integer', 'between:0,50'],
            "{$prefix}.children" => ['present', 'array', 'max:50'],
            "{$prefix}.children.*.age_years" => ['required', 'integer', 'between:2,12'],
            "{$prefix}.children.*.birth_date" => ['nullable', 'date_format:Y-m-d'],
            "{$prefix}.infants" => ['present', 'array', 'max:50'],
            "{$prefix}.infants.*.age_months" => ['required', 'integer', 'between:0,23'],
            "{$prefix}.infants.*.birth_date" => ['nullable', 'date_format:Y-m-d'],
            "{$prefix}.seats" => ['required', 'array'],
            "{$prefix}.seats.carrier" => ['required', 'integer', 'between:0,50'],
            "{$prefix}.seats.child" => ['required', 'integer', 'between:0,50'],
            "{$prefix}.seats.booster" => ['required', 'integer', 'between:0,50'],
            "{$prefix}.seats.own" => ['required', 'integer', 'between:0,50'],
            "{$prefix}.seats.height_cm" => ['nullable', 'integer', 'between:30,250'],
            "{$prefix}.seats.weight_kg" => ['nullable', 'integer', 'between:1,300'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function luggageRules(string $prefix): array
    {
        return [
            $prefix => ['required', 'array'],
            "{$prefix}.confirmed" => ['accepted'],
            "{$prefix}.suitcases" => ['required', 'integer', 'between:0,50'],
            "{$prefix}.suitcase_size" => ['nullable', 'in:regular,large'],
            "{$prefix}.suitcase_dimensions" => ['nullable', 'string', 'max:80'],
            "{$prefix}.cabin" => ['required', 'integer', 'between:0,50'],
            "{$prefix}.strollers" => ['required', 'integer', 'between:0,50'],
            "{$prefix}.stroller_folds" => ['nullable', 'in:yes,no'],
            "{$prefix}.stroller_dimensions" => ['nullable', 'string', 'max:80'],
            "{$prefix}.sports" => ['required', 'integer', 'between:0,50'],
            "{$prefix}.sports_type" => ['nullable', 'string', 'max:80'],
            "{$prefix}.sports_dimensions" => ['nullable', 'string', 'max:80'],
            "{$prefix}.wheelchairs" => ['required', 'integer', 'between:0,50'],
            "{$prefix}.wheelchair_folds" => ['nullable', 'in:yes,no'],
            "{$prefix}.wheelchair_ride" => ['nullable', 'boolean'],
            "{$prefix}.wheelchair_dimensions" => ['nullable', 'string', 'max:80'],
            "{$prefix}.other" => ['required', 'integer', 'between:0,50'],
            "{$prefix}.other_description" => ['nullable', 'string', 'max:120'],
            "{$prefix}.other_dimensions" => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function enquiryMessages(): array
    {
        return [
            'trip.required' => 'Выберите тип поездки.',
            'trip.in' => 'Выберите тип поездки.',
            'outbound.date.required' => 'Укажите дату поездки.',
            'outbound.date.date_format' => 'Укажите дату поездки.',
            'outbound.date.after_or_equal' => 'Дата поездки не может быть в прошлом.',
            'outbound.time.required' => 'Укажите время поездки.',
            'outbound.time.date_format' => 'Укажите время поездки.',
            'return.date.required' => 'Укажите дату обратной поездки.',
            'return.date.date_format' => 'Укажите дату обратной поездки.',
            'return.date.after_or_equal' => 'Дата обратной поездки не может быть раньше первой поездки.',
            'return.time.required' => 'Укажите время обратной поездки.',
            'return.time.date_format' => 'Укажите время обратной поездки.',
            'outbound.pickup.formatted_address.required' => 'Укажите адрес отправления.',
            'outbound.dropoff.formatted_address.required' => 'Укажите адрес назначения.',
            'outbound.pickup.formatted_address.min' => 'Укажите адрес отправления.',
            'outbound.dropoff.formatted_address.min' => 'Укажите адрес назначения.',
            'return.pickup.formatted_address.required' => 'Укажите адрес отправления обратной поездки.',
            'return.dropoff.formatted_address.required' => 'Укажите адрес назначения обратной поездки.',
            'personal_data_consent.accepted' => 'Нужно отдельное согласие на обработку персональных данных.',
            'contact.personal_data_consent.accepted' => 'Нужно отдельное согласие на обработку персональных данных.',
            'outbound.passengers.confirmed.accepted' => 'Подтвердите пассажиров кнопкой «Готово».',
            'outbound.luggage.confirmed.accepted' => 'Укажите багаж или подтвердите, что его нет.',
            'return.passengers.confirmed.accepted' => 'Подтвердите пассажиров обратной поездки кнопкой «Готово».',
            'return.luggage.confirmed.accepted' => 'Укажите багаж обратной поездки или подтвердите, что его нет.',
            'outbound.passengers.children.*.age_years.required' => 'Укажите возраст каждого ребёнка.',
            'outbound.passengers.children.*.age_years.between' => 'Укажите возраст ребёнка от 2 до 12 лет.',
            'outbound.passengers.infants.*.age_months.required' => 'Укажите возраст каждого младенца в месяцах.',
            'outbound.passengers.infants.*.age_months.between' => 'Укажите возраст младенца в месяцах, до 2 лет.',
            'return.passengers.children.*.age_years.required' => 'Укажите возраст каждого ребёнка обратной поездки.',
            'return.passengers.infants.*.age_months.required' => 'Укажите возраст каждого младенца обратной поездки.',
            'contact.name.required' => 'Укажите имя.',
            'contact.phone.required' => 'Укажите телефон.',
            'contact.email.email' => 'Укажите email.',
            'contact.messenger.required' => 'Выберите WhatsApp или Telegram.',
            'contact.messenger.in' => 'Выберите WhatsApp или Telegram.',
            'contact.value.required' => 'Укажите контакт для связи.',
            'contact.method.required' => 'Выберите способ связи.',
            'contact.method.in' => 'Выберите способ связи.',
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     * @param  array<string, mixed>  $data
     */
    private function validateEnquiryConsistency($validator, array $data, bool $withContact = true): void
    {
        $outbound = is_array($data['outbound'] ?? null) ? $data['outbound'] : [];
        $this->validateLegConsistency(
            $validator,
            'outbound',
            $outbound,
            is_string($outbound['date'] ?? null) ? $outbound['date'] : null,
        );

        if (($data['trip'] ?? null) === 'return') {
            $return = is_array($data['return'] ?? null) ? $data['return'] : [];
            $this->validateLegConsistency(
                $validator,
                'return',
                $return,
                is_string($return['date'] ?? null) ? $return['date'] : null,
            );
            $this->validateReturnLater($validator, $data);

            if ($withContact) {
                $this->validateFlight($validator, $data, 'return', 'return_flight_number', 'Укажите номер обратного рейса.');
            }
        }

        if ($withContact) {
            $this->validateFlight($validator, $data, 'outbound', 'flight_number', 'Укажите номер рейса: поездка начинается в аэропорту.');
            $this->validateContactValue($validator, is_array($data['contact'] ?? null) ? $data['contact'] : []);
        }
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     * @param  array<string, mixed>  $leg
     */
    private function validateLegConsistency($validator, string $name, array $leg, ?string $tripDate): void
    {
        $pickup = is_array($leg['pickup'] ?? null) ? $leg['pickup'] : [];
        $dropoff = is_array($leg['dropoff'] ?? null) ? $leg['dropoff'] : [];

        $pickupIdentity = $this->placeIdentity($pickup);
        $dropoffIdentity = $this->placeIdentity($dropoff);

        if ($pickupIdentity !== '' && $pickupIdentity === $dropoffIdentity) {
            $validator->errors()->add("{$name}.dropoff.formatted_address", 'Места отправления и прибытия должны отличаться.');
        }

        $passengers = is_array($leg['passengers'] ?? null) ? $leg['passengers'] : [];
        $people = (int) ($passengers['adults'] ?? 0)
            + count(is_array($passengers['children'] ?? null) ? $passengers['children'] : [])
            + count(is_array($passengers['infants'] ?? null) ? $passengers['infants'] : []);

        if ($people < 1) {
            $validator->errors()->add("{$name}.passengers", 'Укажите хотя бы одного пассажира.');
        }

        foreach (['children' => 'birth_date', 'infants' => 'birth_date'] as $kind => $field) {
            $items = is_array($passengers[$kind] ?? null) ? $passengers[$kind] : [];

            foreach ($items as $index => $item) {
                $birth = is_array($item) ? ($item[$field] ?? null) : null;

                if (is_string($birth) && is_string($tripDate) && $birth > $tripDate) {
                    $validator->errors()->add(
                        "{$name}.passengers.{$kind}.{$index}.{$field}",
                        'Дата рождения не может быть позже даты поездки.',
                    );
                }
            }
        }
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     * @param  array<string, mixed>  $data
     */
    private function validateReturnLater($validator, array $data): void
    {
        $out = ($data['outbound']['date'] ?? '').'T'.($data['outbound']['time'] ?? '');
        $ret = ($data['return']['date'] ?? '').'T'.($data['return']['time'] ?? '');

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $out) === 1
            && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $ret) === 1
            && $ret <= $out) {
            $validator->errors()->add('return.date', 'Обратная поездка должна быть позже первой. Сравниваются и дата, и время.');
        }
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     * @param  array<string, mixed>  $data
     */
    private function validateFlight($validator, array $data, string $leg, string $field, string $requiredMessage): void
    {
        $pickup = is_array($data[$leg]['pickup'] ?? null) ? $data[$leg]['pickup'] : [];

        if (! $this->placeIsAirport($pickup)) {
            return;
        }

        $flight = $data['contact'][$field] ?? null;

        if (! is_string($flight) || trim($flight) === '') {
            $validator->errors()->add("contact.{$field}", $requiredMessage);

            return;
        }

        if (preg_match('/^[A-Za-z0-9 -]{2,20}$/', trim($flight)) !== 1) {
            $validator->errors()->add("contact.{$field}", 'Укажите номер рейса латиницей.');
        }
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     * @param  array<string, mixed>  $contact
     */
    private function validateContactValue($validator, array $contact): void
    {
        if (array_key_exists('phone', $contact) || array_key_exists('email', $contact)) {
            $phone = is_string($contact['phone'] ?? null) ? trim($contact['phone']) : '';
            $digits = preg_replace('/\D+/', '', $phone) ?? '';

            if ($phone !== '' && (strlen($digits) < 10 || strlen($digits) > 15)) {
                $validator->errors()->add('contact.phone', 'Укажите телефон: от 10 до 15 цифр.');
            }

            return;
        }

        $method = $contact['method'] ?? '';
        $value = is_string($contact['value'] ?? null) ? trim($contact['value']) : '';

        if ($value === '') {
            return;
        }

        if ($method === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $validator->errors()->add('contact.value', 'Укажите email.');
        }

        if (in_array($method, ['phone', 'whatsapp'], true)) {
            $digits = preg_replace('/\D+/', '', $value) ?? '';

            if (strlen($digits) < 10 || strlen($digits) > 15) {
                $validator->errors()->add('contact.value', 'Укажите телефон: от 10 до 15 цифр.');
            }
        }

        if ($method === 'telegram' && mb_strlen($value) < 3) {
            $validator->errors()->add('contact.value', 'Укажите Telegram.');
        }
    }

    /**
     * @param  array<string, mixed>  $place
     */
    private function placeIsAirport(array $place): bool
    {
        $types = $place['types'] ?? [];
        $primary = $place['primary_type'] ?? '';

        return $primary === 'airport' || (is_array($types) && in_array('airport', $types, true));
    }

    /**
     * @param  array<string, mixed>  $place
     */
    private function placeIdentity(array $place): string
    {
        $id = trim((string) ($place['place_id'] ?? ''));

        if ($id !== '') {
            return 'id:'.$id;
        }

        $address = mb_strtolower(trim((string) ($place['formatted_address'] ?? $place['display_name'] ?? '')));

        return $address === '' ? '' : 'text:'.$address;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function replyAddress(array $data): ?string
    {
        $contact = $data['contact'] ?? [];
        $email = $contact['email'] ?? null;

        if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        if (($contact['method'] ?? null) === 'email' && is_string($contact['value'] ?? null)) {
            return $contact['value'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function enquiryBody(array $data): string
    {
        $lines = [
            'Новая заявка на трансфер',
            '',
            'Тип: '.($data['trip'] === 'return' ? 'Туда и обратно' : 'В одну сторону'),
            '',
            'Поездка туда',
        ];
        $returnDate = $data['trip'] === 'return' ? ($data['return']['date'] ?? null) : null;
        $lines = array_merge($lines, $this->legLines($data['outbound'], is_string($returnDate) ? $returnDate : null));

        if ($data['trip'] === 'return') {
            $lines[] = '';
            $lines[] = 'Обратная поездка';
            $lines = array_merge($lines, $this->legLines($data['return'], null));
            $lines[] = ! empty($data['return']['route_custom'])
                ? 'Обратный маршрут изменён вручную.'
                : 'Обратный маршрут повторяет прямой в обратном порядке.';
            $lines[] = ! empty($data['return']['party_custom'])
                ? 'Пассажиры и багаж обратной поездки указаны отдельно.'
                : 'Пассажиры и багаж обратной поездки совпадают с поездкой туда.';
        }

        $contact = $data['contact'];
        $lines[] = '';
        $lines[] = 'Контакт';
        $lines[] = 'Имя: '.$this->plain($contact['name'] ?? '', 120);

        if (filled($contact['phone'] ?? null) || filled($contact['email'] ?? null) || filled($contact['messenger'] ?? null)) {
            $lines[] = 'Телефон: '.$this->plain($contact['phone'] ?? '', 40);

            if (filled($contact['email'] ?? null)) {
                $lines[] = 'Email: '.$this->plain((string) $contact['email'], 180);
            }

            if (filled($contact['messenger'] ?? null)) {
                $lines[] = 'Мессенджер: '.$this->messengerLabel((string) $contact['messenger']);
            }
        } else {
            $lines[] = 'Способ связи: '.$this->contactMethodLabel((string) ($contact['method'] ?? ''));
            $lines[] = 'Контакт: '.$this->plain($contact['value'] ?? '', 180);
        }

        if (filled($contact['flight_number'] ?? null)) {
            $lines[] = 'Рейс: '.$this->plain((string) $contact['flight_number'], 20);
        }

        if ($data['trip'] === 'return' && filled($contact['return_flight_number'] ?? null)) {
            $lines[] = 'Обратный рейс: '.$this->plain((string) $contact['return_flight_number'], 20);
        }

        if (filled($contact['comment'] ?? null)) {
            $lines[] = 'Комментарий: '.$this->plain((string) $contact['comment'], 2000);
        }

        $lines[] = '';
        $lines[] = 'Особые пожелания';
        $lines = array_merge($lines, $this->wishLines(is_array($data['wishes'] ?? null) ? $data['wishes'] : []));

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $leg
     * @return list<string>
     */
    private function legLines(array $leg, ?string $otherDate): array
    {
        $pickup = is_array($leg['pickup'] ?? null) ? $leg['pickup'] : [];
        $dropoff = is_array($leg['dropoff'] ?? null) ? $leg['dropoff'] : [];
        $airport = $this->placeIsAirport($pickup);
        $lines = [
            'Откуда: '.$this->plain($pickup['formatted_address'] ?? '', 500),
            'Куда: '.$this->plain($dropoff['formatted_address'] ?? '', 500),
            'Координаты отправления: '.($pickup['latitude'] ?? '').', '.($pickup['longitude'] ?? ''),
            'Координаты назначения: '.($dropoff['latitude'] ?? '').', '.($dropoff['longitude'] ?? ''),
            'Дата: '.($leg['date'] ?? ''),
            ($airport ? 'Время прилёта' : 'Время подачи').': '.($leg['time'] ?? ''),
            $airport
                ? 'Время указано как местное время аэропорта.'
                : 'Время указано как местное время места отправления.',
        ];

        return array_merge(
            $lines,
            $this->passengerLines(is_array($leg['passengers'] ?? null) ? $leg['passengers'] : [], $otherDate),
            $this->luggageLines(is_array($leg['luggage'] ?? null) ? $leg['luggage'] : []),
        );
    }

    /**
     * @param  array<string, mixed>  $passengers
     * @return list<string>
     */
    private function passengerLines(array $passengers, ?string $otherDate): array
    {
        $adults = (int) ($passengers['adults'] ?? 0);
        $children = is_array($passengers['children'] ?? null) ? $passengers['children'] : [];
        $infants = is_array($passengers['infants'] ?? null) ? $passengers['infants'] : [];
        $lines = [
            'Взрослые: '.$adults,
            'Дети: '.count($children),
            'Младенцы: '.count($infants),
            'Всего пассажирских мест: '.($adults + count($children) + count($infants)),
        ];

        foreach ($children as $index => $child) {
            if (! is_array($child)) {
                continue;
            }

            $lines[] = 'Ребёнок '.($index + 1).': '.($child['age_years'] ?? '').' лет';
            $lines = array_merge($lines, $this->birthLines($child, 'child', $otherDate));
        }

        foreach ($infants as $index => $infant) {
            if (! is_array($infant)) {
                continue;
            }

            $lines[] = 'Младенец '.($index + 1).': '.($infant['age_months'] ?? '').' мес.';
            $lines = array_merge($lines, $this->birthLines($infant, 'infant', $otherDate));
        }

        $seats = is_array($passengers['seats'] ?? null) ? $passengers['seats'] : [];
        $seatLabels = [
            'carrier' => 'автолюлька',
            'child' => 'детское кресло',
            'booster' => 'бустер',
            'own' => 'своё кресло',
        ];
        $chosen = [];

        foreach ($seatLabels as $key => $label) {
            $count = (int) ($seats[$key] ?? 0);

            if ($count > 0) {
                $chosen[] = $label.' '.$count;
            }
        }

        if ($chosen !== []) {
            $lines[] = 'Детские кресла: '.implode(', ', $chosen).'. Наличие согласовывается при обработке заявки и заранее не обещано.';
        }

        if (filled($seats['height_cm'] ?? null) || filled($seats['weight_kg'] ?? null)) {
            $lines[] = 'Рост, см: '.($seats['height_cm'] ?? 'не указан').'; вес, кг: '.($seats['weight_kg'] ?? 'не указан');
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $person
     * @return list<string>
     */
    private function birthLines(array $person, string $kind, ?string $otherDate): array
    {
        $birth = $person['birth_date'] ?? null;

        if (! is_string($birth) || $birth === '') {
            return [];
        }

        $lines = ['Дата рождения: '.$birth];

        if (! is_string($otherDate) || $otherDate === '') {
            return $lines;
        }

        $years = $this->yearsOn($birth, $otherDate);

        if ($years === null) {
            return $lines;
        }

        $next = $years >= 13 ? 'взрослые' : ($years >= 2 ? 'дети' : 'младенцы');
        $current = $kind === 'child' ? 'дети' : 'младенцы';

        if ($next !== $current) {
            $lines[] = 'На дату обратной поездки возраст попадает в группу «'.$next.'». Дата рождения сохранена.';
        }

        return $lines;
    }

    private function yearsOn(string $birth, string $on): ?int
    {
        $born = Carbon::createFromFormat('Y-m-d', $birth);
        $date = Carbon::createFromFormat('Y-m-d', $on);

        if (! $born || ! $date || $born->startOfDay()->greaterThan($date->copy()->startOfDay())) {
            return null;
        }

        return $born->diff($date)->y;
    }

    /**
     * @param  array<string, mixed>  $luggage
     * @return list<string>
     */
    private function luggageLines(array $luggage): array
    {
        $counts = [
            'suitcases' => (int) ($luggage['suitcases'] ?? 0),
            'cabin' => (int) ($luggage['cabin'] ?? 0),
            'strollers' => (int) ($luggage['strollers'] ?? 0),
            'sports' => (int) ($luggage['sports'] ?? 0),
            'wheelchairs' => (int) ($luggage['wheelchairs'] ?? 0),
            'other' => (int) ($luggage['other'] ?? 0),
        ];

        if (array_sum($counts) === 0) {
            return ['Багаж: без багажа'];
        }

        $lines = ['Чемоданы и сумки: '.$counts['suitcases']];

        if ($counts['suitcases'] > 0 && filled($luggage['suitcase_size'] ?? null)) {
            $lines[] = 'Размер чемоданов: '.($luggage['suitcase_size'] === 'large' ? 'крупный' : 'обычный');
        }

        if (filled($luggage['suitcase_dimensions'] ?? null)) {
            $lines[] = 'Размеры чемоданов: '.$this->plain((string) $luggage['suitcase_dimensions'], 80);
        }

        $lines[] = 'Ручная кладь: '.$counts['cabin'];
        $lines[] = 'Детские коляски: '.$counts['strollers'];

        if ($counts['strollers'] > 0 && filled($luggage['stroller_folds'] ?? null)) {
            $lines[] = 'Коляска складывается: '.($luggage['stroller_folds'] === 'yes' ? 'да' : 'нет');
        }

        if (filled($luggage['stroller_dimensions'] ?? null)) {
            $lines[] = 'Размеры коляски: '.$this->plain((string) $luggage['stroller_dimensions'], 80);
        }

        $lines[] = 'Спортинвентарь: '.$counts['sports'];

        if (filled($luggage['sports_type'] ?? null)) {
            $lines[] = 'Вид спортинвентаря: '.$this->plain((string) $luggage['sports_type'], 80);
        }

        if (filled($luggage['sports_dimensions'] ?? null)) {
            $lines[] = 'Размеры спортинвентаря: '.$this->plain((string) $luggage['sports_dimensions'], 80);
        }

        $lines[] = 'Кресло-коляска: '.$counts['wheelchairs'];

        if ($counts['wheelchairs'] > 0 && filled($luggage['wheelchair_folds'] ?? null)) {
            $lines[] = 'Кресло-коляска складывается: '.($luggage['wheelchair_folds'] === 'yes' ? 'да' : 'нет');
        }

        if ($counts['wheelchairs'] > 0 && array_key_exists('wheelchair_ride', $luggage) && $luggage['wheelchair_ride'] !== null) {
            $lines[] = 'Нужен проезд в кресле: '.($luggage['wheelchair_ride'] ? 'да' : 'нет');
        }

        if (filled($luggage['wheelchair_dimensions'] ?? null)) {
            $lines[] = 'Размеры кресла-коляски: '.$this->plain((string) $luggage['wheelchair_dimensions'], 80);
        }

        $lines[] = 'Другой багаж: '.$counts['other'];

        if (filled($luggage['other_description'] ?? null)) {
            $lines[] = 'Описание другого багажа: '.$this->plain((string) $luggage['other_description'], 120);
        }

        if (filled($luggage['other_dimensions'] ?? null)) {
            $lines[] = 'Размеры другого багажа: '.$this->plain((string) $luggage['other_dimensions'], 80);
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $wishes
     * @return list<string>
     */
    private function wishLines(array $wishes): array
    {
        $lines = [];
        $map = [
            'pet' => 'Питомец',
            'boarding_help' => 'Помощь при посадке',
            'extra_stop' => 'Дополнительная остановка',
            'other' => 'Другие особенности',
        ];

        foreach ($map as $key => $label) {
            if (empty($wishes[$key]) && blank($wishes[$key.'_details'] ?? null)) {
                continue;
            }

            $details = $this->plain($wishes[$key.'_details'] ?? '', 500);
            $lines[] = $details !== '' ? $label.': '.$details : $label;
        }

        return $lines === [] ? ['Не указаны'] : $lines;
    }

    private function contactMethodLabel(string $method): string
    {
        return match ($method) {
            'email' => 'Email',
            'telegram' => 'Telegram',
            'whatsapp' => 'WhatsApp',
            default => 'Телефон',
        };
    }

    private function messengerLabel(string $messenger): string
    {
        return match (mb_strtolower($messenger)) {
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            default => $messenger,
        };
    }

    private function plain(mixed $value, int $max): string
    {
        $text = trim(strip_tags(is_scalar($value) ? (string) $value : ''));

        return mb_substr($text, 0, $max);
    }

    private function sendSiteMail(string $subject, string $body, ?string $replyTo = null): void
    {
        $to = config('mail.to.address');

        if (! is_string($to) || $to === '') {
            return;
        }

        // После HTTP-ответа: SMTP не блокирует сохранение заявки и редирект/JSON.
        defer(function () use ($to, $subject, $body, $replyTo): void {
            try {
                Mail::raw($body, function ($mail) use ($to, $subject, $replyTo): void {
                    $mail->to($to)->subject($subject);

                    if (is_string($replyTo) && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                        $mail->replyTo($replyTo);
                    }
                });
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function transferDraft(): ?array
    {
        $draft = session('transfer.draft');

        return is_array($draft) ? $draft : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function legPlace(array $data, string $leg, string $point): array
    {
        $place = $data[$leg][$point] ?? null;

        return is_array($place) ? $place : [];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     * @return array<string, list<string>>
     */
    private function enquiryErrorBag($validator): array
    {
        $mapped = [];
        $fields = [
            'contact.name' => 'name',
            'contact.phone' => 'phone',
            'contact.messenger' => 'messenger',
            'contact.flight_number' => 'flight_number',
            'contact.return_flight_number' => 'return_flight_number',
            'contact.personal_data_consent' => 'personal_data_consent',
        ];

        foreach ($validator->errors()->messages() as $key => $messages) {
            $field = $fields[$key] ?? 'trip';

            foreach ($messages as $message) {
                $mapped[$field][] = $message;
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
     */
    private function transferSummary(array $data): array
    {
        $outbound = is_array($data['outbound'] ?? null) ? $data['outbound'] : [];
        $pickup = $this->legPlace($data, 'outbound', 'pickup');
        $dropoff = $this->legPlace($data, 'outbound', 'dropoff');
        $passengers = is_array($outbound['passengers'] ?? null) ? $outbound['passengers'] : [];
        $luggage = is_array($outbound['luggage'] ?? null) ? $outbound['luggage'] : [];
        $groups = [];

        $route = $this->summaryRows([
            ['Откуда', $this->plain($pickup['formatted_address'] ?? '', 500)],
            ['Куда', $this->plain($dropoff['formatted_address'] ?? '', 500)],
            ['Тип поездки', ($data['trip'] ?? null) === 'return' ? 'Туда и обратно' : 'В одну сторону'],
        ]);

        if ($route !== []) {
            $groups[] = ['title' => 'Маршрут', 'rows' => $route];
        }

        $there = $this->summaryRows([
            ['Дата поездки', $this->formatTripDate(is_string($outbound['date'] ?? null) ? $outbound['date'] : null)],
            [$this->timeLabel($pickup), is_string($outbound['time'] ?? null) ? $outbound['time'] : ''],
            ['Пассажиры', $this->passengerSummaryText($passengers)],
            ['Багаж', $this->luggageSummaryText($luggage)],
        ]);

        if ($there !== []) {
            $groups[] = ['title' => 'Поездка туда', 'rows' => $there];
        }

        if (($data['trip'] ?? null) === 'return' && is_array($data['return'] ?? null)) {
            $return = $data['return'];
            $returnPickup = $this->legPlace($data, 'return', 'pickup');
            $returnDropoff = $this->legPlace($data, 'return', 'dropoff');
            $rows = [
                ['Откуда', $this->plain($returnPickup['formatted_address'] ?? '', 500)],
                ['Куда', $this->plain($returnDropoff['formatted_address'] ?? '', 500)],
                ['Дата обратной поездки', $this->formatTripDate(is_string($return['date'] ?? null) ? $return['date'] : null)],
                [$this->timeLabel($returnPickup), is_string($return['time'] ?? null) ? $return['time'] : ''],
            ];

            if (! empty($return['party_custom'])) {
                $rows[] = ['Пассажиры', $this->passengerSummaryText(is_array($return['passengers'] ?? null) ? $return['passengers'] : [])];
                $rows[] = ['Багаж', $this->luggageSummaryText(is_array($return['luggage'] ?? null) ? $return['luggage'] : [])];
            } else {
                $rows[] = ['Пассажиры и багаж', 'Совпадают с поездкой туда'];
            }

            $back = $this->summaryRows($rows);

            if ($back !== []) {
                $groups[] = ['title' => 'Обратно', 'rows' => $back];
            }
        }

        $wishes = is_array($data['wishes'] ?? null) ? $data['wishes'] : [];
        $wishRows = [];

        foreach ([
            'pet' => 'Питомец',
            'boarding_help' => 'Помощь при посадке',
            'extra_stop' => 'Дополнительная остановка',
            'other' => 'Другие особенности',
        ] as $key => $label) {
            if (empty($wishes[$key]) && blank($wishes[$key.'_details'] ?? null)) {
                continue;
            }

            $details = $this->plain($wishes[$key.'_details'] ?? '', 500);
            $wishRows[] = [$label, $details !== '' ? $details : 'Да'];
        }

        $wishRows = $this->summaryRows($wishRows);

        if ($wishRows !== []) {
            $groups[] = ['title' => 'Особые пожелания', 'rows' => $wishRows];
        }

        return $groups;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $rows
     * @return list<array{label: string, value: string}>
     */
    private function summaryRows(array $rows): array
    {
        $result = [];

        foreach ($rows as [$label, $value]) {
            $text = trim($value);

            if ($text === '') {
                continue;
            }

            $result[] = ['label' => $label, 'value' => $text];
        }

        return $result;
    }

    private function formatTripDate(?string $iso): string
    {
        if (! is_string($iso) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso) !== 1) {
            return '';
        }

        $date = Carbon::createFromFormat('Y-m-d', $iso);

        if (! $date) {
            return '';
        }

        $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

        return $date->day.' '.$months[$date->month - 1].' '.$date->year;
    }

    /**
     * @param  array<string, mixed>  $pickup
     */
    private function timeLabel(array $pickup): string
    {
        return $this->placeIsAirport($pickup) ? 'Время прилёта' : 'Время подачи';
    }

    /**
     * @param  array<string, mixed>  $passengers
     */
    private function passengerSummaryText(array $passengers): string
    {
        $adults = (int) ($passengers['adults'] ?? 0);
        $children = is_array($passengers['children'] ?? null) ? $passengers['children'] : [];
        $infants = is_array($passengers['infants'] ?? null) ? $passengers['infants'] : [];
        $parts = [];

        if ($adults > 0) {
            $parts[] = $adults.' '.$this->pluralRu($adults, 'взрослый', 'взрослых', 'взрослых');
        }

        if ($children !== []) {
            $ages = [];

            foreach ($children as $child) {
                if (! is_array($child) || ! isset($child['age_years']) || $child['age_years'] === '' || $child['age_years'] === null) {
                    continue;
                }

                $years = (int) $child['age_years'];
                $ages[] = $years.' '.$this->pluralRu($years, 'год', 'года', 'лет');
            }

            $label = count($children).' '.$this->pluralRu(count($children), 'ребёнок', 'ребёнка', 'детей');
            $parts[] = $ages === [] ? $label : $label.' ('.implode(', ', $ages).')';
        }

        if ($infants !== []) {
            $ages = [];

            foreach ($infants as $infant) {
                if (! is_array($infant) || ! isset($infant['age_months']) || $infant['age_months'] === '' || $infant['age_months'] === null) {
                    continue;
                }

                $months = (int) $infant['age_months'];
                $ages[] = $months === 0 ? 'меньше месяца' : $months.' мес.';
            }

            $label = count($infants).' '.$this->pluralRu(count($infants), 'младенец', 'младенца', 'младенцев');
            $parts[] = $ages === [] ? $label : $label.' ('.implode(', ', $ages).')';
        }

        if ($parts === []) {
            return '';
        }

        $text = implode(', ', $parts);
        $seats = is_array($passengers['seats'] ?? null) ? $passengers['seats'] : [];
        $seatLabels = [
            'carrier' => 'автолюлька',
            'child' => 'детское кресло',
            'booster' => 'бустер',
            'own' => 'своё кресло',
        ];
        $chosen = [];

        foreach ($seatLabels as $key => $label) {
            $count = (int) ($seats[$key] ?? 0);

            if ($count > 0) {
                $chosen[] = $label.' '.$count;
            }
        }

        if ($chosen !== []) {
            $text .= '. Детские кресла: '.implode(', ', $chosen);
        }

        $measure = [];

        if (filled($seats['height_cm'] ?? null)) {
            $measure[] = 'рост '.$seats['height_cm'].' см';
        }

        if (filled($seats['weight_kg'] ?? null)) {
            $measure[] = 'вес '.$seats['weight_kg'].' кг';
        }

        if ($measure !== []) {
            $text .= '. '.implode(', ', $measure);
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>  $luggage
     */
    private function luggageSummaryText(array $luggage): string
    {
        $items = [
            'suitcases' => ['чемодан', 'чемодана', 'чемоданов'],
            'cabin' => ['место ручной клади', 'места ручной клади', 'мест ручной клади'],
            'strollers' => ['коляска', 'коляски', 'колясок'],
            'sports' => ['ед. спортинвентаря', 'ед. спортинвентаря', 'ед. спортинвентаря'],
            'wheelchairs' => ['кресло-коляска', 'кресла-коляски', 'кресел-колясок'],
            'other' => ['другая вещь', 'другие вещи', 'других вещей'],
        ];
        $parts = [];

        foreach ($items as $key => [$one, $few, $many]) {
            $count = (int) ($luggage[$key] ?? 0);

            if ($count < 1) {
                continue;
            }

            $line = $count.' '.$this->pluralRu($count, $one, $few, $many);

            if ($key === 'suitcases' && ($luggage['suitcase_size'] ?? null) === 'large') {
                $line .= ', крупный';
            } elseif ($key === 'suitcases' && ($luggage['suitcase_size'] ?? null) === 'regular') {
                $line .= ', обычный';
            }

            if ($key === 'strollers' && ($luggage['stroller_folds'] ?? null) === 'yes') {
                $line .= ', складывается';
            } elseif ($key === 'strollers' && ($luggage['stroller_folds'] ?? null) === 'no') {
                $line .= ', не складывается';
            }

            if ($key === 'wheelchairs' && array_key_exists('wheelchair_ride', $luggage) && $luggage['wheelchair_ride'] !== null) {
                $line .= $luggage['wheelchair_ride'] ? ', нужен проезд в кресле' : ', проезд в кресле не нужен';
            }

            $detailKey = match ($key) {
                'suitcases' => 'suitcase_dimensions',
                'strollers' => 'stroller_dimensions',
                'sports' => 'sports_type',
                'wheelchairs' => 'wheelchair_dimensions',
                'other' => 'other_description',
                default => null,
            };
            $detail = $detailKey ? $this->plain($luggage[$detailKey] ?? '', 80) : '';

            if ($detail !== '') {
                $line .= ', '.$detail;
            }

            $parts[] = $line;
        }

        return $parts === [] ? 'Без багажа' : implode('; ', $parts);
    }

    private function pluralRu(int $count, string $one, string $few, string $many): string
    {
        $n = abs($count) % 100;
        $n1 = $n % 10;

        if ($n > 10 && $n < 20) {
            return $many;
        }

        if ($n1 > 1 && $n1 < 5) {
            return $few;
        }

        if ($n1 === 1) {
            return $one;
        }

        return $many;
    }

    private function page(string $slug): Page
    {
        $page = Page::query()->where('slug', $slug)->published()->first();

        abort_unless($page instanceof Page, 404);

        return $page;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Destination>
     */
    private function publishedDestinations()
    {
        return Destination::query()->published()->ordered()->get();
    }
}
