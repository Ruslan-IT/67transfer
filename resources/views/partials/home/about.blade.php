@php
    $p = $section['payload'] ?? [];
    $facts = is_array($p['facts'] ?? null) ? $p['facts'] : [];
    $heading = (string) ($p['heading'] ?? '');
@endphp

<section class="home-about" @if (! empty($section['anchor'])) id="{{ $section['anchor'] }}" @endif>
    <div class="container home-about__grid">
        <div>
            <p class="home-kicker">{{ $p['kicker'] ?? '' }}</p>
            <h2>{!! nl2br(e($heading)) !!}</h2>
        </div>
        <div>
            <p>{{ $p['body'] ?? '' }}</p>
            @if ($facts !== [])
                <div class="home-about__facts">
                    @foreach ($facts as $fact)
                        @php
                            $text = is_array($fact) ? (string) ($fact['text'] ?? '') : (string) $fact;
                        @endphp
                        @if ($text !== '')
                            <span>
                                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7"></path></svg>
                                {{ $text }}
                            </span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
