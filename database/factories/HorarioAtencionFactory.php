<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HorarioAtencion;
use App\Models\Sitio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HorarioAtencion>
 */
class HorarioAtencionFactory extends Factory
{
    protected $model = HorarioAtencion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sitio_id' => Sitio::factory(),
            'dia_semana' => fake()->numberBetween(1, 7),
            'apertura' => '09:00',
            'cierre' => '17:00',
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => ['activo' => false]);
    }
}
