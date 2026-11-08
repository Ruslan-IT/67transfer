@php
    $p = $section['payload'] ?? [];
    $steps = is_array($p['steps'] ?? null) ? $p['steps'] : [];
@endphp

<section class="home-process" @if (! empty($section['anchor'])) id="{{ $section['anchor'] }}" @endif>
    <div class="container">
        <p class="home-kicker">{{ $p['kicker'] ?? '' }}</p>
        <h2>{{ $p['heading'] ?? '' }}</h2>
        <div class="home-process__steps">
            @foreach ($steps as $index => $step)
                <article>
                    <span class="home-process__num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <h3>{{ $step['title'] ?? '' }}</h3>
                    <p>{{ $step['description'] ?? '' }}</p>
                </article>
            @endforeach
        </div>
        @if (! empty($p['note']))
            <p class="home-process__note">{{ $p['note'] }}</p>
        @endif
    </div>
</section>
