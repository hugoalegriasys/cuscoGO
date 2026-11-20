<?php

namespace Tests\Feature;

use Database\Seeders\SitioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResenaPantallaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pantalla_resenas_responde_200_y_contiene_elementos_clave(): void
    {
        // Given
        $this->seed(SitioSeeder::class);

        // When
        $response = $this->get('/resenas');

        // Then
        $response->assertStatus(200);
        $response->assertSee('data-cy="resena-sitio-select"', false);
        $response->assertSee('data-cy="resena-promedio"', false);
        $response->assertSee('data-cy="resena-lista"', false);
        $response->assertSee('data-cy="resena-lista-estado"', false);
        $response->assertSee('data-cy="resena-form"', false);
        $response->assertSee('data-cy="resena-estrellas"', false);
        $response->assertSee('data-cy="resena-comentario"', false);
        $response->assertSee('data-cy="resena-contador"', false);
        $response->assertSee('data-cy="resena-enviar-btn"', false);
        $response->assertSee('data-cy="resena-form-estado"', false);
    }
}
