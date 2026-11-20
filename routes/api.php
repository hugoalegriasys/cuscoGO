<?php

use App\Http\Controllers\Api\ResenaController;
use App\Http\Controllers\Api\SitioAforoController;
use Illuminate\Support\Facades\Route;

Route::get('/sitios/{sitioId}/aforo', [SitioAforoController::class, 'show']);
Route::get('/sitios/{id}/resenas', [ResenaController::class, 'index']);
Route::post('/sitios/{id}/resenas', [ResenaController::class, 'store']);
