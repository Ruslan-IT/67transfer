@php
    $consentError = $consentError ?? null;
@endphp
<label class="consent-line {{ $consentError ? 'is-invalid' : '' }}">
    <input type="checkbox" name="personal_data_consent" value="1" required @checked(old('personal_data_consent'))>
    <span>
        Я даю согласие на обработку моих персональных данных, которые указываю в этой форме, для рассмотрения этого обращения.
        <a href="{{ route('legal.consent') }}">Текст согласия</a>.
        <a href="{{ route('legal.privacy') }}">Политика обработки персональных данных</a>.
    </span>
</label>
@if ($consentError)
    <p class="consent-line__error">{{ $consentError }}</p>
@endif
