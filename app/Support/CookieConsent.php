<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Database\QueryException;

class CookieConsent
{
    /**
     * @return array<string, mixed>
     */
    public function publicConfig(): array
    {
        $enabledSetting = $this->raw('analytics.enabled');
        $enabled = $enabledSetting === null || $enabledSetting === '1';
        $service = $this->raw('analytics.service') ?: 'yandex';
        $counter = $this->normalizeCounterId($this->raw('analytics.counter_id'));

        return [
            'version' => $this->raw('cookie.notice_version') ?: '1',
            'banner' => $this->raw('cookie.banner_text') ?: $this->defaultBanner(),
            'settings' => $this->raw('cookie.settings_text') ?: $this->defaultSettings(),
            'categories' => [
                'necessary' => [
                    'title' => $this->raw('cookie.necessary_title') ?: 'Необходимые',
                    'text' => $this->raw('cookie.necessary_text') ?: 'Нужны для сессии, защиты форм и сохранения темы. Их нельзя отключить.',
                ],
                'analytics' => [
                    'title' => $this->raw('cookie.analytics_title') ?: 'Аналитические',
                    'text' => $this->raw('cookie.analytics_text') ?: 'Яндекс Метрика: статистика посещений. До вашего разрешения счётчик не загружается.',
                ],
                'marketing' => [
                    'title' => $this->raw('cookie.marketing_title') ?: 'Маркетинговые',
                    'text' => $this->raw('cookie.marketing_text') ?: 'Сейчас рекламные теги не установлены. Выбор сохраняется и ничего дополнительно не загружает.',
                ],
            ],
            'analytics' => [
                'enabled' => $enabled && $counter !== '',
                'service' => $service,
                'counterId' => ($enabled && $counter !== '') ? $counter : '',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formState(): array
    {
        $config = $this->publicConfig();

        return [
            'banner' => $config['banner'],
            'settings' => $config['settings'],
            'notice_version' => $config['version'],
            'necessary_title' => $config['categories']['necessary']['title'],
            'necessary_text' => $config['categories']['necessary']['text'],
            'analytics_title' => $config['categories']['analytics']['title'],
            'analytics_text' => $config['categories']['analytics']['text'],
            'marketing_title' => $config['categories']['marketing']['title'],
            'marketing_text' => $config['categories']['marketing']['text'],
            'analytics_enabled' => ($this->raw('analytics.enabled') ?? '1') === '1',
            'analytics_service' => $config['analytics']['service'],
            'analytics_counter_id' => $this->raw('analytics.counter_id') ?? '',
            'operator_name' => $this->raw('operator.name') ?? '',
            'operator_address' => $this->raw('operator.address') ?? '',
            'operator_email' => $this->raw('operator.email') ?? '',
            'operator_phone' => $this->raw('operator.phone') ?? '',
            'operator_extra' => $this->raw('operator.extra') ?? '',
            'retention_days' => $this->raw('personal_data.retention_days') ?? '',
        ];
    }

    private function raw(string $key): ?string
    {
        try {
            $row = Setting::query()->where('key', $key)->first();
        } catch (QueryException) {
            return null;
        }

        if (! $row || $row->value === null) {
            return null;
        }

        return $row->value;
    }

    /**
     * @return array{name: string, address: string, email: string, phone: string, extra: string, retention: string}
     */
    public function operator(): array
    {
        return [
            'name' => $this->raw('operator.name') ?? '',
            'address' => $this->raw('operator.address') ?? '',
            'email' => $this->raw('operator.email') ?? '',
            'phone' => $this->raw('operator.phone') ?? '',
            'extra' => $this->raw('operator.extra') ?? '',
            'retention' => $this->raw('personal_data.retention_days') ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function save(array $state): void
    {
        $pairs = [
            'cookie.banner_text' => $state['banner'] ?? '',
            'cookie.settings_text' => $state['settings'] ?? '',
            'cookie.notice_version' => $state['notice_version'] ?? '1',
            'cookie.necessary_title' => $state['necessary_title'] ?? '',
            'cookie.necessary_text' => $state['necessary_text'] ?? '',
            'cookie.analytics_title' => $state['analytics_title'] ?? '',
            'cookie.analytics_text' => $state['analytics_text'] ?? '',
            'cookie.marketing_title' => $state['marketing_title'] ?? '',
            'cookie.marketing_text' => $state['marketing_text'] ?? '',
            'analytics.enabled' => ! empty($state['analytics_enabled']) ? '1' : '0',
            'analytics.service' => $state['analytics_service'] ?? 'yandex',
            'analytics.counter_id' => $this->normalizeCounterId($state['analytics_counter_id'] ?? ''),
            'operator.name' => $state['operator_name'] ?? '',
            'operator.address' => $state['operator_address'] ?? '',
            'operator.email' => $state['operator_email'] ?? '',
            'operator.phone' => $state['operator_phone'] ?? '',
            'operator.extra' => $state['operator_extra'] ?? '',
            'personal_data.retention_days' => trim((string) ($state['retention_days'] ?? '')),
        ];

        foreach ($pairs as $key => $value) {
            Setting::put($key, is_string($value) ? $value : (string) $value, false);
        }
    }

    private function normalizeCounterId(mixed $value): string
    {
        $counter = trim(is_string($value) || is_numeric($value) ? (string) $value : '');

        return preg_match('/^\d{4,15}$/', $counter) === 1 ? $counter : '';
    }

    private function defaultBanner(): string
    {
        return 'Мы используем файлы cookie и аналогичные технологии, чтобы сайт работал корректно, а также для анализа посещаемости и улучшения сервиса. Вы можете принять все файлы cookie или настроить их использование.';
    }

    private function defaultSettings(): string
    {
        return 'Необходимые файлы cookie работают всегда. Аналитика и маркетинговые категории включаются только по вашему выбору. Сейчас на сайте может использоваться Яндекс Метрика. Рекламные теги не установлены.';
    }
}
