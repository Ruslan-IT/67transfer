<div class="transfer-search__field transfer-search__field--place" data-place-field="{{ $key }}">
    <div class="transfer-search__control">
        @include('partials.transfer-glyph', ['icon' => 'map-pin', 'class' => 'transfer-search__icon'])
        <span class="transfer-search__control-copy">
            <label class="transfer-search__label" for="place-{{ $key }}">{{ $label }}</label>
            <input
                id="place-{{ $key }}"
                class="transfer-search__input"
                type="text"
                inputmode="search"
                data-place-input="{{ $key }}"
                placeholder="{{ $placeholder ?? '' }}"
                autocomplete="off"
                autocorrect="off"
                spellcheck="false"
                role="combobox"
                aria-autocomplete="list"
                aria-expanded="false"
                aria-controls="suggestions-{{ $key }}"
                aria-required="true"
            >
        </span>
        <button type="button" class="transfer-search__clear" data-clear-place="{{ $key }}" hidden aria-label="Очистить: {{ $label }}">@include('partials.transfer-glyph', ['icon' => 'close'])</button>
    </div>
    <ul id="suggestions-{{ $key }}" class="transfer-search__suggestions" role="listbox" hidden></ul>
    <p class="transfer-search__field-error" data-field-error hidden></p>
</div>
