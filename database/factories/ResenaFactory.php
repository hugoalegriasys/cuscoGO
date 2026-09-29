<?php

namespace Database\Factories;

use App\Models\Resena;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resena>
 */
class ResenaFactory extends Factory
{
    /**
     * Define los datos para crear una reseña de prueba.
     */
    public function definition(): array
    {
        return [
            // Genera una calificación válida entre 1 y 5.
            'calificacion' => fake()->numberBetween(1, 5),

            // Genera un comentario de ejemplo.
            'comentario' => fake()->sentence(),
        ];
    }
}