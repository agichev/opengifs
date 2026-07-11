<?php

use App\Http\Controllers\GifController;
use App\Http\Controllers\ProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GifController::class, 'index'])->name('home');

Route::get('/upload', [GifController::class, 'create'])->name('gifs.create');
Route::post('/upload', [GifController::class, 'store'])->name('gifs.store');

Route::get('/gif/{proxyPath}', [GifController::class, 'show'])->name('gifs.show');

Route::get('/g/{proxyPath}', [ProxyController::class, 'proxy'])->name('gifs.proxy');

Route::get('/search', [GifController::class, 'search'])->name('gifs.search');

Route::view('/rules', 'pages.rules')->name('rules');
Route::view('/api', 'api.docs')->name('api.docs');
