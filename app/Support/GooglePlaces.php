<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Database\QueryException;

class GooglePlaces
{
    public function enabled(): bool
    {
        return $this->read('google_places.enabled') === '1';
    }

    public function language(): string
    {
        return $this->sanitizeLanguage($this->read('google_places.language') ?? 'ru');
    }

    public function hasBrowserKey(): bool
    {
        return $this->browserKey() !== null;
    }

    public function hasServerKey(): bool
    {
        return $this->serverKey() !== null;
    }

    public function browserKey(): ?string
    {
        return $this->secret('google_places.browser_api_key');
    }

    public function serverKey(): ?string
    {
        return $this->secret('google_places.server_api_key');
    }

    public function keyStatus(string $which): string
    {
        $key = $which === 'server' ? $this->serverKey() : $this->browserKey();

        if ($key === null) {
            return 'Не задан.';
        }

        $suffix = strlen($key) >= 4 ? substr($key, -4) : '';

        return 'Сохранён в зашифрованном виде'
            .($suffix !== '' ? ', окончание '.$suffix : '')
            .'. Полное значение скрыто.';
    }

    /**
     * Данные для браузера. Серверный ключ сюда не входит.
     *
     * @return array{active: bool, browserKey: ?string, language: string, reason: string}
     */
    public function clientConfig(): array
    {
        $browserKey = $this->browserKey();
        $enabled = $this->enabled();
        $active = $enabled && $browserKey !== null;

        return [
            'active' => $active,
            'browserKey' => $active ? $browserKey : null,
            'language' => $this->language(),
            'reason' => match (true) {
                $browserKey === null => 'missing_key',
                ! $enabled => 'disabled',
                default => 'ready',
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(array $input): void
    {
        Setting::put('google_places.enabled', ! empty($input['enabled']) ? '1' : '0');
        Setting::put('google_places.language', $this->sanitizeLanguage((string) ($input['language'] ?? 'ru')));

        $this->storeSecret(
            'google_places.browser_api_key',
            $input['browser_api_key'] ?? null,
            ! empty($input['clear_browser_key']),
        );
        $this->storeSecret(
            'google_places.server_api_key',
            $input['server_api_key'] ?? null,
            ! empty($input['clear_server_key']),
        );
    }

    private function storeSecret(string $key, mixed $value, bool $clear): void
    {
        $secret = is_string($value) ? trim($value) : '';

        if ($secret !== '') {
            Setting::put($key, $secret, true);

            return;
        }

        if ($clear) {
            Setting::forget($key);
        }
    }

    private function sanitizeLanguage(string $language): string
    {
        $language = trim($language);

        return preg_match('/^[a-z]{2}(-[A-Za-z0-9]{2,8})?$/', $language) === 1 ? $language : 'ru';
    }

    private function secret(string $key): ?string
    {
        $value = $this->read($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function read(string $key): ?string
    {
        try {
            return Setting::get($key);
        } catch (QueryException) {
            return null;
        }
    }
}
