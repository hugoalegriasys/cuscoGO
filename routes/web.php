<?php

declare(strict_types=1);

use App\Http\Controllers\CercaniaController;
use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\SitioPatrimonialController;
use App\Http\Controllers\TelemetriaController;
use App\Http\Controllers\TransporteController;
use App\Models\Sitio;
use App\Models\SitioPatrimonial;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/*
 * El nombre {sitio} se usa en dos dominios: el catálogo patrimonial (CUS-03)
 * y la telemetría IoT (CUS-06). Un Route::model() global devolvería siempre
 * SitioPatrimonial y rompería la API; por eso el binding examina la URI de
 * la ruta y elige el modelo correcto en cada caso.
 */
Route::bind('sitio', static function (string $valor, RoutingRoute $ruta): Sitio|SitioPatrimonial {
    $uri = $ruta->uri();

    if (str_starts_with($uri, 'api/sitios/') && str_ends_with($uri, '/telemetria')) {
        return Sitio::query()->findOrFail($valor);
    }

    return SitioPatrimonial::query()->findOrFail($valor);
});

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('sitios')->name('sitios.')->group(function (): void {
    /*
     * Búsqueda por cercanía (CUS-05): el listado se calcula en servidor
     * con Haversine y se muestra ordenado por distancia en la misma vista.
     */
    Route::get('/cercanos', [CercaniaController::class, 'index'])
        ->name('cercanos');
    Route::post('/cercanos', [CercaniaController::class, 'buscarWeb'])
        ->name('cercanos.buscar');

    Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])
        ->name('disponibilidad.index');
    Route::post('/disponibilidad/evaluar', [DisponibilidadController::class, 'evaluar'])
        ->name('disponibilidad.evaluar');

    // Panel de telemetría (CUS-06): consulta real o demo de resiliencia.
    Route::get('/telemetria', [TelemetriaController::class, 'index'])
        ->name('telemetria.index');
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

/*
|--------------------------------------------------------------------------
| Cálculo del Costo del Transporte Turístico (CUS-04)
|--------------------------------------------------------------------------
| Cotización web por modalidad de transporte con tarifa por pasajero
| o tarifa fija por vehículo.
*/
Route::prefix('transporte')->name('transporte.')->group(function (): void {
    Route::get('/calcular', [TransporteController::class, 'index'])
        ->name('calcular');
    Route::post('/calcular', [TransporteController::class, 'calcularWeb'])
        ->name('calcular.evaluar');
});
