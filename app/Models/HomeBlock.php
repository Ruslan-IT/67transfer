<?php

namespace App\Models;

use App\Support\HomeBlockRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HomeBlock extends Model
{
    protected $fillable = [
        'type',
        'name',
        'is_enabled',
        'is_system',
        'sort_order',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
            'payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (HomeBlock $block): bool {
            return ! $block->isSystem();
        });
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function typeLabel(): string
    {
        return HomeBlockRegistry::label($this->type) ?? $this->type;
    }

    public function isSystem(): bool
    {
        return $this->is_system === true;
    }
}
