<?php

declare(strict_types=1);

use App\Http\Controllers\DisponibilidadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('sitios')->name('sitios.')->group(function (): void {
    Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])
        ->name('disponibilidad.index');
    Route::post('/disponibilidad/evaluar', [DisponibilidadController::class, 'evaluar'])
        ->name('disponibilidad.evaluar');
});
