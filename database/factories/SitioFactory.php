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
            'categoria' => fake()->randomElement(['Arqueologico', 'Religioso', 'Museo', 'Natural']),
            'ubicacion' => fake()->address(),
            'descripcion' => fake()->paragraph(),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => ['activo' => false]);
    }
}
