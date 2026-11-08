<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'is_secret',
    ];

    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
        ];
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $row = static::query()->where('key', $key)->first();

        if (! $row || $row->value === null || $row->value === '') {
            return $default;
        }

        if (! $row->is_secret) {
            return $row->value;
        }

        try {
            $decoded = Crypt::decryptString($row->value);
        } catch (Throwable) {
            return $default;
        }

        return $decoded === '' ? $default : $decoded;
    }

    public static function put(string $key, ?string $value, bool $secret = false): void
    {
        $stored = $value;

        if ($secret) {
            $stored = ($value === null || $value === '') ? null : Crypt::encryptString($value);
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'is_secret' => $secret,
            ],
        );
    }

    public static function forget(string $key): void
    {
        static::query()->where('key', $key)->delete();
    }
}
