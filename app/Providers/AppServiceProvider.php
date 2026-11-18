<?php

namespace App\Providers;

use App\Contracts\DisponibilidadServiceInterface;
use App\Contracts\TransporteServiceInterface;
use App\Services\DisponibilidadService;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
