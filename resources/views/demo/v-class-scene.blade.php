@php
    $media = function (string $file): ?string {
        $path = public_path('media/v-class-scene/'.$file);

        return is_file($path) ? asset('media/v-class-scene/'.$file).'?v='.filemtime($path) : null;
    };

    $shots = collect([
        ['file' => 'scene-01-arrival.mp4', 'start' => 0, 'end' => 0.25, 'focus' => '42%'],
        ['file' => 'scene-02-luggage.mp4', 'start' => 0.25, 'end' => 0.5, 'focus' => '26%'],
        ['file' => 'scene-03-boarding.mp4', 'start' => 0.5, 'end' => 0.75, 'focus' => '45%'],
        ['file' => 'scene-04-departure.mp4', 'start' => 0.75, 'end' => 1, 'focus' => '55%'],
    ])->map(fn (array $shot) => $shot + ['src' => $media($shot['file']), 'mobile' => null])
        ->filter(fn (array $shot) => $shot['src'] !== null)
        ->values();

    if ($shots->isEmpty() && ($media('scene-desktop.mp4') || $media('scene-mobile.mp4'))) {
        $shots = collect([[
            'start' => 0,
            'end' => 1,
            'src' => $media('scene-desktop.mp4') ?? $media('scene-mobile.mp4'),
            'mobile' => $media('scene-mobile.mp4'),
        ]]);
    }

    $posterDesktop = $media('scene-poster.jpg');
    $posterMobile = $media('scene-poster-mobile.jpg');
    $hasVideo = $shots->isNotEmpty();

    $posterStyle = collect([
        $posterDesktop ? "--poster-desktop: url('{$posterDesktop}')" : null,
        $posterMobile ? "--poster-mobile: url('{$posterMobile}')" : null,
    ])->filter()->implode('; ');

    $chapters = [
        ['start' => 0, 'end' => 0.14, 'title' => 'Встреча у терминала', 'text' => 'Автомобиль уже ждёт у выхода из аэропорта.'],
        ['start' => 0.14, 'end' => 0.25, 'title' => 'Помощь с багажом', 'text' => 'Водитель выходит навстречу и берёт чемоданы.'],
        ['start' => 0.25, 'end' => 0.5, 'title' => 'Всё на своих местах', 'text' => 'Багаж аккуратно уложен в багажное отделение.'],
        ['start' => 0.5, 'end' => 0.66, 'title' => 'Место для каждого', 'text' => 'Дети рядом с родителями, питомцы удобно устроены в дорогу.'],
        ['start' => 0.66, 'end' => 0.75, 'title' => 'Тишина салона', 'text' => 'Двери мягко закрываются.'],
        ['start' => 0.75, 'end' => 1, 'title' => 'В путь', 'text' => 'Поездка начинается спокойно и без суеты.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="ru" class="scene-page">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Ваше путешествие начинается здесь</title>
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/v-class-scene.css') }}?v={{ filemtime(public_path('css/v-class-scene.css')) }}">
</head>
<body>
<main>
    <header class="scene-intro">
        <p class="scene-intro__eyebrow">Трансфер из аэропорта</p>
        <h1>Ваше путешествие начинается здесь</h1>
        <p class="scene-intro__lead">Встретим у терминала, поможем с багажом и довезём всю семью.</p>
        <div class="scene-intro__hint" aria-hidden="true">
            <i></i>
            <span>Прокрутите вниз</span>
        </div>
    </header>

    <section
        class="scene"
        data-scene
        data-state="{{ $hasVideo ? 'video' : 'placeholder' }}"
        aria-label="Семья отправляется на трансфере из аэропорта"
    >
        <div class="scene__stage">
            <div class="scene__frame" @if ($posterStyle !== '') style="{{ $posterStyle }}" @endif>
                @if ($hasVideo)
                    @foreach ($shots as $shot)
                        <video
                            class="scene__video"
                            data-scene-video
                            data-start="{{ $shot['start'] }}"
                            data-end="{{ $shot['end'] }}"
                            data-src-desktop="{{ $shot['src'] }}"
                            data-src-mobile="{{ $shot['mobile'] ?? $shot['src'] }}"
                            @isset ($shot['focus']) style="--focus-x: {{ $shot['focus'] }}" @endisset
                            muted
                            playsinline
                            preload="none"
                            disablepictureinpicture
                            disableremoteplayback
                            aria-hidden="true"
                            tabindex="-1"
                        ></video>
                    @endforeach
                @else
                    <div class="scene__placeholder" aria-hidden="true"></div>
                @endif
                <div class="scene__shade" aria-hidden="true"></div>
            </div>

            @unless ($hasVideo)
                <p class="scene__pending">Видео сцены готовится</p>
            @endunless

            <ol class="scene__chapters">
                @foreach ($chapters as $index => $chapter)
                    <li
                        class="scene__chapter{{ $index === 0 ? ' is-active' : '' }}"
                        data-chapter
                        data-start="{{ $chapter['start'] }}"
                        data-end="{{ $chapter['end'] }}"
                    >
                        <span class="scene__chapter-index">{{ sprintf('%02d', $index + 1) }} / {{ sprintf('%02d', count($chapters)) }}</span>
                        <h2>{{ $chapter['title'] }}</h2>
                        <p>{{ $chapter['text'] }}</p>
                    </li>
                @endforeach
            </ol>

            <div class="scene__progress" aria-hidden="true"><span data-scene-progress></span></div>
        </div>
    </section>

    <section class="scene-outro">
        <h2>Спокойная дорога — с первой минуты</h2>
        <p>Водитель встретит, поможет с багажом и детскими креслами, а поездка начнётся без ожидания.</p>
        <a href="{{ route('home') }}">Перейти на сайт</a>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/gsap@3/dist/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3/dist/ScrollTrigger.min.js"></script>
<script src="{{ asset('js/v-class-scene.js') }}?v={{ filemtime(public_path('js/v-class-scene.js')) }}"></script>
</body>
</html>
