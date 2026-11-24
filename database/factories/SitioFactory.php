<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sitio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sitio>
 */
class SitioFactory extends Factory
{
    protected $model = Sitio::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->company(),
            /*
             * slug y sensor_id son únicos: unique() del generador evita
             * colisiones entre sitios creados en la misma prueba y que el
             * fallback lea la lectura de otro sensor por error (CUS-06).
             */
            'slug' => fake()->unique()->slug(2),
            'sensor_id' => sprintf('SN-%03d', fake()->unique()->numberBetween(1, 999)),
            'categoria' => fake()->randomElement(['Arqueologico', 'Religioso', 'Museo', 'Natural']),
            'ubicacion' => fake()->address(),
            'latitud' => fake()->randomFloat(7, -13.5467, -13.4867),
            'longitud' => fake()->randomFloat(7, -72.0081, -71.9481),
            'descripcion' => fake()->paragraph(),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => ['activo' => false]);
    }

    /**
     * Fija coordenadas concretas para escenarios de cercanía (CUS-05).
     */
    public function enCoordenadas(float $latitud, float $longitud): static
    {
        return $this->state(fn (): array => [
            'latitud' => $latitud,
            'longitud' => $longitud,
        ]);
    }
}
