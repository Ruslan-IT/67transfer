@extends('layouts.app')

@section('content')
    <article class="section legal-doc">
        <div class="container legal-doc__inner">
            <p class="eyebrow">Документ · версия {{ $document->version }}</p>
            <h1>{{ $document->title }}</h1>
            <p class="legal-doc__updated">Обновлено {{ $document->updated_at?->timezone(config('app.timezone'))->format('d.m.Y') }}</p>

            <section class="legal-doc__operator">
                <h2>Реквизиты оператора</h2>
                @if ($operator['name'] === '' && $operator['address'] === '' && $operator['email'] === '' && $operator['phone'] === '' && $operator['extra'] === '')
                    <p>Оператор ещё не опубликовал наименование, адрес и контакты. Эти сведения заполняются в панели управления и не подставляются автоматически.</p>
                @else
                    @if ($operator['name'] !== '')
                        <p>{{ $operator['name'] }}</p>
                    @endif
                    @if ($operator['address'] !== '')
                        <p>{{ $operator['address'] }}</p>
                    @endif
                    @if ($operator['email'] !== '')
                        <p><a href="mailto:{{ $operator['email'] }}">{{ $operator['email'] }}</a></p>
                    @endif
                    @if ($operator['phone'] !== '')
                        <p><a href="tel:{{ preg_replace('/[^\d+]/', '', $operator['phone']) }}">{{ $operator['phone'] }}</a></p>
                    @endif
                    @if ($operator['extra'] !== '')
                        <p>{{ $operator['extra'] }}</p>
                    @endif
                @endif
                @if ($operator['retention'] !== '')
                    <p>Срок хранения заявок, заданный оператором: {{ $operator['retention'] }} дн.</p>
                @else
                    <p>Автоматический срок удаления заявок оператором не задан.</p>
                @endif
            </section>

            <div class="legal-doc__body">
                {!! str($document->body)->sanitizeHtml() !!}
            </div>
        </div>
    </article>
@endsection
