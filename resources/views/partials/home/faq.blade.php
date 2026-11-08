@php
    $p = $section['payload'] ?? [];
    $items = is_array($p['items'] ?? null) ? $p['items'] : [];
@endphp

<section class="home-faq" @if (! empty($section['anchor'])) id="{{ $section['anchor'] }}" @endif>
    <div class="container home-faq__layout">
        <div>
            <p class="home-kicker">{{ $p['kicker'] ?? '' }}</p>
            <h2>{{ $p['heading'] ?? '' }}</h2>
        </div>
        <div class="home-faq__list">
            @foreach ($items as $item)
                <details>
                    <summary>{{ $item['question'] ?? '' }}</summary>
                    <p>{{ $item['answer'] ?? '' }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
