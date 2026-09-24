@extends('layouts.app')

@section('content')
<section class="page-head">
    <div class="container">
        <p class="eyebrow">{{ $page->text('eyebrow', 'Направления') }}</p>
        <h1>{{ $page->text('heading', 'Выберите направление') }}</h1>
        <p class="lede">{{ $page->text('lede', 'Локальные страницы отличаются территорией, а не новой дизайн-системой. Меняются знак, география и фото. Бренд остаётся тем же.') }}</p>
    </div>
</section>

<section class="section">
    <div class="container cards">
        @foreach ($destinations as $item)
            <a class="card card--photo" href="{{ route('destination', $item) }}">
                @if ($item->imageUrl())
                    <img class="card__photo" src="{{ $item->imageUrl() }}" alt="{{ $item->image_alt }}">
                @endif
                <span class="card__overlay">
                    @if ($item->badgeUrl())
                        <img src="{{ $item->badgeUrl() }}" alt="" class="card__badge">
                    @endif
                    <span class="card__kicker">TRANSFER POINT:</span>
                    <strong>{{ $item->name }}</strong>
                </span>
            </a>
        @endforeach
    </div>
</section>
@endsection
