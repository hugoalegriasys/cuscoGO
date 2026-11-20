<?php

use App\Http\Controllers\Api\SitioAforoController;
use Illuminate\Support\Facades\Route;

Route::get('/sitios/{sitioId}/aforo', [SitioAforoController::class, 'show']);