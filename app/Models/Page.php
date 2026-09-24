<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredFiles;
use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use DeletesStoredFiles;

    public const SYSTEM_SLUGS = ['home', 'destinations', 'information', 'contacts'];

    protected $fillable = [
        'title',
        'slug',
        'content',
        'hero_image',
        'image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleteStoredFilesOnChange(['hero_image', 'image']);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function text(string $key, ?string $default = null): string
    {
        $value = data_get($this->content, $key, $default);

        return is_string($value) && $value !== '' ? $value : (string) ($default ?? '');
    }

    /**
     * @return list<string>
     */
    public function list(string $key, array $default = []): array
    {
        $value = data_get($this->content, $key, $default);

        if (! is_array($value) || $value === []) {
            return $default;
        }

        return array_values(array_filter(array_map(function ($item) {
            if (is_string($item)) {
                return $item;
            }

            if (is_array($item)) {
                return (string) ($item['text'] ?? $item['route'] ?? reset($item) ?: '');
            }

            return '';
        }, $value)));
    }

    public function heroImageUrl(?string $fallback = null): ?string
    {
        return Media::url($this->hero_image) ?? $fallback;
    }

    public function imageUrl(?string $fallback = null): ?string
    {
        return Media::url($this->image) ?? $fallback;
    }

    public function seoTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function seoDescription(): string
    {
        if (filled($this->meta_description)) {
            return $this->meta_description;
        }

        $fallback = $this->text('lede') ?: $this->text('hero_description') ?: $this->title;

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($fallback))), 160);
    }

    public function seoKeywords(): ?string
    {
        return $this->meta_keywords;
    }

    public function isSystem(): bool
    {
        return in_array($this->slug, self::SYSTEM_SLUGS, true);
    }

    public function mailtoUrl(): ?string
    {
        $email = trim($this->text('email'));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$email : null;
    }

    public function telUrl(): ?string
    {
        $phone = trim($this->text('phone'));

        if ($phone === '') {
            return null;
        }

        $tel = preg_replace('/[^\d+]/', '', $phone);

        return $tel !== '' ? 'tel:'.$tel : null;
    }

    public function whatsappUrl(): ?string
    {
        $value = trim($this->text('whatsapp'));

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits !== '' ? 'https://wa.me/'.$digits : null;
    }

    public function telegramUrl(): ?string
    {
        $value = trim($this->text('telegram'));

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $handle = ltrim($value, '@');

        return $handle !== '' ? 'https://t.me/'.$handle : null;
    }

    public function mapIframeSrc(): ?string
    {
        $raw = trim($this->text('map_embed'));

        if ($raw === '') {
            return null;
        }

        $src = preg_match('/src=["\']([^"\']+)["\']/i', $raw, $matches)
            ? html_entity_decode($matches[1])
            : $raw;

        if (! filter_var($src, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = strtolower((string) parse_url($src, PHP_URL_HOST));
        $allowed = [
            'google.com',
            'www.google.com',
            'maps.google.com',
            'www.google.ru',
            'google.ru',
            'yandex.ru',
            'www.yandex.ru',
            'yandex.com',
            'maps.yandex.ru',
            'openstreetmap.org',
            'www.openstreetmap.org',
        ];

        foreach ($allowed as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.'.$allowedHost)) {
                return $src;
            }
        }

        return null;
    }
}
