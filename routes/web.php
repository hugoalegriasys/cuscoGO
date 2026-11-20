<?php

use App\Http\Controllers\AforoVistaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResenaVistaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/aforo', [AforoVistaController::class, 'index'])->name('aforo.index');
Route::get('/resenas', [ResenaVistaController::class, 'index'])->name('resenas.index');
