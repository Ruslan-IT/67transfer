@extends('layouts.app')

@section('content')
    @php
        $formErrors = is_array($formErrors ?? null) ? $formErrors : [];
        $fieldError = function (string $field) use ($errors, $formErrors): ?string {
            $message = $errors->first($field);

            if (is_string($message) && $message !== '') {
                return $message;
            }

            $fallback = $formErrors[$field][0] ?? null;

            return is_string($fallback) && $fallback !== '' ? $fallback : null;
        };
        $tripErrors = array_values(array_filter(array_merge(
            $errors->get('trip'),
            is_array($formErrors['trip'] ?? null) ? $formErrors['trip'] : [],
        ), 'is_string'));
        $selectedMessenger = old('messenger');
        $icon = function (string $name): string {
            $paths = [
                'eye' => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
                'user' => '<circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/>',
                'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
                'message' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/>',
                'send' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
                'arrow' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
            ];

            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name] ?? '').'</svg>';
        };
        $hasContactErrors = $fieldError('name')
            || $fieldError('phone')
            || $fieldError('messenger')
            || $fieldError('flight_number')
            || $fieldError('return_flight_number')
            || $fieldError('personal_data_consent')
            || $tripErrors !== []
            || session('transfer_error');
        $summaryGroups = is_array($summary ?? null) ? $summary : [];
        $summaryRows = function (string $title) use ($summaryGroups): array {
            foreach ($summaryGroups as $group) {
                if (($group['title'] ?? null) === $title && is_array($group['rows'] ?? null)) {
                    return $group['rows'];
                }
            }

            return [];
        };
        $rowValue = function (array $rows, string $label): string {
            foreach ($rows as $row) {
                if (($row['label'] ?? null) === $label && is_string($row['value'] ?? null)) {
                    return $row['value'];
                }
            }

            return '';
        };
        $rowPrefixed = function (array $rows, string $prefix): array {
            foreach ($rows as $row) {
                $label = is_string($row['label'] ?? null) ? $row['label'] : '';

                if ($label === $prefix || str_starts_with($label, $prefix)) {
                    return [
                        'label' => $label,
                        'value' => is_string($row['value'] ?? null) ? $row['value'] : '',
                    ];
                }
            }

            return ['label' => '', 'value' => ''];
        };
        $routeRows = $summaryRows('Маршрут');
        $thereRows = $summaryRows('Поездка туда');
        $backRows = $summaryRows('Обратно');
        $wishRows = $summaryRows('Особые пожелания');
        $routeFrom = $rowValue($routeRows, 'Откуда');
        $routeTo = $rowValue($routeRows, 'Куда');
        $tripType = $rowValue($routeRows, 'Тип поездки');
        $thereDate = $rowValue($thereRows, 'Дата поездки');
        $thereTime = $rowPrefixed($thereRows, 'Время');
        $passengers = $rowValue($thereRows, 'Пассажиры');
        $luggage = $rowValue($thereRows, 'Багаж');
        $backFrom = $rowValue($backRows, 'Откуда');
        $backTo = $rowValue($backRows, 'Куда');
        $backDate = $rowValue($backRows, 'Дата обратной поездки');
        $backTime = $rowPrefixed($backRows, 'Время');
        $backParty = $rowValue($backRows, 'Пассажиры и багаж');
        $backPassengers = $rowValue($backRows, 'Пассажиры');
        $backLuggage = $rowValue($backRows, 'Багаж');
        $routeLine = trim($routeFrom.($routeFrom !== '' && $routeTo !== '' ? ' → ' : '').$routeTo);
        $thereWhen = trim($thereDate.($thereDate !== '' && $thereTime['value'] !== '' ? ', ' : '').$thereTime['value']);
        $backWhen = trim($backDate.($backDate !== '' && $backTime['value'] !== '' ? ', ' : '').$backTime['value']);
        $backRoute = trim($backFrom.($backFrom !== '' && $backTo !== '' ? ' → ' : '').$backTo);
        $hasRouteSummary = $routeLine !== '' || $thereWhen !== '' || $backWhen !== '' || $passengers !== '' || $luggage !== '' || $wishRows !== [];
    @endphp
    <section class="transfer-request" aria-label="Второй шаг — контакт">
        <div class="container">
            @if ($draft)
                <div class="transfer-request__shell">
                    <a class="transfer-request__back" href="{{ route('home') }}">Назад</a>

                    @if ($hasRouteSummary)
                        <section class="transfer-request__route" aria-label="Параметры поездки">
                            <div class="transfer-request__route-grid">
                                @if ($routeLine !== '')
                                    <article class="transfer-request__fact transfer-request__fact--route">
                                        <p class="transfer-request__fact-label">Маршрут</p>
                                        <p class="transfer-request__fact-value">{{ $routeLine }}</p>
                                        @if ($tripType !== '')
                                            <p class="transfer-request__fact-meta">{{ $tripType }}</p>
                                        @endif
                                    </article>
                                @endif

                                @if ($thereWhen !== '')
                                    <article class="transfer-request__fact transfer-request__fact--there">
                                        <p class="transfer-request__fact-label">Поездка туда</p>
                                        <p class="transfer-request__fact-value">{{ $thereWhen }}</p>
                                        @if ($thereTime['label'] !== '')
                                            <p class="transfer-request__fact-meta">{{ $thereTime['label'] }}</p>
                                        @endif
                                    </article>
                                @endif

                                @if ($backWhen !== '' || $backRoute !== '')
                                    <article class="transfer-request__fact transfer-request__fact--back">
                                        <p class="transfer-request__fact-label">Поездка обратно</p>
                                        @if ($backWhen !== '')
                                            <p class="transfer-request__fact-value">{{ $backWhen }}</p>
                                        @endif
                                        @if ($backRoute !== '' && $backRoute !== $routeTo.' → '.$routeFrom && $backRoute !== $routeLine)
                                            <p class="transfer-request__fact-meta">{{ $backRoute }}</p>
                                        @endif
                                        @if ($backTime['label'] !== '')
                                            <p class="transfer-request__fact-meta">{{ $backTime['label'] }}</p>
                                        @endif
                                    </article>
                                @endif

                                @if ($passengers !== '' || $luggage !== '' || $backParty !== '' || $backPassengers !== '' || $backLuggage !== '')
                                    <article class="transfer-request__fact transfer-request__fact--party">
                                        <p class="transfer-request__fact-label">Пассажиры и багаж</p>
                                        @if ($passengers !== '')
                                            <p class="transfer-request__fact-value">{{ $passengers }}</p>
                                        @endif
                                        @if ($luggage !== '')
                                            <p class="transfer-request__fact-meta">{{ $luggage }}</p>
                                        @endif
                                        @if ($backPassengers !== '' || $backLuggage !== '')
                                            <p class="transfer-request__fact-meta">Обратно: {{ trim($backPassengers.($backPassengers !== '' && $backLuggage !== '' ? '; ' : '').$backLuggage) }}</p>
                                        @elseif ($backParty !== '')
                                            <p class="transfer-request__fact-meta">Обратно: {{ $backParty }}</p>
                                        @endif
                                    </article>
                                @endif
                            </div>

                            @if ($wishRows !== [])
                                <div class="transfer-request__wishes">
                                    @foreach ($wishRows as $row)
                                        <p><span>{{ $row['label'] }}</span>{{ $row['value'] }}</p>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endif

                    <section class="transfer-request__card" aria-labelledby="transfer-request-title">
                        <div class="transfer-request__tag">{!! $icon('eye') !!}Контакт</div>
                        <div class="transfer-request__step">Шаг 2 из 2 · Контакты</div>
                        <h1 id="transfer-request-title">Как с вами связаться?</h1>
                        <p class="transfer-request__lead">Оставьте имя и номер телефона. Мы напишем в выбранный мессенджер, чтобы согласовать трансфер.</p>

                        @if (session('status'))
                            <p class="transfer-request__status transfer-request__status--ok" role="status">{{ session('status') }}</p>
                        @endif

                        @if ($hasContactErrors)
                            <div class="transfer-request__status transfer-request__status--error" role="alert">
                                <p>{{ session('transfer_error', 'Проверьте поля с сообщениями. Заявка не отправлена.') }}</p>
                                @foreach ($tripErrors as $message)
                                    <p>{{ $message }}</p>
                                @endforeach
                            </div>
                        @endif

                        <form class="transfer-request__form" method="post" action="{{ route('transfer.request.store') }}" novalidate data-request-form>
                            @csrf
                            <div class="hp-field" aria-hidden="true">
                                <label>Сайт<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
                            </div>
                            @if (! empty($draft['personal_data_consent']))
                                <input type="hidden" name="personal_data_consent" value="1">
                            @endif

                            <label class="transfer-request__field {{ $fieldError('name') ? 'is-invalid' : '' }}">
                                <span>Ваше имя</span>
                                <span class="transfer-request__input-wrap">
                                    {!! $icon('user') !!}
                                    <input name="name" value="{{ old('name') }}" maxlength="120" autocomplete="name" placeholder="Как к вам обращаться" required>
                                </span>
                                @if ($message = $fieldError('name'))
                                    <span class="transfer-request__error">{{ $message }}</span>
                                @endif
                            </label>

                            <label class="transfer-request__field {{ $fieldError('phone') ? 'is-invalid' : '' }}">
                                <span>Номер телефона</span>
                                <span class="transfer-request__input-wrap">
                                    {!! $icon('phone') !!}
                                    <input name="phone" type="tel" value="{{ old('phone') }}" maxlength="40" autocomplete="tel" inputmode="tel" data-phone placeholder="+ код страны и номер" required>
                                </span>
                                @if ($message = $fieldError('phone'))
                                    <span class="transfer-request__error">{{ $message }}</span>
                                @endif
                            </label>

                            <fieldset class="transfer-request__messengers {{ $fieldError('messenger') ? 'is-invalid' : '' }}">
                                <legend>Куда удобнее написать?</legend>
                                <div class="transfer-request__choices">
                                    <label class="transfer-request__choice">
                                        <input type="radio" name="messenger" value="WhatsApp" @checked($selectedMessenger === 'WhatsApp') required>
                                        {!! $icon('message') !!}
                                        WhatsApp
                                    </label>
                                    <label class="transfer-request__choice">
                                        <input type="radio" name="messenger" value="Telegram" @checked($selectedMessenger === 'Telegram') required>
                                        {!! $icon('send') !!}
                                        Telegram
                                    </label>
                                </div>
                                @if ($message = $fieldError('messenger'))
                                    <span class="transfer-request__error">{{ $message }}</span>
                                @endif
                            </fieldset>

                            @if ($needsOutboundFlight)
                                <label class="transfer-request__field transfer-request__field--wide {{ $fieldError('flight_number') ? 'is-invalid' : '' }}">
                                    <span>Номер рейса</span>
                                    <span class="transfer-request__input-wrap">
                                        <input name="flight_number" value="{{ old('flight_number') }}" maxlength="20" autocomplete="off" placeholder="Например, IB123" required>
                                    </span>
                                    <span class="transfer-request__note">Нужен для встречи в аэропорту. Время поездки — местное время прилёта, не время подачи автомобиля.</span>
                                    @if ($message = $fieldError('flight_number'))
                                        <span class="transfer-request__error">{{ $message }}</span>
                                    @endif
                                </label>
                            @endif

                            @if ($needsReturnFlight)
                                <label class="transfer-request__field transfer-request__field--wide {{ $fieldError('return_flight_number') ? 'is-invalid' : '' }}">
                                    <span>Номер обратного рейса</span>
                                    <span class="transfer-request__input-wrap">
                                        <input name="return_flight_number" value="{{ old('return_flight_number') }}" maxlength="20" autocomplete="off" required>
                                    </span>
                                    @if ($message = $fieldError('return_flight_number'))
                                        <span class="transfer-request__error">{{ $message }}</span>
                                    @endif
                                </label>
                            @endif

                            <p class="transfer-request__consent-note">Согласие на обработку заявки — на первом шаге.</p>
                            @if ($message = $fieldError('personal_data_consent'))
                                <p class="transfer-request__error transfer-request__error--full">{{ $message }}</p>
                            @endif

                            <button class="transfer-request__submit" type="submit" data-send>
                                Отправить заявку
                                {!! $icon('arrow') !!}
                            </button>
                            <p class="transfer-request__status-line" data-request-status role="status"></p>
                        </form>
                    </section>
                </div>
            @elseif (session('status'))
                <div class="transfer-request__shell">
                    <section class="transfer-request__card">
                        <div class="transfer-request__tag">{!! $icon('eye') !!}Контакт</div>
                        <h1>Заявка отправлена</h1>
                        <p class="transfer-request__status transfer-request__status--ok" role="status">{{ session('status') }}</p>
                        <p class="transfer-request__empty">
                            <a class="transfer-request__submit" href="{{ route('home') }}">Вернуться на главную</a>
                        </p>
                    </section>
                </div>
            @else
                <div class="transfer-request__shell">
                    <section class="transfer-request__card">
                        <h1>Оформление заявки</h1>
                        <div class="transfer-request__empty">
                            <p>Сначала укажите маршрут и параметры поездки.</p>
                            <a class="transfer-request__submit" href="{{ route('home') }}">Перейти к форме</a>
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </section>
    <script>
        document.querySelectorAll("[data-phone]").forEach((input) => {
            input.addEventListener("input", () => {
                const cleaned = input.value.replace(/[^\d+\s()-]/g, "");

                if (cleaned !== input.value) {
                    input.value = cleaned;
                }
            });
        });

        const form = document.querySelector("[data-request-form]");

        if (form) {
            const status = form.querySelector("[data-request-status]");
            const button = form.querySelector("[data-send]");

            form.addEventListener("submit", (event) => {
                if (form.dataset.sent === "1") {
                    event.preventDefault();

                    return;
                }

                const name = form.querySelector('[name="name"]')?.value.trim() || "";
                const phone = form.querySelector('[name="phone"]')?.value.trim() || "";
                const messenger = form.querySelector('[name="messenger"]:checked');
                const messages = [];

                if (!name) {
                    messages.push("Укажите имя.");
                }

                if (!phone) {
                    messages.push("Укажите телефон.");
                }

                if (!messenger) {
                    messages.push("Выберите WhatsApp или Telegram.");
                }

                if (messages.length) {
                    event.preventDefault();

                    if (status) {
                        status.textContent = messages.join(" ");
                        status.classList.add("is-visible");
                    }

                    return;
                }

                form.dataset.sent = "1";

                if (button) {
                    button.disabled = true;
                    button.setAttribute("aria-busy", "true");
                }

                if (status) {
                    status.textContent = "Отправляем заявку…";
                    status.classList.add("is-visible");
                }
            });
        }
    </script>
@endsection
