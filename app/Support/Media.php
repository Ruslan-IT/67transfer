<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Media
{
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    public static function isManagedPath(?string $path): bool
    {
        return filled($path)
            && ! str_starts_with($path, 'http://')
            && ! str_starts_with($path, 'https://')
            && ! str_starts_with(ltrim($path, '/'), 'images/');
    }
}
