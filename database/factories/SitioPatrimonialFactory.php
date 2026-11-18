<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SitioPatrimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SitioPatrimonial>
 */
class SitioPatrimonialFactory extends Factory
{
    /**
     * Define los datos para crear un sitio patrimonial de prueba.
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->company(),
            'direccion' => fake()->streetAddress(),
            'descripcion' => fake()->sentence(8),
            'hora_apertura' => '09:00',
            'hora_cierre' => '17:00',
            'activo' => true,
        ];
    }

    /**
     * Estado para sitios desactivados temporalmente.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $atributos) => [
            'activo' => false,
        ]);
    }

    /**
     * Estado con un horario de atención nocturno extendido.
     */
    public function horarioExtendido(): static
    {
        return $this->state(fn (array $atributos) => [
            'hora_apertura' => '08:00',
            'hora_cierre' => '22:00',
        ]);
    }
}
