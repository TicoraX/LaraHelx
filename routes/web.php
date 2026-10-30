<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Sin closures: route:cache falla con ellas (02 §4).
Route::view('/', 'landing')->name('home');

Route::middleware(['auth', 'cache.headers:no_store;private'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('artists', ArtistController::class);
    Route::resource('albums', AlbumController::class);
});
