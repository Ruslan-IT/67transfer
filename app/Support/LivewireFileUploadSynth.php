<?php

namespace App\Support;

use Livewire\Features\SupportFileUploads\FileUploadSynth as BaseFileUploadSynth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LivewireFileUploadSynth extends BaseFileUploadSynth
{
    public static $key = 'fil';

    public function dehydrate($target)
    {
        if ($target instanceof TemporaryUploadedFile) {
            $filename = LivewireTemporaryUploads::relativeFilename($target->getFilename())
                ?? LivewireTemporaryUploads::relativeFilename($target->getRealPath());

            if ($filename !== null) {
                return ['livewire-file:'.TemporaryUploadedFile::signPath($filename), []];
            }
        }

        return parent::dehydrate($target);
    }

    public function hydrate($value)
    {
        if (is_string($value) && str($value)->startsWith('livewire-file:')) {
            $path = TemporaryUploadedFile::extractPathFromSignedPath((string) str($value)->after('livewire-file:'));

            if ($path === false) {
                return null;
            }

            $filename = LivewireTemporaryUploads::relativeFilename($path);

            return $filename === null ? null : TemporaryUploadedFile::createFromLivewire($filename);
        }

        if (is_string($value) && str($value)->startsWith('livewire-files:')) {
            $signedPaths = json_decode((string) str($value)->after('livewire-files:'), true) ?: [];

            return collect($signedPaths)
                ->map(fn ($signedPath) => TemporaryUploadedFile::extractPathFromSignedPath($signedPath))
                ->map(fn ($path) => is_string($path) ? LivewireTemporaryUploads::relativeFilename($path) : null)
                ->filter()
                ->map(fn (string $path) => TemporaryUploadedFile::createFromLivewire($path))
                ->values()
                ->all();
        }

        return parent::hydrate($value);
    }
}
