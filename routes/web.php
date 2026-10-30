<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;

// Sin closures: route:cache falla con ellas (02 §4).
Route::view('/', 'landing')->name('home');

Route::middleware(['auth', 'cache.headers:no_store;private'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('artists', ArtistController::class);
    Route::resource('albums', AlbumController::class);
    Route::get('/stats', StatsController::class)->name('stats');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/tokens', [TokenController::class, 'store'])->name('tokens.store');
    Route::delete('/profile/tokens/{apiToken}', [TokenController::class, 'destroy'])->name('tokens.destroy');
});
