<?php

namespace App\Providers;

use App\Http\Controllers\LivewireFileUploadController;
use App\Models\Destination;
use App\Models\Page;
use App\Support\LivewireTemporaryUploads;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Features\SupportFileUploads\FileUploadController;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FileUploadController::class, LivewireFileUploadController::class);
    }

    public function boot(): void
    {
        LivewireTemporaryUploads::boot();

        View::composer('layouts.app', function ($view): void {
            $view->with([
                'navDestinations' => Destination::query()->published()->ordered()->get(),
                'sitePage' => Page::query()->where('slug', 'home')->first(),
            ]);
        });
    }
}
