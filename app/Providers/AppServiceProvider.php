<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' || app()->environment('production')) {
            URL::forceScheme('https');
        }

        // En el VPS, Nginx suele devolver 404 a /livewire-*/livewire.js (reglas estáticas .js).
        // Servimos el JS de Livewire por una ruta sin extensión .js para que llegue a Laravel.
        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/lw-assets/livewire', $handle);
        });
    }
}
