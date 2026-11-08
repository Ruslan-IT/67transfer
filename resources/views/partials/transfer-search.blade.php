@php
    $config = app(\App\Support\GooglePlaces::class)->clientConfig();
    $embedded = $embedded ?? false;
@endphp

<section class="transfer-search{{ $embedded ? ' transfer-search--embedded' : '' }}" aria-label="Заявка на трансфер">
    <div class="{{ $embedded ? 'transfer-search__frame' : 'container' }}">
        <form
            class="transfer-search__card"
            method="post"
            action="{{ route('transfer.continue') }}"
            data-transfer-form
            data-trip="return"
            data-step="route"
            data-enquiry-url="{{ route('transfer.enquiry') }}"
            data-continue-url="{{ route('transfer.continue') }}"
            novalidate
        >
            @csrf
            <p class="transfer-search__step-label" data-step-label>Шаг 1 из 2 · Поездка</p>
            @unless ($config['active'])
                <p class="transfer-search__alert transfer-search__alert--warn" data-config-notice>
                    Автоподсказки адресов сейчас выключены. Адрес можно ввести вручную, форма от этого не останавливается.
                </p>
            @endunless

            <div data-step-panel="route">
                <div class="transfer-search__trip-row">
                    <div class="transfer-search__trip" role="radiogroup" aria-label="Тип поездки">
                        <label>
                            <input type="radio" name="trip" value="return" checked>
                            @include('partials.transfer-glyph', ['icon' => 'check', 'class' => 'transfer-search__check'])
                            Туда и обратно
                        </label>
                        <label>
                            <input type="radio" name="trip" value="oneway">
                            @include('partials.transfer-glyph', ['icon' => 'radio-circle', 'class' => 'transfer-search__radio'])
                            В одну сторону
                        </label>
                    </div>
                    <span class="transfer-search__trip-caption">Тип поездки</span>
                </div>

                <p class="transfer-search__section-title">Откуда - куда</p>

                <div class="transfer-search__route">
                    @include('partials.transfer-place-field', [
                        'key' => 'outbound-pickup',
                        'label' => 'Откуда',
                    ])
                    <button type="button" class="transfer-search__swap" data-swap-places aria-label="Поменять адреса местами">
                        @include('partials.transfer-glyph', ['icon' => 'swap-horizontal', 'class' => 'transfer-search__swap-icon transfer-search__swap-icon--horizontal'])
                        @include('partials.transfer-glyph', ['icon' => 'swap-vertical', 'class' => 'transfer-search__swap-icon transfer-search__swap-icon--vertical'])
                    </button>
                    @include('partials.transfer-place-field', [
                        'key' => 'outbound-dropoff',
                        'label' => 'Куда',
                    ])
                    <p class="transfer-search__helper">Аэропорт, отель, вокзал или адрес</p>
                </div>

                <div class="transfer-search__leg">
                    <p class="transfer-search__section-title">Туда</p>
                    <div class="transfer-search__when">
                        <div class="transfer-search__field" data-pop="date" data-when-key="outbound-date" data-field="outbound-date">
                            <button type="button" class="transfer-search__picker transfer-search__pop-trigger" aria-expanded="false" aria-haspopup="dialog" aria-controls="panel-outbound-date">
                                @include('partials.transfer-glyph', ['icon' => 'calendar-plain', 'class' => 'transfer-search__icon'])
                                <span class="transfer-search__picker-copy">
                                    <span class="transfer-search__label">Дата поездки</span>
                                    <span class="transfer-search__picker-value is-placeholder" data-when-label="outbound-date">Выберите дату</span>
                                </span>
                                @include('partials.transfer-glyph', ['icon' => 'chevron-down', 'class' => 'transfer-search__chevron'])
                            </button>
                            <div id="panel-outbound-date" class="transfer-search__pop-panel" role="dialog" aria-label="Дата" hidden>
                                <div class="transfer-search__panel-head">
                                    <p>Дата</p>
                                    <button type="button" data-panel-close aria-label="Закрыть">@include('partials.transfer-glyph', ['icon' => 'close'])</button>
                                </div>
                                <div data-calendar="outbound-date"></div>
                            </div>
                            <input type="hidden" data-when="outbound-date" value="">
                            <p class="transfer-search__field-error" data-field-error hidden></p>
                        </div>

                        <div class="transfer-search__field" data-pop="time" data-when-key="outbound-time" data-field="outbound-time">
                            <button type="button" class="transfer-search__picker transfer-search__pop-trigger" aria-expanded="false" aria-haspopup="dialog" aria-controls="panel-outbound-time">
                                @include('partials.transfer-glyph', ['icon' => 'clock', 'class' => 'transfer-search__icon'])
                                <span class="transfer-search__picker-copy">
                                    <span class="transfer-search__label" data-time-label="outbound-time">Время</span>
                                    <span class="transfer-search__picker-value is-placeholder" data-when-label="outbound-time">Выберите время</span>
                                </span>
                                @include('partials.transfer-glyph', ['icon' => 'chevron-down', 'class' => 'transfer-search__chevron'])
                            </button>
                            <div id="panel-outbound-time" class="transfer-search__pop-panel" role="dialog" aria-label="Время" hidden>
                                <div class="transfer-search__panel-head">
                                    <p>Время</p>
                                    <button type="button" data-panel-close aria-label="Закрыть">@include('partials.transfer-glyph', ['icon' => 'close'])</button>
                                </div>
                                <div class="transfer-search__time-cols" data-time-cols="outbound-time"></div>
                                <p class="transfer-search__note" data-time-note="outbound-time">Местное время места отправления</p>
                                <p class="transfer-search__field-error" data-panel-error hidden></p>
                                <button type="button" class="transfer-search__done" data-panel-done="time">Готово</button>
                            </div>
                            <input type="hidden" data-when="outbound-time" value="">
                            <p class="transfer-search__field-error" data-field-error hidden></p>
                        </div>
                    </div>
                </div>

                <div class="transfer-search__return" data-return-only>
                    <p class="transfer-search__section-title">Обратно</p>
                    <div class="transfer-search__when">
                        <div class="transfer-search__field" data-pop="date" data-when-key="return-date" data-field="return-date">
                            <button type="button" class="transfer-search__picker transfer-search__pop-trigger" aria-expanded="false" aria-haspopup="dialog" aria-controls="panel-return-date">
                                @include('partials.transfer-glyph', ['icon' => 'calendar-plain', 'class' => 'transfer-search__icon'])
                                <span class="transfer-search__picker-copy">
                                    <span class="transfer-search__label">Дата обратной поездки</span>
                                    <span class="transfer-search__picker-value is-placeholder" data-when-label="return-date">Выберите дату</span>
                                </span>
                                @include('partials.transfer-glyph', ['icon' => 'chevron-down', 'class' => 'transfer-search__chevron'])
                            </button>
                            <div id="panel-return-date" class="transfer-search__pop-panel" role="dialog" aria-label="Дата" hidden>
                                <div class="transfer-search__panel-head">
                                    <p>Дата</p>
                                    <button type="button" data-panel-close aria-label="Закрыть">@include('partials.transfer-glyph', ['icon' => 'close'])</button>
                                </div>
                                <div data-calendar="return-date"></div>
                            </div>
                            <input type="hidden" data-when="return-date" value="">
                            <p class="transfer-search__field-error" data-field-error hidden></p>
                        </div>

                        <div class="transfer-search__field" data-pop="time" data-when-key="return-time" data-field="return-time">
                            <button type="button" class="transfer-search__picker transfer-search__pop-trigger" aria-expanded="false" aria-haspopup="dialog" aria-controls="panel-return-time">
                                @include('partials.transfer-glyph', ['icon' => 'clock', 'class' => 'transfer-search__icon'])
                                <span class="transfer-search__picker-copy">
                                    <span class="transfer-search__label" data-time-label="return-time">Время</span>
                                    <span class="transfer-search__picker-value is-placeholder" data-when-label="return-time">Выберите время</span>
                                </span>
                                @include('partials.transfer-glyph', ['icon' => 'chevron-down', 'class' => 'transfer-search__chevron'])
                            </button>
                            <div id="panel-return-time" class="transfer-search__pop-panel" role="dialog" aria-label="Время" hidden>
                                <div class="transfer-search__panel-head">
                                    <p>Время</p>
                                    <button type="button" data-panel-close aria-label="Закрыть">@include('partials.transfer-glyph', ['icon' => 'close'])</button>
                                </div>
                                <div class="transfer-search__time-cols" data-time-cols="return-time"></div>
                                <p class="transfer-search__note" data-time-note="return-time">Местное время места отправления</p>
                                <p class="transfer-search__field-error" data-panel-error hidden></p>
                                <button type="button" class="transfer-search__done" data-panel-done="time">Готово</button>
                            </div>
                            <input type="hidden" data-when="return-time" value="">
                            <p class="transfer-search__field-error" data-field-error hidden></p>
                        </div>
                    </div>
                    <div class="transfer-search__return-meta">
                        <p data-return-summary>Обратные адреса повторяют прямой маршрут</p>
                        <button type="button" class="transfer-search__text-button" data-edit-return-route>@include('partials.transfer-glyph', ['icon' => 'edit'])Изменить маршрут</button>
                    </div>
                    <div class="transfer-search__return-route" data-return-route hidden>
                        @include('partials.transfer-place-field', [
                            'key' => 'return-pickup',
                            'label' => 'Откуда обратно',
                        ])
                        @include('partials.transfer-place-field', [
                            'key' => 'return-dropoff',
                            'label' => 'Куда обратно',
                        ])
                        <p class="transfer-search__helper">Аэропорт, отель, вокзал или адрес</p>
                    </div>
                    <button type="button" class="transfer-search__text-button" data-edit-return-party>@include('partials.transfer-glyph', ['icon' => 'edit'])Изменить пассажиров и багаж обратно</button>
                    <div data-return-party hidden>
                        <p class="transfer-search__return-party-title">Пассажиры и багаж обратной поездки</p>
                        @include('partials.transfer-party', ['leg' => 'return'])
                    </div>
                </div>

                @include('partials.transfer-party', ['leg' => 'outbound'])

                <div class="transfer-search__pop transfer-search__wishes" data-pop="wishes">
                    <button type="button" class="transfer-search__text-button transfer-search__pop-trigger" aria-expanded="false" aria-controls="wishes-panel">
                        @include('partials.transfer-glyph', ['icon' => 'plus'])
                        <span data-wishes-label>Особые пожелания</span>
                    </button>
                    <div id="wishes-panel" class="transfer-search__pop-panel" role="dialog" aria-label="Особые пожелания" hidden>
                        <div class="transfer-search__panel-head">
                            <p>Особые пожелания</p>
                            <button type="button" data-panel-close aria-label="Закрыть">@include('partials.transfer-glyph', ['icon' => 'close'])</button>
                        </div>
                        <label class="transfer-search__checkline"><input type="checkbox" data-wish="pet"> Питомец</label>
                        <label class="transfer-search__detail" data-wish-detail="pet" hidden>Кто едет и какая переноска
                            <input data-wish-text="pet_details" maxlength="180" autocomplete="off">
                        </label>
                        <label class="transfer-search__checkline"><input type="checkbox" data-wish="boarding_help"> Помощь при посадке</label>
                        <label class="transfer-search__detail" data-wish-detail="boarding_help" hidden>Какая помощь нужна
                            <input data-wish-text="boarding_help_details" maxlength="180" autocomplete="off">
                        </label>
                        <label class="transfer-search__checkline"><input type="checkbox" data-wish="extra_stop"> Дополнительная остановка</label>
                        <label class="transfer-search__detail" data-wish-detail="extra_stop" hidden>Где остановиться
                            <input data-wish-text="extra_stop_details" maxlength="180" autocomplete="off">
                        </label>
                        <label class="transfer-search__checkline"><input type="checkbox" data-wish="other"> Другие особенности</label>
                        <label class="transfer-search__detail" data-wish-detail="other" hidden>Комментарий
                            <textarea data-wish-text="other_details" maxlength="500" rows="3"></textarea>
                        </label>
                        <button type="button" class="transfer-search__done" data-panel-done="wishes">Готово</button>
                    </div>
                </div>

                <div class="transfer-search__consent" data-field="consent">
                    @include('partials.personal-data-consent')
                    <p class="transfer-search__field-error" data-field-error hidden></p>
                </div>

                <div class="transfer-search__footer">
                    <p class="transfer-search__free">Подача заявки бесплатна</p>
                    <div class="transfer-search__submit-wrap">
                        <button class="transfer-search__submit" type="submit" data-continue>Продолжить →</button>
                        <p class="transfer-search__submit-note">Далее — проверка поездки и контакты</p>
                    </div>
                </div>
            </div>

            <div class="transfer-search__success" data-success hidden>
                <p data-success-text></p>
            </div>

            <div class="transfer-search__backdrop" data-backdrop hidden></div>
            <p class="transfer-search__alert" data-form-alert hidden></p>
            @if (is_array($draft ?? null))
                <script type="application/json" id="transfer-search-draft">@json($draft)</script>
            @endif
            <script type="application/json" id="transfer-search-config">@json($config)</script>
        </form>
    </div>
    <script src="{{ asset('js/transfer-search.js') }}?v={{ filemtime(public_path('js/transfer-search.js')) }}"></script>
</section>
