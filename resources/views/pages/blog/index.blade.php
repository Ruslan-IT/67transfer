@extends('layouts.app')

@section('content')
<section class="page-head">
    <div class="container">
        <p class="eyebrow">Блог</p>
        <h1>Заметки о трансферах</h1>
        <p class="lede">Практичные материалы о маршрутах, аэропортах и местном транспорте в Европе.</p>
    </div>
</section>

<section class="section blog-list">
    <div class="container">
        @if ($posts->isEmpty())
            <p class="lede">Пока нет опубликованных статей.</p>
        @else
            <div class="blog-grid">
                @foreach ($posts as $post)
                    <article class="blog-card">
                        <a class="blog-card__media" href="{{ route('blog.show', $post) }}">
                            <img src="{{ $post->imageUrl() }}" alt="{{ $post->image_alt ?: $post->title }}">
                        </a>
                        <div class="blog-card__body">
                            <div class="blog-card__meta">
                                @if ($post->category)
                                    <span>{{ $post->category }}</span>
                                @endif
                                @if ($post->published_at)
                                    <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d.m.Y') }}</time>
                                @endif
                            </div>
                            <h2 class="blog-card__title">
                                <a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a>
                            </h2>
                            <p class="blog-card__excerpt">{{ $post->excerpt }}</p>
                            <a class="blog-card__link" href="{{ route('blog.show', $post) }}">
                                Читать
                                <span>↗</span>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            {{ $posts->links('pagination.transfer') }}
        @endif
    </div>
</section>
@endsection
