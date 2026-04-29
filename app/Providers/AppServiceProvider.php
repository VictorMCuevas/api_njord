<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Limita la subida de archivos GPX a 5 por día por usuario autenticado.
        // Superar el límite devuelve 429 Too Many Requests.
        RateLimiter::for('subida-gpx', function (Request $request) {
            return Limit::perDay(5)->by($request->user()?->id);
        });
    }
}
