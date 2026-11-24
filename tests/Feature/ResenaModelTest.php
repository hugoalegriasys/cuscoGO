<?php

namespace Tests\Feature;

use App\Models\Resena;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResenaModelTest extends TestCase
{
    // Limpia y prepara la base de datos para cada prueba.
    use RefreshDatabase;

    /**
     * Given: La base de datos MySQL está limpia y el Factory de Resena puede generar una reseña válida.
     * When: Se crea y persiste una reseña con Resena::factory()->create().
     * Then: La tabla resenas contiene el registro con su id, calificacion y comentario.
     */
    public function test_reseña_se_guarda_en_mysql(): void
    {
        // Given: crea una reseña usando el Factory.
        // When: La reseña se persiste en MySQL con sus datos generados.
        $resena = Resena::factory()->create();

        // Then: verifica que la reseña exista en la base de datos.
        $this->assertDatabaseHas('resenas', [
            'id' => $resena->id,
            'calificacion' => $resena->calificacion,
            'comentario' => $resena->comentario,
        ]);
    }
}
