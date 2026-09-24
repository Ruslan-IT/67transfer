@extends('layouts.app')

@section('content')
<section class="hero hero--local">
    <div class="container hero__grid">
        <div>
            <p class="eyebrow">Местная информация о трансферах</p>
            <h1>{{ $destination->title }}</h1>
            <div class="lede">{!! str($destination->content)->sanitizeHtml() !!}</div>
        </div>
        <figure class="photo-frame">
            @if ($destination->imageUrl())
                <img src="{{ $destination->imageUrl() }}" alt="{{ $destination->image_alt }}">
            @endif
            <figcaption>
                @if ($destination->territory)
                    <span>{{ mb_strtoupper($destination->territory) }}</span>
                @endif
                {{ $destination->tagline }}
            </figcaption>
        </figure>
    </div>
</section>

<section class="section" id="request">
    <div class="container request">
        <form class="panel" method="post" action="{{ route('destination.request', $destination) }}">
            @csrf
            <p class="eyebrow">Запрос</p>
            <h2>Запросить информацию о трансфере</h2>

            @if (session('status'))
                <p class="notice">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <p class="notice notice--error">Проверьте поля и попробуйте ещё раз.</p>
            @endif

            <label>
                <span>Откуда</span>
                <input type="text" name="from" value="{{ old('from', $destination->from_default) }}" required>
            </label>
            <label>
                <span>Куда</span>
                <input type="text" name="to" value="{{ old('to', $destination->to_default) }}" required>
            </label>
            <label>
                <span>Дата</span>
                <input type="date" name="date" value="{{ old('date', '2026-09-12') }}" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="you@email.com" required>
            </label>
            <button class="button" type="submit">Запросить варианты <span>↗</span></button>
            <p class="fineprint">Transfer Point предоставляет информацию и помогает связаться с перевозчиком. Саму перевозку выполняют независимые компании.</p>
        </form>

        <div>
            <p class="eyebrow">Маршруты</p>
            <h2>Аэропорт · город · курорт</h2>
            <ul class="facts">
                @foreach ($destination->routeList() as $route)
                    <li>{{ $route }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
@endsection
