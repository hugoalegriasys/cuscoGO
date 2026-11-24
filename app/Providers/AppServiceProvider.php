<?php

namespace App\Providers;

use App\Contracts\CercaniaServiceInterface;
use App\Contracts\DisponibilidadServiceInterface;
use App\Contracts\SiteSensorClientInterface;
use App\Contracts\TelemetriaAforoServiceInterface;
use App\Contracts\TransporteServiceInterface;
use App\Services\CercaniaService;
use App\Services\DisponibilidadService;
use App\Services\HttpSiteSensorClient;
use App\Services\TelemetriaAforoService;
use App\Services\TransporteService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Vincula la interfaz con su implementación concreta en el Service Container
        $this->app->bind(DisponibilidadServiceInterface::class, DisponibilidadService::class);
        $this->app->bind(TransporteServiceInterface::class, TransporteService::class);
        $this->app->bind(CercaniaServiceInterface::class, CercaniaService::class);

        /*
         * CUS-06: el cliente HTTP es el único que conoce la URL del sensor;
         * el servicio de resiliencia solo depende del contrato, por lo que
         * las pruebas pueden sustituirlo por Http::fake() sin cambiar código.
         */
        $this->app->bind(SiteSensorClientInterface::class, HttpSiteSensorClient::class);
        $this->app->bind(TelemetriaAforoServiceInterface::class, TelemetriaAforoService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
