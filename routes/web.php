<?php

declare(strict_types=1);

use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\SitioPatrimonialController;
use App\Models\SitioPatrimonial;
use Illuminate\Support\Facades\Route;

// Vincula explícitamente el parámetro de ruta {sitio} con el modelo SitioPatrimonial,
// dado que el nombre del parámetro no coincide con el nombre corto de la clase.
Route::model('sitio', SitioPatrimonial::class);

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('sitios')->name('sitios.')->group(function (): void {
    Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])
        ->name('disponibilidad.index');
    Route::post('/disponibilidad/evaluar', [DisponibilidadController::class, 'evaluar'])
        ->name('disponibilidad.evaluar');
});

/*
|--------------------------------------------------------------------------
| Rutas RESTful de Sitios Patrimoniales (CUS-03)
|--------------------------------------------------------------------------
| Gestión del catálogo de sitios patrimoniales y evaluación de su
| estado de operatividad según la hora actual y su horario de atención.
*/
Route::resource('sitios', SitioPatrimonialController::class);

// Evaluación de estado de operatividad (petición AJAX con respuesta JSON).
Route::post('/sitios/{sitio}/verificar', [SitioPatrimonialController::class, 'verificar'])
    ->name('sitios.verificar');
