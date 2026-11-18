<?php

declare(strict_types=1);

use App\Http\Controllers\DisponibilidadController;
use Illuminate\Support\Facades\Route;

Route::post('/disponibilidad/evaluar', [DisponibilidadController::class, 'evaluar'])
    ->name('api.disponibilidad.evaluar');
