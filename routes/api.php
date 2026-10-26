<?php

use App\Http\Controllers\Api\ArtistController;
use Illuminate\Support\Facades\Route;

// Prefijo /api/v1 (bootstrap/app.php). Contratos en 05 §4.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/artists', [ArtistController::class, 'index'])->name('api.artists.index');
});
