@extends('layouts.app')

@section('content')
<article class="article">
    <section class="page-head article__head">
        <div class="container">
            <p class="eyebrow">
                <a href="{{ route('blog.index') }}">Блог</a>
                @if ($post->category)
                    · {{ $post->category }}
                @endif
            </p>
            <h1 class="article__title">{{ $post->headingText() }}</h1>
            @if ($post->published_at)
                <p class="article__date">
                    <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d.m.Y') }}</time>
                </p>
            @endif
        </div>
    </section>

    @if ($post->imageUrl())
        <div class="container">
            <figure class="article__cover">
                <img src="{{ $post->imageUrl() }}" alt="{{ $post->image_alt ?: $post->title }}">
            </figure>
        </div>
    @endif

    <section class="section">
        <div class="container article__content prose">
            {!! str($post->content)->sanitizeHtml() !!}
        </div>
        <div class="container">
            <a class="blog-card__link" href="{{ route('blog.index') }}">
                Все статьи
                <span>↗</span>
            </a>
        </div>
    </section>
</article>
@endsection
