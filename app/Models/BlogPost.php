<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredFiles;
use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use DeletesStoredFiles;

    protected $fillable = [
        'title',
        'slug',
        'heading',
        'category',
        'excerpt',
        'content',
        'image',
        'image_alt',
        'published_at',
        'is_published',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleteStoredFilesOnChange(['image']);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeLatestPublished(Builder $query): Builder
    {
        return $query->orderByDesc('published_at');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->published()
            ->firstOrFail();
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

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($this->excerpt ?: $this->content))), 160);
    }

    public function seoKeywords(): ?string
    {
        return $this->meta_keywords;
    }

    public function headingText(): string
    {
        return $this->heading ?: $this->title;
    }
}
