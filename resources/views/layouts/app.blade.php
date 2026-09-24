<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'TRANSFER POINT: ЕВРОПА' }}</title>
    <meta name="description" content="{{ $description ?? 'Связываем путешественников с местными перевозчиками по всей Европе.' }}">
    @if (! empty($keywords))
        <meta name="keywords" content="{{ $keywords }}">
    @endif
    <link rel="icon" href="{{ asset('images/badges/europe.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

</head>
<body>

<div class="preloader">
    <div class="preloader__noise"></div>
    <div class="preloader__scanlines"></div>

    <div class="preloader__title">
        TRANSFER POINT
    </div>
</div>

<header class="header">
    <div class="container header__inner">

        <a href="{{ route('home') }}" class="logo">
            <span class="logo__transfer">TRANSFER POINT</span>
            <span class="logo__country">ЕВРОПА</span>
        </a>

        <button
            class="menu-button"
            type="button"
            aria-label="Открыть меню"
            aria-expanded="false"
        >
            <span></span>
            <span></span>
        </button>

    </div>
</header>

    <main>
        @yield('content')
    </main>

    <footer class="footer">
        <div class="container footer__grid">
            <div>
                @include('partials.wordmark', [
                    'label' => 'ЕВРОПА',
                    'badge' => 'images/badges/europe.png',
                    'href' => route('home'),
                    'inverse' => false,
                ])
                <p class="footer__descriptor">{{ $sitePage?->text('footer_descriptor', 'Связываем путешественников с местными перевозчиками') }}</p>
            </div>
            <div>
                <p class="footer__label">Направления</p>
                <nav class="footer__links">
                    @foreach ($navDestinations as $item)
                        <a href="{{ route('destination', $item) }}">{{ $item->name }}</a>
                    @endforeach
                </nav>
            </div>
            <div>
                <p class="footer__label">Сервис</p>
                <nav class="footer__links">
                    <a href="{{ route('destinations') }}">Выбрать направление</a>
                    <a href="{{ route('blog.index') }}">Блог</a>
                    <a href="{{ route('information') }}">Информация</a>
                    <a href="{{ route('contacts') }}">Контакты</a>
                    <a href="{{ route('information') }}#legal">Правовая оговорка</a>
                </nav>
            </div>
        </div>
        <div class="container footer__note">
            {{ $sitePage?->text('footer_note', 'Transfer Point предоставляет информацию и помогает связаться с перевозчиком. Саму перевозку выполняют независимые компании.') }}
        </div>
    </footer>

    <div class="menu-overlay">
        <div class="menu-overlay__top">
            <a href="{{ route('home') }}" class="logo logo--menu">
                <span class="logo__transfer">TRANSFER POINT</span>
                <span class="logo__country">ЕВРОПА</span>
            </a>
            <button class="menu-close" type="button" aria-label="Закрыть меню">
                <span></span>
                <span></span>
            </button>
        </div>
        <nav class="menu">
            <a href="{{ route('home') }}#transfers" class="menu__item">
                <span class="menu__number">01</span>
                <span class="menu__text">Трансферы</span>
                <span class="menu__arrow">↗</span>
            </a>
            <a href="{{ route('destinations') }}" class="menu__item">
                <span class="menu__number">02</span>
                <span class="menu__text">Направления</span>
                <span class="menu__arrow">↗</span>
            </a>
            <a href="{{ route('blog.index') }}" class="menu__item">
                <span class="menu__number">03</span>
                <span class="menu__text">Блог</span>
                <span class="menu__arrow">↗</span>
            </a>
            <a href="{{ route('information') }}" class="menu__item">
                <span class="menu__number">04</span>
                <span class="menu__text">Информация</span>
                <span class="menu__arrow">↗</span>
            </a>
            <a href="{{ route('contacts') }}" class="menu__item">
                <span class="menu__number">05</span>
                <span class="menu__text">Контакты</span>
                <span class="menu__arrow">↗</span>
            </a>
            <a href="{{ route('information') }}#legal" class="menu__item">
                <span class="menu__number">06</span>
                <span class="menu__text">О Европе</span>
                <span class="menu__arrow">↗</span>
            </a>
        </nav>
        <div class="menu-overlay__bottom">
            <span>МЕСТНАЯ ИНФОРМАЦИЯ О ТРАНСФЕРАХ</span>
            <span>ЕВРОПА · 2026</span>
        </div>
    </div>

    <script src="https://unpkg.com/split-type@0.3.4/umd/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3/dist/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3/dist/ScrollTrigger.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
