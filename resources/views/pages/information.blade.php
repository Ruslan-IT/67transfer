@extends('layouts.app')

@section('content')
<section class="page-head">
    <div class="container">
        <p class="eyebrow">{{ $page->text('eyebrow', 'Информация') }}</p>
        <h1>{{ $page->text('heading', 'Связываем путешественников с местными перевозчиками') }}</h1>
        <p class="lede">{{ $page->text('lede', 'Transfer Point — это слой связи для местной информации о трансферах. Это не автопарк, не пул водителей и не оператор поездок.') }}</p>
    </div>
</section>

<section class="section">
    <div class="container prose">
        <h2>{{ $page->text('say_heading', 'Как мы говорим') }}</h2>
        <ul class="facts">
            @foreach ($page->list('say_items', ['Найдите местные варианты трансфера', 'Свяжитесь с местными перевозчиками', 'Запросите информацию о трансфере', 'Сравните доступные предложения', 'Независимые перевозчики']) as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>

        <h2>{{ $page->text('avoid_heading', 'Как мы не говорим') }}</h2>
        <ul class="facts facts--avoid">
            @foreach ($page->list('avoid_items', ['Наши водители', 'Мы вас отвезём', 'Наш автопарк', 'Забронируйте наш трансфер', 'Мы гарантируем поездку']) as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </div>
</section>

<section class="section section--muted" id="legal">
    <div class="container prose">
        <p class="eyebrow">{{ $page->text('legal_eyebrow', 'Правовая оговорка') }}</p>
        <h2>{{ $page->text('legal_heading', 'Знак ЕС: территориальная метка, а не официальная эмблема') }}</h2>
        {!! str($page->text('legal_p1'))->sanitizeHtml() !!}
        {!! str($page->text('legal_p2'))->sanitizeHtml() !!}
        <p class="fineprint">{{ $page->text('legal_fineprint') }}</p>
    </div>
</section>
@endsection
