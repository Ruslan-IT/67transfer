<?php

namespace App\Models\Concerns;

use App\Support\Media;
use Illuminate\Support\Facades\Storage;

trait DeletesStoredFiles
{
    /**
     * @param  list<string>  $attributes
     */
    protected static function deleteStoredFilesOnChange(array $attributes): void
    {
        static::updating(function ($model) use ($attributes): void {
            foreach ($attributes as $attribute) {
                if (! $model->isDirty($attribute)) {
                    continue;
                }

                $old = $model->getOriginal($attribute);

                if (Media::isManagedPath(is_string($old) ? $old : null)) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

        static::deleting(function ($model) use ($attributes): void {
            foreach ($attributes as $attribute) {
                $path = $model->{$attribute};

                if (Media::isManagedPath(is_string($path) ? $path : null)) {
                    Storage::disk('public')->delete($path);
                }
            }
        });
    }
}
