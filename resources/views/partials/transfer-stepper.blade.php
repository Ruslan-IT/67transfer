<div class="transfer-search__stepper" data-counter="{{ $name }}">
    <div class="transfer-search__stepper-copy">
        <p class="transfer-search__stepper-label">
            @isset($icon)
                @include('partials.transfer-glyph', ['icon' => $icon])
            @endisset
            {{ $label }}
        </p>
        @if (! empty($hint))
            <p class="transfer-search__stepper-hint">{{ $hint }}</p>
        @endif
    </div>
    <div class="transfer-search__stepper-controls">
        <button type="button" data-step="-1" aria-label="Меньше: {{ $label }}" @disabled(($value ?? 0) <= 0)>@include('partials.transfer-glyph', ['icon' => 'minus'])</button>
        <span data-count>{{ $value ?? 0 }}</span>
        <button type="button" data-step="1" aria-label="Больше: {{ $label }}">@include('partials.transfer-glyph', ['icon' => 'plus'])</button>
    </div>
</div>
