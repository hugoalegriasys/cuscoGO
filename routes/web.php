<?php

use App\Http\Controllers\ResenaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ResenaController::class, 'home'])->name('home');
Route::get('/sitios/{sitio?}', [ResenaController::class, 'sitio'])->name('sitios.show');
Route::post('/resenas', [ResenaController::class, 'store'])->name('resenas.store');
