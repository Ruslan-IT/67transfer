@extends('layouts.app')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
@section('content')
<section class="page-head">
    <div class="container">
        <p class="eyebrow">{{ $page->text('eyebrow', 'Контакты') }}</p>
        <h1>{{ $page->text('heading', 'Свяжитесь с нами') }}</h1>
        <p class="lede">{{ $page->text('lede', 'Задайте вопрос по трансферу — мы свяжем вас с независимыми местными перевозчиками.') }}</p>
        <button class="button" type="button" data-contact-open>
            {{ $page->text('button_text', 'Задать вопрос') }}
            <span>↗</span>
        </button>
    </div>
</section>

<section class="section contacts">
    <div class="container">

        <ul class="contacts__list">

            @if ($page->text('email'))
                <li>
                    @if ($page->mailtoUrl())
                        <a class="contact-card" href="{{ $page->mailtoUrl() }}">
                            @else
                                <div class="contact-card">
                                    @endif

                                    <span class="contact-card__icon" aria-hidden="true">
                    <i class="fa-solid fa-envelope"></i>
                </span>

                                    <span class="contact-card__label">Email</span>
                                    <strong>{{ $page->text('email') }}</strong>

                                @if ($page->mailtoUrl())
                        </a>
                @else
            </div>
                    @endif
                </li>
            @endif


        @if ($page->text('phone'))
            <li>
                @if ($page->telUrl())
                    <a class="contact-card" href="{{ $page->telUrl() }}">
                        @else
                            <div class="contact-card">
                                @endif

                                <span class="contact-card__icon" aria-hidden="true">
                        <i class="fa-solid fa-phone"></i>
                    </span>

                                <span class="contact-card__label">Телефон</span>
                                <strong>{{ $page->text('phone') }}</strong>

                            @if ($page->telUrl())
                    </a>
                    @else
                        </div>
                @endif
            </li>
        @endif


        @if ($page->whatsappUrl())
            <li>
                <a
                    class="contact-card"
                    href="{{ $page->whatsappUrl() }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span class="contact-card__icon" aria-hidden="true">
                        <i class="fa-brands fa-whatsapp"></i>
                    </span>

                    <span class="contact-card__label">WhatsApp</span>
                    <strong>{{ $page->text('whatsapp') }}</strong>
                </a>
            </li>
        @endif


        @if ($page->telegramUrl())
            <li>
                <a
                    class="contact-card"
                    href="{{ $page->telegramUrl() }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span class="contact-card__icon" aria-hidden="true">
                        <i class="fa-brands fa-telegram"></i>
                    </span>

                    <span class="contact-card__label">Telegram</span>
                    <strong>{{ $page->text('telegram') }}</strong>
                </a>
            </li>
        @endif


        @if ($page->text('address'))
            <li>
                <div class="contact-card">
                    <span class="contact-card__icon" aria-hidden="true">
                        <i class="fa-solid fa-location-dot"></i>
                    </span>

                    <span class="contact-card__label">Адрес</span>
                    <strong>{{ $page->text('address') }}</strong>
                </div>
            </li>
            @endif

        </ul>



    @if ($page->text('extra_text'))
            <p class="contacts__extra">{{ $page->text('extra_text') }}</p>
        @endif
    </div>
</section>

@if ($page->mapIframeSrc())
    <section class="contacts-map" aria-label="Карта">
        <iframe
            src="{{ $page->mapIframeSrc() }}"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen
            title="Карта"
        ></iframe>
    </section>
@endif

<div class="modal" data-contact-modal hidden>
    <div class="modal__backdrop" data-contact-close></div>
    <div class="modal__dialog panel" role="dialog" aria-modal="true" aria-labelledby="contact-modal-title">
        <button class="modal__close" type="button" data-contact-close aria-label="Закрыть">
            <span></span>
            <span></span>
        </button>
        <p class="eyebrow">Вопрос</p>
        <h2 id="contact-modal-title">{{ $page->text('button_text', 'Задать вопрос') }}</h2>

        <p class="notice" data-contact-success hidden>Сообщение успешно отправлено</p>
        <p class="notice notice--error" data-contact-error hidden></p>

        <form data-contact-form method="post" action="{{ route('contacts.request') }}">
            @csrf
            <label>
                <span>Имя</span>
                <input type="text" name="name" required maxlength="120">
            </label>
            <label>
                <span>Email или телефон</span>
                <input type="text" name="contact" required maxlength="180">
            </label>
            <label>
                <span>Текст вопроса</span>
                <textarea name="message" rows="5" required maxlength="2000"></textarea>
            </label>
            <button class="button" type="submit">
                Отправить
                <span>↗</span>
            </button>
        </form>
    </div>
</div>
@endsection
