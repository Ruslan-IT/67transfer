@extends('layouts.app')

@section('header_nav')
    @if (! empty($homeNav))
        <nav class="home-nav" aria-label="Разделы страницы">
            @foreach ($homeNav as $item)
                <a class="home-nav__link" href="{{ $item['href'] }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>
    @endif
@endsection

@section('content')
    <div class="home-page">
        <div id="request">
            @include('partials.first-screen')
        </div>

        @foreach ($homeSections as $section)
            @include($section['partial'], ['section' => $section])
        @endforeach
    </div>
@endsection
