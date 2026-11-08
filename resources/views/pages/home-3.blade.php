@extends('layouts.app')

@php
    $screen = app(\App\Support\FirstScreen::class)->data();
    $media = fn (string $file) => asset('media/home-3/'.$file).'?v='.filemtime(public_path('media/home-3/'.$file));
@endphp

@section('header_nav')
    <nav class="h3-nav" aria-label="Разделы страницы">
        <a class="h3-nav__link" href="#how">Как это работает</a>
        <a class="h3-nav__link" href="{{ route('information') }}">О сервисе</a>
        <a class="h3-nav__link" href="{{ route('contacts') }}">Вопросы</a>
    </nav>
    <details class="h3-nav-mobile" data-h3-nav-mobile>
        <summary class="h3-nav-mobile__toggle" aria-label="Разделы страницы">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                <path d="M5 7h14M5 12h10M5 17h6"></path>
            </svg>
        </summary>
        <nav class="h3-nav-mobile__panel" aria-label="Разделы страницы">
            <a href="#how">Как это работает</a>
            <a href="{{ route('information') }}">О сервисе</a>
            <a href="{{ route('contacts') }}">Вопросы</a>
        </nav>
    </details>
@endsection

@section('content')
    <link rel="preload" as="image" href="{{ $media('cabin-wall.webp') }}">
    <link rel="preload" as="image" href="{{ $media('sky.webp') }}">
    <link rel="stylesheet" href="{{ asset('css/transfer-search.css') }}?v={{ filemtime(public_path('css/transfer-search.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/home-3.css') }}?v={{ filemtime(public_path('css/home-3.css')) }}">

    <div class="h3-flight" data-h3-flight style="--h3-land-static: url('{{ $media('europe-aerial-2048.jpg') }}')">
        <div class="h3-stage" data-h3-stage aria-hidden="true">
            <div class="h3-sky" data-h3-sky>
                <img class="h3-sky__photo" src="{{ $media('sky.webp') }}" alt="" decoding="async">
                <div class="h3-sky__band h3-sky__band--far" data-h3-band-far>
                    <div class="h3-sky__track">
                        <img src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async">
                        <img src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async">
                    </div>
                </div>
                <div class="h3-sky__band h3-sky__band--near" data-h3-band-near>
                    <div class="h3-sky__track">
                        <img src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async">
                        <img src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async">
                    </div>
                </div>
            </div>

            <div class="h3-land" data-h3-land>
                <div class="h3-land__camera" data-h3-land-camera>
                    <div class="h3-land__drift" data-h3-land-drift>
                        <img
                            class="h3-land__photo"
                            src="{{ $media('europe-aerial-2048.jpg') }}"
                            srcset="{{ $media('europe-aerial-2048.jpg') }} 2048w, {{ $media('europe-aerial-3840.jpg') }} 3840w"
                            sizes="(orientation: portrait) 190vh, 115vw"
                            alt=""
                            decoding="async"
                            fetchpriority="low"
                        >
                    </div>
                </div>
                <div class="h3-land__mist">
                    <img src="{{ $media('sky-deep.avif') }}" alt="" decoding="async" loading="lazy">
                </div>
                <div class="h3-land__haze" data-h3-land-haze></div>
            </div>

            <div class="h3-wisps" data-h3-wisps>
                <img src="{{ $media('sky-deep.avif') }}" alt="" decoding="async" loading="lazy">
            </div>

            <div class="h3-cabin" data-h3-cabin>
                <div class="h3-cabin__layer h3-cabin__pane">
                    <img src="{{ $media('window-pane.webp') }}" alt="" decoding="async">
                </div>
                <div class="h3-cabin__layer h3-cabin__window">
                    <div class="h3-cabin__window-fit">
                        <div class="h3-cabin__window-box">
                            <div class="h3-cabin__shade" data-h3-shade>
                                <div class="h3-cabin__shade-sheet"></div>
                                <img class="h3-cabin__shade-handle" src="{{ $media('shade-handle.webp') }}" alt="" decoding="async">
                                <img class="h3-cabin__shade-handle h3-cabin__shade-handle--lit" src="{{ $media('shade-handle-lit.webp') }}" alt="" decoding="async" data-h3-shade-lit>
                            </div>
                            <div class="h3-cabin__glass"></div>
                        </div>
                    </div>
                </div>
                <div class="h3-cabin__layer h3-cabin__wall">
                    <div class="h3-cabin__wall-fit" style="--h3-wall: url('{{ $media('cabin-wall.webp') }}')">
                        <img src="{{ $media('cabin-wall.webp') }}" alt="" decoding="async" fetchpriority="high">
                    </div>
                </div>
                <div class="h3-cabin__layer h3-cabin__glow" data-h3-glow>
                    <img src="{{ $media('cabin-wall-glow.webp') }}" alt="" decoding="async">
                </div>
            </div>

            <div class="h3-cloudwall" data-h3-cloudwall>
                <img class="h3-cloudwall__puff h3-cloudwall__puff--a" src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async" loading="lazy" data-h3-puff-a>
                <img class="h3-cloudwall__puff h3-cloudwall__puff--b" src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async" loading="lazy" data-h3-puff-b>
                <img class="h3-cloudwall__puff h3-cloudwall__puff--c" src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async" loading="lazy" data-h3-puff-c>
            </div>

            <div class="h3-deck" data-h3-deck>
                @foreach (range(1, 7) as $mass)
                    <div class="h3-deck__mass h3-deck__mass--{{ $mass }}" data-h3-mass="{{ $mass }}"></div>
                @endforeach
                <img class="h3-deck__texture h3-deck__texture--a" src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async" loading="lazy" data-h3-texture-a>
                <img class="h3-deck__texture h3-deck__texture--b" src="{{ $media('clouds-strip.webp') }}" alt="" decoding="async" loading="lazy" data-h3-texture-b>
                <img class="h3-deck__veils" src="{{ $media('sky-deep.avif') }}" alt="" decoding="async" loading="lazy" data-h3-deck-veils>
            </div>

            <div class="h3-stage__fog" data-h3-fog></div>
            <div class="h3-stage__vignette" data-h3-vignette></div>
            <div class="h3-stage__tint" data-h3-tint></div>
            <div class="h3-stage__haze" data-h3-haze></div>

            <div class="h3-stage__copy">
                <p class="h3-stage__title h3-stage__title--left" data-h3-left>
                    <span>Трансфер</span>
                    <span>по&nbsp;Европе</span>
                </p>
                <p class="h3-stage__title h3-stage__title--right" data-h3-right>
                    <span>Встретим</span>
                    <span>у&nbsp;трапа</span>
                </p>
                <p class="h3-stage__hint" data-h3-hint>
                    <span class="h3-stage__hint-line"></span>
                    Прокрутите вниз и&nbsp;начните поездку
                </p>
            </div>
        </div>

        <div class="h3-runway" data-h3-runway></div>

        <section class="h3-order" id="order" data-h3-order aria-labelledby="h3-order-title">
            <div class="container h3-order__inner">
                <div class="h3-order__head" data-h3-head>
                    <p class="h3-order__eyebrow">{{ $screen['eyebrow'] }}</p>
                    <h1 id="h3-order-title">{{ $screen['title'] }}</h1>
                    <p class="h3-order__lead">{{ $screen['lead'] }}. {{ $screen['note'] }}</p>
                    <ul class="h3-order__points">
                        @foreach ($screen['blocks'] as $block)
                            <li>
                                <strong>{{ $block['title'] }}</strong>
                                <span>{{ $block['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="h3-order__form" data-h3-form>
                    @include('partials.transfer-search', ['embedded' => true])
                    <div class="h3-order__veil" data-h3-veil aria-hidden="true"></div>
                </div>
            </div>
        </section>

        <div class="h3-horizon" aria-hidden="true"></div>
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

    <section class="section section--muted" id="how">
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

    <script src="{{ asset('js/home-3.js') }}?v={{ filemtime(public_path('js/home-3.js')) }}" defer></script>
@endsection
