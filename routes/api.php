<?php

use App\Http\Controllers\Api\ArtistController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

// Prefijo /api/v1 (bootstrap/app.php). Contratos en 05 §4.
Route::post('/tokens', [TokenController::class, 'store'])->middleware('throttle:5,1')->name('api.tokens.store');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::apiResource('artists', ArtistController::class)->names('api.artists');
});
