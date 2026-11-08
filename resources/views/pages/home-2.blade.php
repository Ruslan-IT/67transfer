@extends('layouts.app')

@php
    $screen = app(\App\Support\FirstScreen::class)->data();
    $media = fn (string $file) => asset('media/home-2/'.$file).'?v='.filemtime(public_path('media/home-2/'.$file));
@endphp

@section('content')
    <link rel="preload" as="image" href="{{ $media('cabin-wall.webp') }}">
    <link rel="preload" as="image" href="{{ $media('sky.webp') }}">
    <link rel="stylesheet" href="{{ asset('css/transfer-search.css') }}?v={{ filemtime(public_path('css/transfer-search.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/home-2.css') }}?v={{ filemtime(public_path('css/home-2.css')) }}">

    <div class="h2-flight" data-h2-flight>
        <div class="h2-stage" data-h2-stage aria-hidden="true">
            <div class="h2-sky" data-h2-sky>
                <img class="h2-sky__photo" src="{{ $media('sky.webp') }}" alt="" decoding="async">
                <div class="h2-sky__band h2-sky__band--far" data-h2-band-far>
                    <div class="h2-sky__track">
                        <img src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async">
                        <img src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async">
                    </div>
                </div>
                <div class="h2-sky__band h2-sky__band--near" data-h2-band-near>
                    <div class="h2-sky__track">
                        <img src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async">
                        <img src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async">
                    </div>
                </div>
                <div class="h2-sky__tint" data-h2-tint></div>
            </div>

            <div class="h2-wisps" data-h2-wisps>
                <img src="{{ $media('sky-deep.avif') }}" alt="" decoding="async" loading="lazy">
            </div>

            <div class="h2-cabin" data-h2-cabin>
                <div class="h2-cabin__layer h2-cabin__pane">
                    <img src="{{ $media('window-pane.webp') }}" alt="" decoding="async">
                </div>
                <div class="h2-cabin__layer h2-cabin__window">
                    <div class="h2-cabin__window-fit">
                        <div class="h2-cabin__window-box">
                            <div class="h2-cabin__shade" data-h2-shade>
                                <div class="h2-cabin__shade-sheet"></div>
                                <img class="h2-cabin__shade-handle" src="{{ $media('shade-handle.webp') }}" alt="" decoding="async">
                                <img class="h2-cabin__shade-handle h2-cabin__shade-handle--lit" src="{{ $media('shade-handle-lit.webp') }}" alt="" decoding="async" data-h2-shade-lit>
                            </div>
                            <div class="h2-cabin__glass"></div>
                        </div>
                    </div>
                </div>
                <div class="h2-cabin__layer h2-cabin__wall">
                    <img src="{{ $media('cabin-wall.webp') }}" alt="" decoding="async" fetchpriority="high">
                </div>
                <div class="h2-cabin__layer h2-cabin__glow" data-h2-glow>
                    <img src="{{ $media('cabin-wall-glow.webp') }}" alt="" decoding="async">
                </div>
            </div>

            <div class="h2-cloudwall" data-h2-cloudwall>
                <img class="h2-cloudwall__puff h2-cloudwall__puff--a" src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async" loading="lazy" data-h2-puff-a>
                <img class="h2-cloudwall__puff h2-cloudwall__puff--b" src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async" loading="lazy" data-h2-puff-b>
                <img class="h2-cloudwall__puff h2-cloudwall__puff--c" src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async" loading="lazy" data-h2-puff-c>
            </div>

            <div class="h2-stage__vignette" data-h2-vignette></div>
            <div class="h2-stage__haze" data-h2-haze></div>

            <div class="h2-stage__copy">
                <p class="h2-stage__title h2-stage__title--left" data-h2-left>
                    <span>Трансфер</span>
                    <span>по&nbsp;Европе</span>
                </p>
                <p class="h2-stage__title h2-stage__title--right" data-h2-right>
                    <span>Встретим</span>
                    <span>у&nbsp;трапа</span>
                </p>
                <p class="h2-stage__hint" data-h2-hint>
                    <span class="h2-stage__hint-line"></span>
                    Прокрутите вниз и&nbsp;начните поездку
                </p>
            </div>
        </div>

        <div class="h2-runway" data-h2-runway></div>

        <section class="h2-order" id="order" data-h2-order aria-labelledby="h2-order-title">
            <div class="container h2-order__inner">
                <div class="h2-order__head" data-h2-head>
                    <p class="h2-order__eyebrow">{{ $screen['eyebrow'] }}</p>
                    <h1 id="h2-order-title">{{ $screen['title'] }}</h1>
                    <p class="h2-order__lead">{{ $screen['lead'] }}. {{ $screen['note'] }}</p>
                    <ul class="h2-order__points">
                        @foreach ($screen['blocks'] as $block)
                            <li>
                                <strong>{{ $block['title'] }}</strong>
                                <span>{{ $block['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="h2-order__form" data-h2-form>
                    @include('partials.transfer-search', ['embedded' => true])
                    <div class="h2-order__veil" data-h2-veil aria-hidden="true"></div>
                </div>
            </div>
        </section>

        <div class="h2-horizon" aria-hidden="true"></div>
    </div>

    <section class="intro" id="transfers">
        <div class="container">
            <div class="intro__grid">
                <div>
                    <span class="section-number">{{ $page->text('intro_kicker', '01 / ТРАНСФЕР') }}</span>
                </div>
                <div>
                    <h2>{!! nl2br(e($page->text('intro_heading', "Передвигаться по Европе\nдолжно быть просто."))) !!}</h2>
                </div>
                <div>
                    <p>
                        {!! nl2br(e($page->text('intro_text', "Практичная информация о местных трансферах,\nмаршрутах, аэропортах и направлениях.\nВсё, что нужно, чтобы уверенно спланировать поездку."))) !!}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="destinations">
        <div class="container">
            <div class="section__intro">
                <p class="eyebrow">{{ $page->text('destinations_kicker', '02 / Направления') }}</p>
                <h2>{{ $page->text('destinations_heading', 'Один бренд. Много локальных точек.') }}</h2>
                <p>{{ $page->text('destinations_text', 'TRANSFER POINT — это не один автомобиль и не один перевозчик. Это система доступа к местным предложениям трансфера. Формула всегда одна: TRANSFER POINT: [НАПРАВЛЕНИЕ].') }}</p>
            </div>
            <div class="cards">
                @foreach ($destinations as $item)
                    <a class="card" href="{{ route('destination', $item) }}">
                        @if ($item->badgeUrl())
                            <img src="{{ $item->badgeUrl() }}" alt="" class="card__badge">
                        @endif
                        <span class="card__kicker">TRANSFER POINT:</span>
                        <strong>{{ $item->name }}</strong>
                        <span class="card__meta">{{ $item->tagline }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section section--muted">
        <div class="container split">
            <p class="eyebrow">{{ $page->text('how_kicker', '03 / Как это работает') }}</p>
            <div>
                <h2>{{ $page->text('how_heading', 'Информационный сервис, а не свой автопарк.') }}</h2>
                <ul class="facts">
                    @foreach ($page->list('how_items', ['Найдите местные варианты трансфера', 'Сравните доступные предложения', 'Запросите информацию о трансфере', 'Свяжитесь с местными перевозчиками']) as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <script src="{{ asset('js/home-2.js') }}?v={{ filemtime(public_path('js/home-2.js')) }}" defer></script>
@endsection
