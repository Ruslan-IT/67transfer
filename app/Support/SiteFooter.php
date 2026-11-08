<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SiteFooter
{
    public const KEY = 'site_footer';

    public const DEFAULT_WHATSAPP_ICON = 'images/icons/messengers/whatsapp.svg';

    public const DEFAULT_TELEGRAM_ICON = 'images/icons/messengers/telegram.svg';

    public const DEFAULT_SOCIAL_ICON = 'images/icons/messengers/link.svg';

    public const DEFAULT_LOGO = 'images/brand/transfer-i-tochka_compact_white.svg';

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $stored = json_decode((string) Setting::get(self::KEY, ''), true);

        return $this->merge(is_array($stored) ? $stored : []);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function update(array $state): void
    {
        $previous = $this->data();
        $next = $this->merge($state);

        $next['logo'] = $this->replaceFile($next['logo'], $previous['logo'] ?? null, 'logo');
        $next['whatsapp']['icon'] = $this->replaceFile(
            $next['whatsapp']['icon'],
            $previous['whatsapp']['icon'] ?? null,
            'whatsapp.icon',
            true,
        );
        $next['telegram']['icon'] = $this->replaceFile(
            $next['telegram']['icon'],
            $previous['telegram']['icon'] ?? null,
            'telegram.icon',
            true,
        );

        $previousSocials = is_array($previous['socials'] ?? null) ? $previous['socials'] : [];
        $keptIcons = [];

        foreach ($next['socials'] as $index => $social) {
            $prevIcon = $previousSocials[$index]['icon'] ?? null;
            $next['socials'][$index]['icon'] = $this->replaceFile(
                $social['icon'] ?? null,
                is_string($prevIcon) ? $prevIcon : null,
                'socials.'.$index.'.icon',
                true,
            );

            if (filled($next['socials'][$index]['icon'])) {
                $keptIcons[] = $next['socials'][$index]['icon'];
            }
        }

        foreach ($previousSocials as $social) {
            $icon = is_array($social) ? ($social['icon'] ?? null) : null;

            if (is_string($icon) && Media::isManagedPath($icon) && ! in_array($icon, $keptIcons, true)) {
                Storage::disk('public')->delete($icon);
            }
        }

        Setting::put(self::KEY, json_encode($next, JSON_UNESCAPED_UNICODE));
    }

    /**
     * View-ready payload for the home footer.
     *
     * @return array<string, mixed>
     */
    public function present(): array
    {
        $data = $this->data();

        $phones = [];

        foreach (['phone', 'phone_extra'] as $key) {
            $item = $data[$key];

            if (! ($item['enabled'] ?? false)) {
                continue;
            }

            $display = trim((string) ($item['display'] ?? ''));

            if ($display === '') {
                continue;
            }

            $tel = trim((string) ($item['tel'] ?? ''));

            if ($tel === '') {
                $tel = preg_replace('/[^\d+]/', '', $display) ?? '';
            }

            $phones[] = [
                'display' => $display,
                'href' => $tel !== '' ? 'tel:'.$tel : null,
            ];
        }

        $email = null;

        if (($data['email']['enabled'] ?? false) && trim((string) $data['email']['display']) !== '') {
            $display = trim((string) $data['email']['display']);
            $mailto = trim((string) ($data['email']['mailto'] ?? ''));

            if ($mailto === '' || ! filter_var($mailto, FILTER_VALIDATE_EMAIL)) {
                $mailto = filter_var($display, FILTER_VALIDATE_EMAIL) ? $display : '';
            }

            $email = [
                'display' => $display,
                'href' => $mailto !== '' ? 'mailto:'.$mailto : null,
            ];
        }

        $address = null;

        if (($data['address']['enabled'] ?? false) && trim((string) $data['address']['text']) !== '') {
            $address = trim((string) $data['address']['text']);
        }

        $messengers = [];

        foreach (['whatsapp', 'telegram'] as $key) {
            $item = $data[$key];

            if (! ($item['enabled'] ?? false)) {
                continue;
            }

            $url = $this->messengerUrl($key, $item);

            if ($url === null) {
                continue;
            }

            $messengers[] = [
                'key' => $key,
                'label' => trim((string) ($item['label'] ?? '')) ?: ($key === 'whatsapp' ? 'WhatsApp' : 'Telegram'),
                'url' => $url,
                'icon' => $this->iconUrl($item['icon'] ?? null, $key === 'whatsapp' ? self::DEFAULT_WHATSAPP_ICON : self::DEFAULT_TELEGRAM_ICON),
            ];
        }

        $socials = [];

        foreach ($data['socials'] as $social) {
            if (! ($social['enabled'] ?? false)) {
                continue;
            }

            $name = trim((string) ($social['name'] ?? ''));
            $url = trim((string) ($social['url'] ?? ''));

            if ($name === '' || $url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $socials[] = [
                'name' => $name,
                'url' => $url,
                'icon' => $this->iconUrl($social['icon'] ?? null, self::DEFAULT_SOCIAL_ICON),
            ];
        }

        $hasContacts = $phones !== [] || $email !== null || $address !== null || $messengers !== [] || $socials !== [];

        $owner = '';

        if ($data['show_owner'] ?? true) {
            $custom = trim((string) ($data['owner_line'] ?? ''));

            if ($custom !== '') {
                $owner = $custom;
            } else {
                $operator = app(CookieConsent::class)->operator();
                $owner = trim(collect([
                    $operator['name'] ?? '',
                    $operator['address'] ?? '',
                ])->filter()->implode(' · '));
            }
        }

        return [
            'logo' => Media::url($data['logo'] ?: self::DEFAULT_LOGO) ?? asset(self::DEFAULT_LOGO),
            'logo_alt' => $data['logo_alt'],
            'brand_text' => $data['brand_text'],
            'contact_label' => $data['contact_label'],
            'phones' => $phones,
            'email' => $email,
            'address' => $address,
            'messengers' => $messengers,
            'socials' => $socials,
            'has_contacts' => $hasContacts,
            'fallback_enabled' => (bool) ($data['fallback']['enabled'] ?? true),
            'fallback_label' => $data['fallback']['label'],
            'privacy_label' => $data['legal']['privacy_label'],
            'consent_label' => $data['legal']['consent_label'],
            'cookie_label' => $data['legal']['cookie_label'],
            'copyright' => str_replace('{year}', (string) date('Y'), $data['copyright']),
            'owner' => $owner,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function merge(array $state): array
    {
        $defaults = $this->defaults();

        return [
            'logo' => $this->path($state['logo'] ?? $defaults['logo']) ?: self::DEFAULT_LOGO,
            'logo_alt' => $this->text($state['logo_alt'] ?? null, $defaults['logo_alt']),
            'brand_text' => $this->text($state['brand_text'] ?? null, $defaults['brand_text']),
            'contact_label' => $this->text($state['contact_label'] ?? null, $defaults['contact_label']),
            'phone' => $this->mergePhone($state['phone'] ?? [], $defaults['phone']),
            'phone_extra' => $this->mergePhone($state['phone_extra'] ?? [], $defaults['phone_extra']),
            'email' => $this->mergeEmail($state['email'] ?? [], $defaults['email']),
            'address' => [
                'enabled' => (bool) ($state['address']['enabled'] ?? $defaults['address']['enabled']),
                'text' => $this->optional($state['address']['text'] ?? null),
            ],
            'whatsapp' => $this->mergeMessenger($state['whatsapp'] ?? [], $defaults['whatsapp']),
            'telegram' => $this->mergeMessenger($state['telegram'] ?? [], $defaults['telegram']),
            'socials' => $this->mergeSocials($state['socials'] ?? null, $defaults['socials']),
            'fallback' => [
                'enabled' => (bool) ($state['fallback']['enabled'] ?? $defaults['fallback']['enabled']),
                'label' => $this->text($state['fallback']['label'] ?? null, $defaults['fallback']['label']),
            ],
            'legal' => [
                'privacy_label' => $this->text($state['legal']['privacy_label'] ?? null, $defaults['legal']['privacy_label']),
                'consent_label' => $this->text($state['legal']['consent_label'] ?? null, $defaults['legal']['consent_label']),
                'cookie_label' => $this->text($state['legal']['cookie_label'] ?? null, $defaults['legal']['cookie_label']),
            ],
            'copyright' => $this->text($state['copyright'] ?? null, $defaults['copyright']),
            'show_owner' => (bool) ($state['show_owner'] ?? $defaults['show_owner']),
            'owner_line' => $this->optional($state['owner_line'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $contacts = Page::query()->where('slug', 'contacts')->where('is_published', true)->first();
        $operator = app(CookieConsent::class)->operator();

        $phone = trim((string) ($contacts?->text('phone') ?: ($operator['phone'] ?? '') ?: '+34 600 000 000'));
        $email = trim((string) ($contacts?->text('email') ?: ($operator['email'] ?? '') ?: 'hello@transferpoint.eu'));
        $whatsapp = trim((string) ($contacts?->text('whatsapp') ?: '+34600000000'));
        $telegram = trim((string) ($contacts?->text('telegram') ?: 'transferpoint'));
        $address = trim((string) ($contacts?->text('address') ?: ''));

        return [
            'logo' => self::DEFAULT_LOGO,
            'logo_alt' => 'ТРАНСФЕР и точка!',
            'brand_text' => "Бесплатная заявка на поездку по Европе.\nДетали и стоимость согласуются с водителем.",
            'contact_label' => 'Связаться с нами',
            'phone' => [
                'enabled' => $phone !== '',
                'display' => $phone,
                'tel' => preg_replace('/[^\d+]/', '', $phone) ?? '',
            ],
            'phone_extra' => [
                'enabled' => false,
                'display' => '',
                'tel' => '',
            ],
            'email' => [
                'enabled' => $email !== '',
                'display' => $email,
                'mailto' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '',
            ],
            'address' => [
                'enabled' => $address !== '',
                'text' => $address,
            ],
            'whatsapp' => [
                'enabled' => $whatsapp !== '',
                'label' => 'WhatsApp',
                'phone' => preg_match('/^\+?\d[\d\s\-()]+$/', $whatsapp) === 1 ? $whatsapp : '',
                'url' => str_starts_with($whatsapp, 'http') ? $whatsapp : '',
                'icon' => null,
            ],
            'telegram' => [
                'enabled' => $telegram !== '',
                'label' => 'Telegram',
                'username' => ltrim(str_starts_with($telegram, 'http') ? '' : $telegram, '@'),
                'url' => str_starts_with($telegram, 'http') ? $telegram : '',
                'icon' => null,
            ],
            'socials' => [],
            'fallback' => [
                'enabled' => true,
                'label' => 'Задать вопрос',
            ],
            'legal' => [
                'privacy_label' => 'Политика обработки данных',
                'consent_label' => 'Согласие на обработку данных',
                'cookie_label' => 'Настройки cookie',
            ],
            'copyright' => '© {year} «ТРАНСФЕР и точка!»',
            'show_owner' => true,
            'owner_line' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @param  array<string, mixed>  $default
     * @return array{enabled: bool, display: string, tel: string}
     */
    private function mergePhone(array $incoming, array $default): array
    {
        $display = $this->optional($incoming['display'] ?? null) ?? '';

        return [
            'enabled' => (bool) ($incoming['enabled'] ?? $default['enabled']),
            'display' => $display !== '' ? $display : (string) ($default['display'] ?? ''),
            'tel' => $this->optional($incoming['tel'] ?? null)
                ?? (preg_replace('/[^\d+]/', '', $display !== '' ? $display : (string) ($default['display'] ?? '')) ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @param  array<string, mixed>  $default
     * @return array{enabled: bool, display: string, mailto: string}
     */
    private function mergeEmail(array $incoming, array $default): array
    {
        $display = $this->optional($incoming['display'] ?? null) ?? (string) ($default['display'] ?? '');
        $mailto = $this->optional($incoming['mailto'] ?? null) ?? '';

        if ($mailto === '' && filter_var($display, FILTER_VALIDATE_EMAIL)) {
            $mailto = $display;
        }

        return [
            'enabled' => (bool) ($incoming['enabled'] ?? $default['enabled']),
            'display' => $display,
            'mailto' => $mailto,
        ];
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    private function mergeMessenger(array $incoming, array $default): array
    {
        $merged = [
            'enabled' => (bool) ($incoming['enabled'] ?? $default['enabled']),
            'label' => $this->text($incoming['label'] ?? null, (string) $default['label']),
            'icon' => $this->path($incoming['icon'] ?? $default['icon'] ?? null),
        ];

        if (array_key_exists('phone', $default)) {
            $merged['phone'] = $this->optional($incoming['phone'] ?? null) ?? (string) ($default['phone'] ?? '');
            $merged['url'] = $this->optional($incoming['url'] ?? null) ?? (string) ($default['url'] ?? '');
        } else {
            $merged['username'] = ltrim($this->optional($incoming['username'] ?? null) ?? (string) ($default['username'] ?? ''), '@');
            $merged['url'] = $this->optional($incoming['url'] ?? null) ?? (string) ($default['url'] ?? '');
        }

        return $merged;
    }

    /**
     * @param  mixed  $incoming
     * @param  list<array<string, mixed>>  $default
     * @return list<array{name: string, url: string, enabled: bool, icon: ?string}>
     */
    private function mergeSocials(mixed $incoming, array $default): array
    {
        if (! is_array($incoming)) {
            return $default;
        }

        $items = [];

        foreach ($incoming as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = $this->optional($item['name'] ?? null) ?? '';
            $url = $this->optional($item['url'] ?? null) ?? '';

            if ($name === '' && $url === '') {
                continue;
            }

            $items[] = [
                'name' => $name !== '' ? $name : 'Соцсеть',
                'url' => $url,
                'enabled' => (bool) ($item['enabled'] ?? true),
                'icon' => $this->path($item['icon'] ?? null),
            ];
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function messengerUrl(string $key, array $item): ?string
    {
        $url = trim((string) ($item['url'] ?? ''));

        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        if ($key === 'whatsapp') {
            $digits = preg_replace('/\D+/', '', (string) ($item['phone'] ?? '')) ?? '';

            return $digits !== '' ? 'https://wa.me/'.$digits : null;
        }

        $username = ltrim(trim((string) ($item['username'] ?? '')), '@');

        return $username !== '' ? 'https://t.me/'.$username : null;
    }

    private function iconUrl(?string $path, string $fallback): string
    {
        return Media::url($path) ?? asset($fallback);
    }

    private function text(mixed $value, string $default): string
    {
        if (! is_string($value)) {
            return $default;
        }

        $value = trim($value);

        return $value === '' ? $default : $value;
    }

    private function optional(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function path(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim(str_replace('\\', '/', $value), '/');

        if ($value === '' || str_contains($value, '..')) {
            return null;
        }

        return $value;
    }

    private function replaceFile(?string $next, ?string $previous, string $field, bool $svg = false): ?string
    {
        if ($next !== null && $svg && Media::isManagedPath($next)) {
            $this->assertSafeSvg($next, $field);
        }

        if (
            $previous
            && $previous !== $next
            && Media::isManagedPath($previous)
            && ! in_array($previous, [self::DEFAULT_LOGO, self::DEFAULT_WHATSAPP_ICON, self::DEFAULT_TELEGRAM_ICON, self::DEFAULT_SOCIAL_ICON], true)
        ) {
            Storage::disk('public')->delete($previous);
        }

        return $next;
    }

    private function assertSafeSvg(string $path, string $field): void
    {
        $contents = Storage::disk('public')->get($path);
        $unsafe = ! is_string($contents)
            || ! preg_match('/<svg\b/i', $contents)
            || preg_match('/<script\b|<\/script|on[a-z]+\s*=|javascript:|foreignObject|<iframe\b|<object\b|<embed\b/i', $contents);

        if ($unsafe) {
            if (Media::isManagedPath($path)) {
                Storage::disk('public')->delete($path);
            }

            throw ValidationException::withMessages([
                'data.'.$field => 'Загрузите обычный SVG без скриптов и встроенных документов.',
            ]);
        }
    }
}
