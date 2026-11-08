@php
    $p = $section['payload'] ?? [];
@endphp

<section class="home-cta" @if (! empty($section['anchor'])) id="{{ $section['anchor'] }}" @endif>
    <div class="container home-cta__row">
        <div>
            <p class="home-kicker">{{ $p['kicker'] ?? '' }}</p>
            <h2>{{ $p['heading'] ?? '' }}</h2>
            <p>{{ $p['text'] ?? '' }}</p>
        </div>
        <a class="home-cta__button" href="#request">
            {{ $p['button_label'] ?? 'Оставить заявку' }}
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18m-6-6 6 6-6 6"></path></svg>
        </a>
    </div>
</section>
