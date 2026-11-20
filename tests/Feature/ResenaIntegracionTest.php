<?php

namespace Tests\Feature;

use App\Models\Resena;
use App\Models\Sitio;
use Database\Seeders\SitioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResenaIntegracionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SitioSeeder::class);
    }

    public function test_registrar_resena_valida_retorna_201(): void
    {
        // Given
        $sitio = Sitio::query()->where('slug', 'plaza-de-armas')->firstOrFail();
        $payload = ['calificacion' => 5, 'comentario' => 'Excelente lugar'];

        // When
        $response = $this->postJson("/api/sitios/{$sitio->id}/resenas", $payload);

        // Then
        $response->assertStatus(201);
        $this->assertDatabaseHas('resenas', [
            'sitio_id' => $sitio->id,
            'calificacion' => 5,
            'comentario' => 'Excelente lugar',
        ]);
    }

    public function test_registrar_resena_con_calificacion_invalida_retorna_422_con_mensaje(): void
    {
        // Given
        $sitio = Sitio::query()->firstOrFail();
        $payload = ['calificacion' => 0, 'comentario' => 'Buen lugar'];

        // When
        $response = $this->postJson("/api/sitios/{$sitio->id}/resenas", $payload);

        // Then
        $response->assertStatus(422)->assertJson(['mensaje' => 'La calificación debe estar entre 1 y 5']);
    }

    public function test_registrar_resena_con_calificacion_decimal_retorna_422_y_no_500(): void
    {
        // Given
        $sitio = Sitio::query()->firstOrFail();
        $payload = ['calificacion' => 4.5, 'comentario' => 'Buen lugar'];

        // When
        $response = $this->postJson("/api/sitios/{$sitio->id}/resenas", $payload);

        // Then
        $response->assertStatus(422)->assertJson(['mensaje' => 'La calificación debe estar entre 1 y 5']);
    }

    public function test_registrar_resena_con_calificacion_no_numerica_retorna_422_y_no_500(): void
    {
        // Given
        $sitio = Sitio::query()->firstOrFail();
        $payload = ['calificacion' => 'abc', 'comentario' => 'Buen lugar'];

        // When
        $response = $this->postJson("/api/sitios/{$sitio->id}/resenas", $payload);

        // Then
        $response->assertStatus(422)->assertJson(['mensaje' => 'La calificación debe estar entre 1 y 5']);
    }

    public function test_registrar_resena_con_calificacion_nula_retorna_422_y_no_500(): void
    {
        // Given
        $sitio = Sitio::query()->firstOrFail();
        $payload = ['calificacion' => null, 'comentario' => 'Buen lugar'];

        // When
        $response = $this->postJson("/api/sitios/{$sitio->id}/resenas", $payload);

        // Then
        $response->assertStatus(422)->assertJson(['mensaje' => 'La calificación debe estar entre 1 y 5']);
    }

    public function test_registrar_resena_con_comentario_vacio_retorna_422_con_mensaje(): void
    {
        // Given
        $sitio = Sitio::query()->firstOrFail();
        $payload = ['calificacion' => 4, 'comentario' => ''];

        // When
        $response = $this->postJson("/api/sitios/{$sitio->id}/resenas", $payload);

        // Then
        $response->assertStatus(422)->assertJson(['mensaje' => 'El comentario no puede estar vacío']);
    }

    public function test_registrar_resena_con_comentario_solo_espacios_retorna_422_con_mensaje(): void
    {
        // Given
        $sitio = Sitio::query()->firstOrFail();
        $payload = ['calificacion' => 4, 'comentario' => '   '];

        // When
        $response = $this->postJson("/api/sitios/{$sitio->id}/resenas", $payload);

        // Then
        $response->assertStatus(422)->assertJson(['mensaje' => 'El comentario no puede estar vacío']);
    }

    public function test_registrar_resena_con_comentario_mayor_a_500_retorna_422_con_mensaje(): void
    {
        // Given
        $sitio = Sitio::query()->firstOrFail();
        $payload = ['calificacion' => 4, 'comentario' => str_repeat('a', 501)];

        // When
        $response = $this->postJson("/api/sitios/{$sitio->id}/resenas", $payload);

        // Then
        $response->assertStatus(422)->assertJson(['mensaje' => 'El comentario no debe superar los 500 caracteres']);
    }

    public function test_registrar_resena_valida_orden_calificacion_antes_de_comentario(): void
    {
        // Given
        $sitio = Sitio::query()->firstOrFail();
        $payload = ['calificacion' => 0, 'comentario' => ''];

        // When
        $response = $this->postJson("/api/sitios/{$sitio->id}/resenas", $payload);

        // Then
        $response->assertStatus(422)->assertJson(['mensaje' => 'La calificación debe estar entre 1 y 5']);
    }

    public function test_registrar_resena_en_sitio_inexistente_retorna_404(): void
    {
        // Given
        $payload = ['calificacion' => 5, 'comentario' => 'Excelente lugar'];

        // When
        $response = $this->postJson('/api/sitios/99999/resenas', $payload);

        // Then
        $response->assertStatus(404);
    }

    public function test_listar_resenas_retorna_lista_y_promedio_redondeado(): void
    {
        // Given
        $sitio = Sitio::query()->firstOrFail();
        Resena::query()->create(['sitio_id' => $sitio->id, 'calificacion' => 5, 'comentario' => 'Muy bueno']);
        Resena::query()->create(['sitio_id' => $sitio->id, 'calificacion' => 4, 'comentario' => 'Bueno']);
        Resena::query()->create(['sitio_id' => $sitio->id, 'calificacion' => 4, 'comentario' => 'Regular']);

        // When
        $response = $this->getJson("/api/sitios/{$sitio->id}/resenas");

        // Then
        $response->assertStatus(200)
            ->assertJsonPath('promedio', 4.3)
            ->assertJsonCount(3, 'resenas');
    }

    public function test_listar_resenas_sin_registros_retorna_promedio_cero_y_lista_vacia(): void
    {
        // Given
        $sitio = Sitio::query()->firstOrFail();

        // When
        $response = $this->getJson("/api/sitios/{$sitio->id}/resenas");

        // Then
        $response->assertStatus(200)
            ->assertJsonPath('promedio', 0)
            ->assertJsonCount(0, 'resenas');
    }

    public function test_listar_resenas_en_sitio_inexistente_retorna_404(): void
    {
        // When
        $response = $this->getJson('/api/sitios/99999/resenas');

        // Then
        $response->assertStatus(404);
    }
}
