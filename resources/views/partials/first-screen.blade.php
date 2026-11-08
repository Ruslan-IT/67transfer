@php
    $screen = app(\App\Support\FirstScreen::class)->data();
    $background = \App\Support\Media::url($screen['background']);
@endphp

<section class="first-screen">
    <div class="container first-screen__grid">
        <div class="first-screen__copy">
            @if ($background)
                <img class="first-screen__art" src="{{ $background }}" alt="">
                <div class="first-screen__scrim" aria-hidden="true"></div>
            @endif
            <p class="first-screen__eyebrow">{{ $screen['eyebrow'] }}</p>
            <h1>{{ $screen['title'] }}</h1>
            <p class="first-screen__lead">{{ $screen['lead'] }}</p>
            <p class="first-screen__note">{{ $screen['note'] }}</p>

            <ul class="first-screen__points">
                @foreach ($screen['blocks'] as $index => $block)
                    <li>
                        <span class="first-screen__icon">
                            @if ($icon = \App\Support\Media::url($block['icon']))
                                <img src="{{ $icon }}" alt="">
                            @else
                                @include('partials.first-screen-icon', ['icon' => $block['fallback']])
                            @endif
                        </span>
                        <span class="first-screen__point-copy">
                            <strong>{{ $block['title'] }}</strong>
                            <span>{{ $block['text'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        @include('partials.transfer-search', ['embedded' => true])
    </div>
</section>
