@php
    $media = function (string $file): ?string {
        $path = public_path('media/v-class-scene-v2/'.$file);

        return is_file($path) ? asset('media/v-class-scene-v2/'.$file).'?v='.filemtime($path) : null;
    };

    $shots = collect([
        ['file' => 'scene-01-landing.mp4', 'start' => 0, 'end' => 0.14, 'focus' => '50%'],
        ['file' => 'scene-02-rollout.mp4', 'start' => 0.14, 'end' => 0.26, 'focus' => '60%'],
        ['file' => 'scene-03-meet.mp4', 'start' => 0.26, 'end' => 0.42, 'focus' => '10%'],
        ['file' => 'scene-04-luggage.mp4', 'start' => 0.42, 'end' => 0.56, 'focus' => '15%'],
        ['file' => 'scene-05-boarding.mp4', 'start' => 0.56, 'end' => 0.72, 'focus' => '100%'],
        ['file' => 'scene-06-door.mp4', 'start' => 0.72, 'end' => 0.84, 'focus' => '100%'],
        ['file' => 'scene-07-departure.mp4', 'start' => 0.84, 'end' => 1, 'focus' => '45%'],
    ])->map(fn (array $shot) => $shot + ['src' => $media($shot['file'])])
        ->filter(fn (array $shot) => $shot['src'] !== null)
        ->values();

    $posterDesktop = $media('scene-poster.jpg');
    $posterMobile = $media('scene-poster-mobile.jpg');
    $hasVideo = $shots->isNotEmpty();

    $posterStyle = collect([
        $posterDesktop ? "--poster-desktop: url('{$posterDesktop}')" : null,
        $posterMobile ? "--poster-mobile: url('{$posterMobile}')" : null,
    ])->filter()->implode('; ');

    $chapters = [
        ['start' => 0, 'end' => 0.26, 'title' => 'Прилёт в аэропорт', 'text' => 'Самолёт мягко касается полосы — путешествие начинается.'],
        ['start' => 0.26, 'end' => 0.42, 'title' => 'Встречаем вас у терминала', 'text' => 'Водитель ждёт у выхода и знакомится со всей семьёй.'],
        ['start' => 0.42, 'end' => 0.56, 'title' => 'Бережно загружаем багаж', 'text' => 'Чемоданы аккуратно уложены, руки свободны.'],
        ['start' => 0.56, 'end' => 0.84, 'title' => 'Комфортная посадка всей семьи', 'text' => 'Место для каждого — и для двух маленьких пассажиров.'],
        ['start' => 0.84, 'end' => 1, 'title' => 'Комфортный трансфер до места назначения', 'text' => 'Спокойная дорога от терминала до двери.'],
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
    <link rel="stylesheet" href="{{ asset('css/v-class-scene-v2.css') }}?v={{ filemtime(public_path('css/v-class-scene-v2.css')) }}">
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
                            data-src="{{ $shot['src'] }}"
                            style="--focus-x: {{ $shot['focus'] }}"
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
<script src="{{ asset('js/v-class-scene-v2.js') }}?v={{ filemtime(public_path('js/v-class-scene-v2.js')) }}"></script>
</body>
</html>
