<div class="cookie-banner" data-cookie-banner hidden>
    <div class="cookie-banner__card">
        <p data-cookie-banner-text></p>
        <div class="cookie-banner__actions">
            <button type="button" class="cookie-banner__primary" data-cookie-accept>Принять все</button>
            <button type="button" data-cookie-reject>Отклонить необязательные</button>
            <button type="button" data-cookie-configure>Настроить</button>
        </div>
    </div>
</div>

<div class="cookie-panel" data-cookie-panel hidden>
    <div class="cookie-panel__backdrop" data-cookie-close tabindex="-1" aria-hidden="true"></div>
    <div class="cookie-panel__dialog" role="dialog" aria-modal="true" aria-labelledby="cookie-panel-title">
        <div class="cookie-panel__head">
            <h2 id="cookie-panel-title">Настройки cookie</h2>
            <button type="button" class="cookie-panel__close" data-cookie-close aria-label="Закрыть">×</button>
        </div>
        <p class="cookie-panel__intro" data-cookie-settings-text></p>
        <fieldset class="cookie-panel__choices">
            <label class="cookie-choice">
                <input type="checkbox" checked disabled>
                <span>
                    <strong data-cookie-necessary-title>Необходимые</strong>
                    <small data-cookie-necessary-text></small>
                </span>
            </label>
            <label class="cookie-choice">
                <input type="checkbox" data-cookie-analytics>
                <span>
                    <strong data-cookie-analytics-title>Аналитические</strong>
                    <small data-cookie-analytics-text></small>
                </span>
            </label>
            <label class="cookie-choice">
                <input type="checkbox" data-cookie-marketing>
                <span>
                    <strong data-cookie-marketing-title>Маркетинговые</strong>
                    <small data-cookie-marketing-text></small>
                </span>
            </label>
        </fieldset>
        <div class="cookie-panel__actions">
            <button type="button" class="cookie-banner__primary" data-cookie-save>Сохранить настройки</button>
        </div>
    </div>
</div>
<script type="application/json" id="cookie-consent-config">@json($cookieConsent)</script>
<script src="{{ asset('js/cookie-consent.js') }}?v={{ filemtime(public_path('js/cookie-consent.js')) }}" defer></script>
