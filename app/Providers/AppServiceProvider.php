<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
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
        // I2 (05 §2): lo ajeno y lo inexistente lanzan el mismo 404 antes del FormRequest.
        // 'auth' corre antes de SubstituteBindings por la prioridad de middleware.
        Route::bind('artist', fn (string $v) => request()->user()->artists()->findOrFail($v));
        // Un token ajeno responde 404 igual que un inexistente. No se llama 'token': choca con /reset-password/{token}.
        Route::bind('apiToken', fn (string $v) => request()->user()->tokens()->findOrFail($v));
        Route::bind('album', fn (string $v) => request()->user()->albums()->findOrFail($v));
    }
}
