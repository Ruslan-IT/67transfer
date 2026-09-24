<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredFiles;
use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Destination extends Model
{
    use DeletesStoredFiles;

    public const RESERVED_SLUGS = [
        'admin',
        'destinations',
        'information',
        'livewire',
        'storage',
        'up',
        'login',
        'home',
        'blog',
        'contacts',
    ];

    protected $fillable = [
        'name',
        'slug',
        'title',
        'content',
        'image',
        'image_alt',
        'badge',
        'territory',
        'tagline',
        'from_default',
        'to_default',
        'routes',
        'is_published',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected function casts(): array
    {
        return [
            'routes' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleteStoredFilesOnChange(['image', 'badge']);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->published()
            ->firstOrFail();
    }

    /**
     * @return list<string>
     */
    public function routeList(): array
    {
        $routes = $this->routes ?? [];

        if (! is_array($routes)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (is_string($item)) {
                return $item;
            }

            if (is_array($item)) {
                return (string) ($item['text'] ?? $item['route'] ?? reset($item) ?: '');
            }

            return '';
        }, $routes)));
    }

    public function imageUrl(?string $fallback = null): ?string
    {
        return Media::url($this->image) ?? $fallback;
    }

    public function badgeUrl(?string $fallback = null): ?string
    {
        return Media::url($this->badge) ?? $fallback;
    }

    public function seoTitle(): string
    {
        return $this->meta_title ?: ('TRANSFER POINT: '.$this->name);
    }

    public function seoDescription(): string
    {
        if (filled($this->meta_description)) {
            return $this->meta_description;
        }

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->content))), 160);
    }

    public function seoKeywords(): ?string
    {
        return $this->meta_keywords;
    }
}
