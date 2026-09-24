<?php

namespace App\Http\Controllers;

use App\Support\LivewireTemporaryUploads;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\FileUploadController as BaseFileUploadController;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class LivewireFileUploadController extends BaseFileUploadController
{
    public function validateAndStore($files, $disk)
    {
        Validator::make(['files' => $files], [
            'files.*' => FileUploadConfiguration::rules(),
        ])->validate();

        return collect($files)->map(function ($file) use ($disk) {
            $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
            $filename = str()->random(40).'.'.$extension;

            $stored = $file->storeAs(
                FileUploadConfiguration::directory(),
                $filename,
                ['disk' => $disk],
            );

            if ($stored === false) {
                throw new RuntimeException('Не удалось сохранить временный файл загрузки.');
            }

            $relative = LivewireTemporaryUploads::relativeFilename($stored);

            if ($relative === null) {
                throw new RuntimeException('Временный файл загрузки записан без имени.');
            }

            return TemporaryUploadedFile::signPath($relative);
        });
    }
}
