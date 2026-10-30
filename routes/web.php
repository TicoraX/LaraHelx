<?php

use App\Http\Controllers\ArtistController;
use Illuminate\Support\Facades\Route;

// Sin closures: route:cache falla con ellas (02 §4).
Route::view('/', 'landing')->name('home');

Route::middleware(['auth', 'cache.headers:no_store;private'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::resource('artists', ArtistController::class);
});
