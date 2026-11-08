@php
    $footer = app(\App\Support\SiteFooter::class)->present();
@endphp

<footer class="home-footer" aria-label="Подвал сайта">
    <div class="container">
        <div class="home-footer__top">
            <div class="home-footer__brand">
                <img
                    class="home-footer__logo"
                    src="{{ $footer['logo'] }}"
                    alt="{{ $footer['logo_alt'] }}"
                    width="258"
                    height="25"
                >
                <p>{!! nl2br(e($footer['brand_text'])) !!}</p>
            </div>
            <div class="home-footer__contact">
                <span class="home-footer__label">{{ $footer['contact_label'] }}</span>
                @if ($footer['has_contacts'])
                    @if ($footer['phones'] !== [] || $footer['email'])
                        <div class="home-footer__contact-row">
                            @foreach ($footer['phones'] as $phone)
                                @if ($phone['href'])
                                    <a href="{{ $phone['href'] }}">{{ $phone['display'] }}</a>
                                @else
                                    <span>{{ $phone['display'] }}</span>
                                @endif
                            @endforeach
                            @if ($footer['email'])
                                @if ($footer['email']['href'])
                                    <a href="{{ $footer['email']['href'] }}">{{ $footer['email']['display'] }}</a>
                                @else
                                    <span>{{ $footer['email']['display'] }}</span>
                                @endif
                            @endif
                        </div>
                    @endif
                    @if ($footer['address'])
                        <p class="home-footer__address">{{ $footer['address'] }}</p>
                    @endif
                    @if ($footer['messengers'] !== [])
                        <div class="home-footer__messengers">
                            @foreach ($footer['messengers'] as $messenger)
                                <a href="{{ $messenger['url'] }}" target="_blank" rel="noopener noreferrer">
                                    <span class="home-footer__icon" style="--home-footer-icon: url('{{ $messenger['icon'] }}')" aria-hidden="true"></span>
                                    {{ $messenger['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                    @if ($footer['socials'] !== [])
                        <div class="home-footer__socials">
                            @foreach ($footer['socials'] as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer">
                                    <span class="home-footer__icon" style="--home-footer-icon: url('{{ $social['icon'] }}')" aria-hidden="true"></span>
                                    {{ $social['name'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                @elseif ($footer['fallback_enabled'])
                    <a class="home-footer__contact-fallback" href="{{ route('contacts') }}">{{ $footer['fallback_label'] }}</a>
                @endif
            </div>
        </div>
        <nav class="home-footer__legal" aria-label="Документы">
            <a href="{{ route('legal.privacy') }}">{{ $footer['privacy_label'] }}</a>
            <a href="{{ route('legal.consent') }}">{{ $footer['consent_label'] }}</a>
            <button type="button" class="home-footer__cookie" data-cookie-open>{{ $footer['cookie_label'] }}</button>
        </nav>
        <div class="home-footer__bottom">
            <span>{{ $footer['copyright'] }}</span>
            @if ($footer['owner'] !== '')
                <span>{{ $footer['owner'] }}</span>
            @endif
        </div>
    </div>
</footer>
