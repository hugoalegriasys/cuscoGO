<?php

declare(strict_types=1);

use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\TransporteController;
use Illuminate\Support\Facades\Route;

Route::post('/disponibilidad/evaluar', [DisponibilidadController::class, 'evaluar'])
    ->name('api.disponibilidad.evaluar');

Route::post('/transporte/calcular', [TransporteController::class, 'calcularApi'])
    ->name('api.transporte.calcular');
