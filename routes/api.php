<?php

declare(strict_types=1);

use App\Http\Controllers\CercaniaController;
use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\TelemetriaController;
use App\Http\Controllers\TransporteController;
use Illuminate\Support\Facades\Route;

Route::post('/disponibilidad/evaluar', [DisponibilidadController::class, 'evaluar'])
    ->name('api.disponibilidad.evaluar');

Route::post('/transporte/calcular', [TransporteController::class, 'calcularApi'])
    ->name('api.transporte.calcular');

// GET y POST comparten contrato y nombre para /api/sitios/cercanos (CUS-05).
Route::match(['get', 'post'], '/sitios/cercanos', [CercaniaController::class, 'buscarApi'])
    ->name('api.sitios.cercanos');

/*
 * CUS-06: telemetría de un sitio del catálogo de turismo. El parámetro
 * {sitio} se resuelve con App\Models\Sitio gracias al binding condicional
 * definido en routes/web.php (allí el mismo nombre apunta a SitioPatrimonial).
 */
Route::get('/sitios/{sitio}/telemetria', [TelemetriaController::class, 'showApi'])
    ->whereNumber('sitio')
    ->name('api.sitios.telemetria');
