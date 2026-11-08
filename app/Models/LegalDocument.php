<?php

namespace App\Models;

use App\Support\LegalDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class LegalDocument extends Model
{
    protected $fillable = [
        'key',
        'title',
        'slug',
        'body',
        'meta_title',
        'meta_description',
        'is_published',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LegalDocumentVersion::class);
    }

    public function currentVersion(): ?LegalDocumentVersion
    {
        return $this->versions()->where('version', $this->version)->first();
    }

    /**
     * @param  array{
     *     title: string,
     *     slug: string,
     *     body: string,
     *     is_published: bool,
     *     meta_title?: ?string,
     *     meta_description?: ?string
     * }  $attributes
     */
    public function saveRevision(array $attributes): void
    {
        $title = trim($attributes['title']);
        $slug = trim($attributes['slug']);
        $body = trim($attributes['body']);
        $published = (bool) $attributes['is_published'];
        $textChanged = $this->exists && ($this->title !== $title || $this->body !== $body);

        if (! $this->exists) {
            $this->version = 1;
        } elseif ($textChanged) {
            $this->version = (int) $this->version + 1;
        }

        $fill = [
            'title' => $title,
            'slug' => $slug,
            'body' => $body,
            'is_published' => $published,
        ];

        if (array_key_exists('meta_title', $attributes)) {
            $fill['meta_title'] = $this->nullableText($attributes['meta_title'], 255);
        }

        if (array_key_exists('meta_description', $attributes)) {
            $fill['meta_description'] = $this->nullableText($attributes['meta_description'], 500);
        }

        $this->fill($fill);
        $this->save();

        $version = $this->versions()->firstOrNew(['version' => $this->version]);
        $version->title = $title;
        $version->body = $body;

        if (! $version->exists) {
            $version->created_at = now();
        }

        $version->save();
    }

    public function seoTitle(): string
    {
        return filled($this->meta_title) ? (string) $this->meta_title : $this->title;
    }

    public function seoDescription(): string
    {
        if (filled($this->meta_description)) {
            return (string) $this->meta_description;
        }

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->body)) ?? ''), 160);
    }

    private function nullableText(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $max);
    }

    public static function ensureDefaults(): void
    {
        try {
            foreach (LegalDocuments::defaults() as $key => $document) {
                $row = static::query()->firstOrCreate(
                    ['key' => $key],
                    [
                        'title' => $document['title'],
                        'slug' => $document['slug'],
                        'body' => $document['body'],
                        'is_published' => true,
                        'version' => 1,
                    ],
                );

                if (! $row->versions()->exists()) {
                    $row->versions()->create([
                        'version' => $row->version,
                        'title' => $row->title,
                        'body' => $row->body,
                        'created_at' => now(),
                    ]);
                }
            }
        } catch (QueryException) {
            // Таблицы ещё нет: идёт миграция или установка.
        }
    }

    public static function published(string $key): ?self
    {
        static::ensureDefaults();

        return static::query()
            ->where('key', $key)
            ->where('is_published', true)
            ->first();
    }

    public static function consentVersion(): ?LegalDocumentVersion
    {
        static::ensureDefaults();

        $document = static::query()->where('key', LegalDocuments::CONSENT)->first();

        return $document?->currentVersion();
    }
}
