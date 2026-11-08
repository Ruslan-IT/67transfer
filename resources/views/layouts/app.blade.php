<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <script>
        (function () {
            var theme = "light";
            try {
                var stored = localStorage.getItem("transfer-theme");
                if (stored === "dark" || stored === "light") {
                    theme = stored;
                }
            } catch (e) {}
            document.documentElement.setAttribute("data-theme", theme);
        })();
    </script>
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
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/home-footer.css') }}?v={{ filemtime(public_path('css/home-footer.css')) }}">
    @if (request()->routeIs('home', 'transfer.request'))
        <link rel="stylesheet" href="{{ asset('css/transfer-search.css') }}?v={{ filemtime(public_path('css/transfer-search.css')) }}">
    @endif
    @if (request()->routeIs('home'))
        <link rel="stylesheet" href="{{ asset('css/first-screen.css') }}?v={{ filemtime(public_path('css/first-screen.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ filemtime(public_path('css/home.css')) }}">
    @endif
    @if (request()->routeIs('transfer.request'))
        <link rel="stylesheet" href="{{ asset('css/transfer-request.css') }}?v={{ filemtime(public_path('css/transfer-request.css')) }}">
    @endif

    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">

</head>
<body>

{{--<div class="preloader">
    <div class="preloader__noise"></div>
    <div class="preloader__scanlines"></div>

    <div class="preloader__title">
        TRANSFER POINT
    </div>
</div>--}}

<header class="header">
    <div class="container header__inner">

        <a href="{{ route('home') }}" class="logo">

            <img class="logo-img" src="/logo.png" alt="">
        </a>@yield('header_nav')


        <div class="header__tools">
            <div class="theme-switch" role="group" aria-label="Тема оформления">
                <span class="theme-switch__thumb" aria-hidden="true"></span>
                <button class="theme-switch__btn" type="button" data-theme-choice="light" aria-label="Светлая тема" aria-pressed="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2.5v2.2M12 19.3v2.2M4.9 4.9l1.5 1.5M17.6 17.6l1.5 1.5M2.5 12h2.2M19.3 12h2.2M4.9 19.1l1.5-1.5M17.6 6.4l1.5-1.5"></path>
                    </svg>
                </button>
                <button class="theme-switch__btn" type="button" data-theme-choice="dark" aria-label="Тёмная тема" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 14.5A8 8 0 1 1 9.5 4 6.2 6.2 0 0 0 20 14.5z"></path>
                    </svg>
                </button>
            </div>
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

    </div>
</header>

    <main>
        @yield('content')
    </main>

    @include('partials.home-footer')

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
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @include('partials.cookie-consent')
</body>
</html>
