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

    public function test_servicio_de_resena_se_resuelve_desde_el_contenedor_y_persiste(): void
    {
        // Given: Se resuelve el servicio desde el Service Container de Laravel
        $resenaService = $this->app->make(\App\Services\ResenaService::class);

        // When: Se registra una reseña a través del servicio
        $resena = $resenaService->registrarResena(5, 'Excelente vista de Sacsayhuamán');

        // Then: Debe persistirse en la base de datos
        $this->assertDatabaseHas('resenas', [
            'id' => $resena->id,
            'calificacion' => 5,
            'comentario' => 'Excelente vista de Sacsayhuamán',
        ]);

        // Y el promedio calculado a través del repositorio debe ser correcto
        $this->assertEquals(5.0, $resenaService->calcularPromedioCalificaciones());
    }
}