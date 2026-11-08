<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FirstScreen
{
    public const KEY = 'first_screen';

    /**
     * @return array{
     *     eyebrow: string,
     *     title: string,
     *     lead: string,
     *     note: string,
     *     background: ?string,
     *     blocks: list<array{title: string, text: string, icon: ?string, fallback: string}>
     * }
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

        $next['background'] = $this->replaceFile(
            $next['background'],
            $previous['background'],
            'background',
        );

        foreach ($next['blocks'] as $index => $block) {
            $next['blocks'][$index]['icon'] = $this->replaceFile(
                $block['icon'],
                $previous['blocks'][$index]['icon'] ?? null,
                'blocks.'.$index.'.icon',
                true,
            );
        }

        Setting::put(self::KEY, json_encode($next, JSON_UNESCAPED_UNICODE));
    }

    public function backgroundUrl(): ?string
    {
        return Media::url($this->data()['background']);
    }

    public function iconUrl(int $index): ?string
    {
        return Media::url($this->data()['blocks'][$index]['icon'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{
     *     eyebrow: string,
     *     title: string,
     *     lead: string,
     *     note: string,
     *     background: ?string,
     *     blocks: list<array{title: string, text: string, icon: ?string, fallback: string}>
     * }
     */
    public function merge(array $state): array
    {
        $defaults = $this->defaults();
        $blocks = [];

        foreach ($defaults['blocks'] as $index => $default) {
            $incoming = $state['blocks'][$index] ?? [];
            if (! is_array($incoming)) {
                $incoming = [];
            }

            $blocks[] = [
                'title' => $this->text($incoming['title'] ?? null, $default['title']),
                'text' => $this->text($incoming['text'] ?? null, $default['text']),
                'icon' => $this->path($incoming['icon'] ?? $default['icon']),
                'fallback' => $default['fallback'],
            ];
        }

        return [
            'eyebrow' => $this->text($state['eyebrow'] ?? null, $defaults['eyebrow']),
            'title' => $this->text($state['title'] ?? null, $defaults['title']),
            'lead' => $this->text($state['lead'] ?? null, $defaults['lead']),
            'note' => $this->text($state['note'] ?? null, $defaults['note']),
            'background' => $this->path($state['background'] ?? null),
            'blocks' => $blocks,
        ];
    }

    /**
     * @return array{
     *     eyebrow: string,
     *     title: string,
     *     lead: string,
     *     note: string,
     *     background: null,
     *     blocks: list<array{title: string, text: string, icon: null, fallback: string}>
     * }
     */
    public function defaults(): array
    {
        return [
            'eyebrow' => 'Комфортные поездки по Европе',
            'title' => 'Трансферы в Европе — просто и надёжно',
            'lead' => 'Оставить заявку бесплатно',
            'note' => 'Мы подберём оптимальный вариант и свяжемся для подтверждения.',
            'background' => null,
            'blocks' => [
                [
                    'title' => 'Бесплатная заявка',
                    'text' => 'Заполнение займёт всего пару минут.',
                    'icon' => null,
                    'fallback' => 'clipboard',
                ],
                [
                    'title' => 'Подтверждение менеджером',
                    'text' => 'Проверим детали и предложим лучшие варианты.',
                    'icon' => null,
                    'fallback' => 'phone',
                ],
                [
                    'title' => 'Удобно для всей семьи',
                    'text' => 'Детские кресла, вместительный багаж, любые направления в Европе.',
                    'icon' => null,
                    'fallback' => 'people',
                ],
            ],
        ];
    }

    private function text(mixed $value, string $default): string
    {
        if (! is_string($value)) {
            return $default;
        }

        $value = trim($value);

        return $value === '' ? $default : $value;
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
        if ($next !== null && $svg) {
            $this->assertSafeSvg($next, $field);
        }

        if ($previous && $previous !== $next && Media::isManagedPath($previous)) {
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
