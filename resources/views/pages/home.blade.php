@extends('layouts.app')

@section('content')




    <!-- HERO -->
    <section class="hero">

        <div class="container hero__inner">

            <!-- LEFT -->
            <div class="hero__content">

                <div class="hero__badge">

                    <div class="hero__badge-stars">
                        <span>★</span>
                        <span>★</span>
                        <span>★</span>
                        <span>★</span>
                        <span>★</span>
                        <span>★</span>
                        <span>★</span>
                        <span>★</span>
                        <span>★</span>
                        <span>★</span>
                        <span>★</span>
                        <span>★</span>
                    </div>

                    <div class="hero__badge-center">
                        <span></span>
                    </div>

                </div>


                <div class="hero__text ">

                    <h1>{!! nl2br(e($page->text('hero_title', "Transfer Point:\nЕвропа"))) !!}</h1>

                    <div class="hero__subtitle">
                        {{ $page->text('hero_subtitle', 'Местная информация о трансферах в Европе') }}
                    </div>

                    <p class="hero__description">
                        {!! nl2br(e($page->text('hero_description', "Ваш ориентир по комфортным трансферам,\nмаршрутам и местному транспорту по всей Европе.\nПонятная информация. Простое планирование."))) !!}
                    </p>

                    <a href="#transfers" class="hero__button">
                        {{ $page->text('hero_button', 'Смотреть трансферы') }}
                        <span>↗</span>
                    </a>

                </div>

            </div>


            <!-- RIGHT -->
            <div class="hero__visual">

                <div class="hero__image-wrapper">

                    <img
                        src="{{ $page->heroImageUrl(asset('images/photos/airport-arrival.png')) }}"
                        alt="{{ $page->text('hero_image_alt', 'Трансфер в Европе') }}"
                        class="hero__image"
                    >

                    <div class="hero__image-label">
                        <span>01</span>
                        <span>{{ $page->text('hero_image_label', 'ЕВРОПА') }}</span>
                    </div>

                </div>

                <div class="hero__vertical-text">
                    TRANSFER POINT
                </div>

            </div>

        </div>

    </section>


    <!-- SIMPLE INFO -->
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
@endsection
