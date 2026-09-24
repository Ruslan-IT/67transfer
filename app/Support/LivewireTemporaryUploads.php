<?php

namespace App\Support;

use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;

use function Livewire\on;

class LivewireTemporaryUploads
{
    public static function boot(): void
    {
        config(['livewire.temporary_file_upload.disk' => 'livewire-tmp']);

        on('call', function ($component, $method, $params, $context, $returnEarly): void {
            if ($method !== '_finishUpload') {
                return;
            }

            static::finishUpload(
                $component,
                $params[0] ?? null,
                $params[1] ?? [],
                (bool) ($params[2] ?? false),
                (bool) ($params[3] ?? false),
            );

            $returnEarly();
        });

        Livewire::propertySynthesizer(LivewireFileUploadSynth::class);
    }

    public static function relativeFilename(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = trim(str_replace('\\', '/', $path), '/');
        $directory = trim((string) FileUploadConfiguration::directory(), '/');

        while ($directory !== '' && ($path === $directory || str_starts_with($path, $directory.'/'))) {
            if ($path === $directory) {
                return null;
            }

            $path = substr($path, strlen($directory) + 1);
        }

        $path = basename($path);

        return ($path === '' || $path === $directory) ? null : $path;
    }

    protected static function finishUpload(object $component, mixed $name, mixed $tmpPath, bool $isMultiple, bool $append): void
    {
        if (FileUploadConfiguration::shouldCleanupOldUploads() && method_exists($component, 'cleanupOldUploads')) {
            \Closure::bind(function (): void {
                $this->cleanupOldUploads();
            }, $component, $component)();
        }

        $tmpPath = collect((array) $tmpPath)->map(function ($signedPath) {
            $path = TemporaryUploadedFile::extractPathFromSignedPath((string) $signedPath);

            if ($path === false) {
                abort(403, 'Invalid upload reference.');
            }

            $filename = static::relativeFilename($path);

            if ($filename === null) {
                abort(422, 'Invalid upload reference.');
            }

            return $filename;
        })->values()->all();

        if ($tmpPath === []) {
            abort(422, 'Invalid upload reference.');
        }

        if ($isMultiple) {
            $file = collect($tmpPath)->map(fn (string $i) => TemporaryUploadedFile::createFromLivewire($i))->all();
            $component->dispatch('upload:finished', name: $name, tmpFilenames: collect($file)->map->getFilename()->all())->self();

            if ($append) {
                $existing = $component->getPropertyValue($name);
                if ($existing instanceof \Illuminate\Support\Collection) {
                    $file = $existing->merge($file);
                } elseif (is_array($existing)) {
                    $file = array_merge($existing, $file);
                }
            }
        } else {
            $file = TemporaryUploadedFile::createFromLivewire($tmpPath[0]);
            $component->dispatch('upload:finished', name: $name, tmpFilenames: [$file->getFilename()])->self();

            if (is_array($value = $component->getPropertyValue($name))) {
                $file = array_merge($value, [$file]);
            }
        }

        app('livewire')->updateProperty($component, $name, $file);
    }
}
