@php
    $href = $href ?? route('home');
    $label = $label ?? 'ЕВРОПА';
    $badge = $badge ?? 'images/badges/europe.png';
    $inverse = $inverse ?? false;
@endphp
<a class="wordmark {{ $inverse ? 'wordmark--inverse' : '' }}" href="{{ $href }}">
    <img class="wordmark__badge" src="{{ asset($badge) }}" alt="" width="48" height="48">
    <span class="wordmark__text">
        <span class="wordmark__primary">TRANSFER POINT:</span>
        <span class="wordmark__secondary">{{ $label }}</span>
    </span>
</a>
