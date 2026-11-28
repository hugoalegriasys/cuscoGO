<?php

namespace App\Providers;

use App\Contracts\ResenaRepository;
use App\Repositories\EloquentResenaRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ResenaRepository::class, EloquentResenaRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
