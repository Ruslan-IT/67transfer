@php
    $leg = $leg ?? 'outbound';
@endphp

<div class="transfer-search__party-grid">
    <div class="transfer-search__pop" data-pop="passengers" data-counter-group="{{ $leg }}" data-field="{{ $leg }}-passengers">
        <button type="button" class="transfer-search__picker transfer-search__pop-trigger" aria-expanded="false" aria-haspopup="dialog" aria-controls="passengers-panel-{{ $leg }}">
            @include('partials.transfer-glyph', ['icon' => 'passengers', 'class' => 'transfer-search__icon'])
            <span class="transfer-search__picker-copy">
                <span class="transfer-search__label">Пассажиры</span>
                <span class="transfer-search__picker-value is-placeholder" data-summary="passengers-{{ $leg }}">Укажите пассажиров</span>
            </span>
            @include('partials.transfer-glyph', ['icon' => 'chevron-down', 'class' => 'transfer-search__chevron'])
        </button>
        <div id="passengers-panel-{{ $leg }}" class="transfer-search__pop-panel" role="dialog" aria-label="Пассажиры" hidden>
            <div class="transfer-search__panel-head">
                <p>Пассажиры</p>
                <button type="button" data-panel-close aria-label="Закрыть">@include('partials.transfer-glyph', ['icon' => 'close'])</button>
            </div>
            @include('partials.transfer-stepper', ['name' => 'adults', 'label' => 'Взрослые', 'hint' => 'от 13 лет', 'value' => 0])
            @include('partials.transfer-stepper', ['name' => 'children', 'label' => 'Дети', 'hint' => '2–12 лет', 'value' => 0])
            @include('partials.transfer-stepper', ['name' => 'infants', 'label' => 'Младенцы', 'hint' => 'до 2 лет', 'value' => 0])
            <div class="transfer-search__ages" data-ages="{{ $leg }}"></div>
            <p class="transfer-search__note" data-age-note="{{ $leg }}" hidden></p>
            <button type="button" class="transfer-search__text-button" data-seats-toggle aria-expanded="false" aria-controls="seats-{{ $leg }}">Детские кресла</button>
            <div id="seats-{{ $leg }}" data-seats hidden>
                <p class="transfer-search__note">Кресло не выбирается автоматически по возрасту. Наличие нужного устройства согласовывается при обработке заявки.</p>
                @include('partials.transfer-stepper', ['name' => 'seatCarrier', 'label' => 'Автолюлька', 'value' => 0])
                @include('partials.transfer-stepper', ['name' => 'seatChild', 'label' => 'Детское кресло', 'value' => 0])
                @include('partials.transfer-stepper', ['name' => 'seatBooster', 'label' => 'Бустер', 'value' => 0])
                @include('partials.transfer-stepper', ['name' => 'seatOwn', 'label' => 'Своё кресло', 'value' => 0])
                <div class="transfer-search__measures" data-show-when="seats" hidden>
                    <label>Рост, см
                        <input data-detail="seatHeight" inputmode="numeric" maxlength="3" autocomplete="off">
                    </label>
                    <label>Вес, кг
                        <input data-detail="seatWeight" inputmode="numeric" maxlength="3" autocomplete="off">
                    </label>
                </div>
            </div>
            <button type="button" class="transfer-search__done" data-panel-done="passengers">Готово</button>
        </div>
        <p class="transfer-search__field-error" data-field-error hidden></p>
    </div>

    <div class="transfer-search__pop" data-pop="luggage" data-counter-group="{{ $leg }}" data-field="{{ $leg }}-luggage">
        <button type="button" class="transfer-search__picker transfer-search__pop-trigger" aria-expanded="false" aria-haspopup="dialog" aria-controls="luggage-panel-{{ $leg }}">
            @include('partials.transfer-glyph', ['icon' => 'suitcase', 'class' => 'transfer-search__icon'])
            <span class="transfer-search__picker-copy">
                <span class="transfer-search__label">Багаж</span>
                <span class="transfer-search__picker-value is-placeholder" data-summary="luggage-{{ $leg }}">Укажите багаж</span>
            </span>
            @include('partials.transfer-glyph', ['icon' => 'chevron-down', 'class' => 'transfer-search__chevron'])
        </button>
        <div id="luggage-panel-{{ $leg }}" class="transfer-search__pop-panel" role="dialog" aria-label="Багаж" hidden>
            <div class="transfer-search__panel-head">
                <p>Багаж</p>
                <button type="button" data-panel-close aria-label="Закрыть">@include('partials.transfer-glyph', ['icon' => 'close'])</button>
            </div>
            @include('partials.transfer-stepper', ['name' => 'suitcases', 'label' => 'Чемоданы и сумки', 'hint' => 'Багаж для багажника', 'value' => 0, 'icon' => 'suitcase'])
            <div data-show-when="suitcases" hidden>
                <fieldset class="transfer-search__choice">
                    <legend>Размер чемоданов</legend>
                    <label><input type="radio" name="suitcase-size-{{ $leg }}" value="regular" data-detail="suitcaseSize"> Обычный</label>
                    <label><input type="radio" name="suitcase-size-{{ $leg }}" value="large" data-detail="suitcaseSize"> Крупный</label>
                </fieldset>
                <label class="transfer-search__detail" data-show-when="suitcase-large" hidden>Размеры
                    <input data-detail="suitcaseDimensions" maxlength="80" autocomplete="off">
                </label>
            </div>
            @include('partials.transfer-stepper', ['name' => 'cabin', 'label' => 'Ручная кладь', 'hint' => 'Небольшой чемодан, сумка или рюкзак', 'value' => 0, 'icon' => 'cabin-bag'])
            @include('partials.transfer-stepper', ['name' => 'strollers', 'label' => 'Детские коляски', 'hint' => 'Прогулочная коляска или коляска с люлькой', 'value' => 0, 'icon' => 'stroller'])
            <div data-show-when="strollers" hidden>
                <label class="transfer-search__detail">Складывается ли
                    <select data-detail="strollerFolds">
                        <option value="">Не указано</option>
                        <option value="yes">Складывается</option>
                        <option value="no">Не складывается</option>
                    </select>
                </label>
                <label class="transfer-search__detail">Размеры в сложенном виде
                    <input data-detail="strollerDimensions" maxlength="80" autocomplete="off">
                </label>
            </div>
            @include('partials.transfer-stepper', ['name' => 'sports', 'label' => 'Спортинвентарь', 'hint' => 'Лыжи, сноуборд, велосипед и другое', 'value' => 0, 'icon' => 'skis'])
            <div data-show-when="sports" hidden>
                <label class="transfer-search__detail">Вид
                    <input data-detail="sportsType" maxlength="80" autocomplete="off">
                </label>
                <label class="transfer-search__detail">Чехлы и размеры
                    <input data-detail="sportsDimensions" maxlength="80" autocomplete="off">
                </label>
            </div>
            @include('partials.transfer-stepper', ['name' => 'wheelchairs', 'label' => 'Кресло-коляска', 'hint' => 'Средство передвижения, не дополнительный пассажир', 'value' => 0, 'icon' => 'wheelchair'])
            <div data-show-when="wheelchairs" hidden>
                <label class="transfer-search__detail">Складывается ли
                    <select data-detail="wheelchairFolds">
                        <option value="">Не указано</option>
                        <option value="yes">Складывается</option>
                        <option value="no">Не складывается</option>
                    </select>
                </label>
                <label class="transfer-search__checkline" data-show-when="wheelchairs">
                    <input type="checkbox" data-detail="wheelchairRide"> Нужен проезд в кресле
                </label>
                <label class="transfer-search__detail">Размеры
                    <input data-detail="wheelchairDimensions" maxlength="80" autocomplete="off">
                </label>
            </div>
            @include('partials.transfer-stepper', ['name' => 'other', 'label' => 'Другой багаж', 'hint' => 'Нестандартные вещи', 'value' => 0, 'icon' => 'bag'])
            <div data-show-when="other" hidden>
                <label class="transfer-search__detail">Что везёте
                    <input data-detail="otherDescription" maxlength="120" autocomplete="off">
                </label>
                <label class="transfer-search__detail">Размеры
                    <input data-detail="otherDimensions" maxlength="80" autocomplete="off">
                </label>
            </div>
            <p class="transfer-search__note">Крупный багаж — укажите размеры</p>
            <button type="button" class="transfer-search__done" data-panel-done="luggage">Готово</button>
        </div>
        <p class="transfer-search__field-error" data-field-error hidden></p>
    </div>
</div>
