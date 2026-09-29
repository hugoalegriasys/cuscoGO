<?php

namespace Tests\Feature;

use App\Models\Resena;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResenaModelTest extends TestCase
{
    // Limpia y prepara la base de datos para cada prueba.
    use RefreshDatabase;

    public function test_reseña_se_guarda_en_mysql(): void
    {
        // Arrange: crea una reseña usando el Factory.
        $resena = Resena::factory()->create();

        // Assert: verifica que la reseña exista en la base de datos.
        $this->assertDatabaseHas('resenas', [
            'id' => $resena->id,
            'calificacion' => $resena->calificacion,
            'comentario' => $resena->comentario,
        ]);
    }
}