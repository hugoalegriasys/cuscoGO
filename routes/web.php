<?php

use App\Http\Controllers\AforoVistaController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/aforo', [AforoVistaController::class, 'index'])->name('aforo.index');
