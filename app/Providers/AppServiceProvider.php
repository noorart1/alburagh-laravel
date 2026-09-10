<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        URL::forceRootUrl(config('app.url'));
        URL::forceScheme('https');
        Livewire::setScriptRoute(function ($handle) {
            return Route::get(
                '/alburagh-laravel/livewire/livewire.min.js',
                $handle
            );
        });
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post(
                '/alburagh-laravel/livewire/update',
                $handle
            );
        });
    }
}