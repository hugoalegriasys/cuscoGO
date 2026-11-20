<?php

namespace App\Providers;

use App\Contracts\SiteSensorInterface;
use App\Services\HttpSiteSensor;
use App\Services\SimulatedSiteSensor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SiteSensorInterface::class, function () {
            $driver = env('SENSOR_DRIVER', 'simulado');

            if ($driver === 'http') {
                return new HttpSiteSensor();
            }

            return new SimulatedSiteSensor();
        });
    }

    public function boot(): void
    {
        //
    }
}
