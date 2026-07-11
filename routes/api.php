<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/gifs')->group(function () {
    Route::get('/search', [ApiController::class, 'search'])->name('api.gifs.search');
    Route::get('/trending', [ApiController::class, 'trending'])->name('api.gifs.trending');
    Route::get('/latest', [ApiController::class, 'latest'])->name('api.gifs.latest');
    Route::get('/random', [ApiController::class, 'random'])->name('api.gifs.random');
    Route::get('/{id}', [ApiController::class, 'show'])->name('api.gifs.show');
});
