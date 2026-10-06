<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\DisponibilidadServiceInterface;
use App\Services\DisponibilidadService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Vincula la interfaz con su implementación concreta en el Service Container
        $this->app->bind(DisponibilidadServiceInterface::class, DisponibilidadService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}