<?php

namespace Tests\Feature;

use Database\Seeders\SitioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AforoPantallaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pantalla_aforo_responde_200_y_contiene_elementos_clave(): void
    {
        // Given
        $this->seed(SitioSeeder::class);

        // When
        $response = $this->get('/aforo');

        // Then
        $response->assertStatus(200);
        $response->assertSee('data-cy="aforo-form"', false);
        $response->assertSee('data-cy="aforo-sitio-select"', false);
        $response->assertSee('data-cy="aforo-consultar-btn"', false);
        $response->assertSee('data-cy="aforo-estado"', false);
        $response->assertSee('data-cy="aforo-resultado"', false);
        $response->assertSee('data-cy="aforo-nivel"', false);
    }
}