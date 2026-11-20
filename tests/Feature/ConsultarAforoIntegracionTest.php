<?php

namespace Tests\Feature;

use App\Contracts\SiteSensorInterface;
use App\Services\HttpSiteSensor;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsultarAforoIntegracionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(SiteSensorInterface::class, HttpSiteSensor::class);
    }

    public function test_consultar_aforo_retorna_bajo_cuando_ocupacion_es_30(): void
    {
        // Given
        Http::fake(['*' => Http::response(['capacidad' => 100, 'ocupacion' => 30], 200)]);

        // When
        $response = $this->getJson('/api/sitios/plaza-de-armas/aforo');

        // Then
        $response->assertStatus(200)
            ->assertJson(['sitio_id' => 'plaza-de-armas', 'nivel_aforo' => 'Bajo']);
    }

    public function test_consultar_aforo_retorna_bajo_cuando_ocupacion_es_40(): void
    {
        // Given
        Http::fake(['*' => Http::response(['capacidad' => 100, 'ocupacion' => 40], 200)]);

        // When
        $response = $this->getJson('/api/sitios/plaza-de-armas/aforo');

        // Then
        $response->assertStatus(200)
            ->assertJson(['sitio_id' => 'plaza-de-armas', 'nivel_aforo' => 'Bajo']);
    }

    public function test_consultar_aforo_retorna_moderado_cuando_ocupacion_es_60(): void
    {
        // Given
        Http::fake(['*' => Http::response(['capacidad' => 100, 'ocupacion' => 60], 200)]);

        // When
        $response = $this->getJson('/api/sitios/plaza-de-armas/aforo');

        // Then
        $response->assertStatus(200)
            ->assertJson(['sitio_id' => 'plaza-de-armas', 'nivel_aforo' => 'Moderado']);
    }

    public function test_consultar_aforo_retorna_moderado_cuando_ocupacion_es_75(): void
    {
        // Given
        Http::fake(['*' => Http::response(['capacidad' => 100, 'ocupacion' => 75], 200)]);

        // When
        $response = $this->getJson('/api/sitios/plaza-de-armas/aforo');

        // Then
        $response->assertStatus(200)
            ->assertJson(['sitio_id' => 'plaza-de-armas', 'nivel_aforo' => 'Moderado']);
    }

    public function test_consultar_aforo_retorna_alto_cuando_ocupacion_es_90(): void
    {
        // Given
        Http::fake(['*' => Http::response(['capacidad' => 100, 'ocupacion' => 90], 200)]);

        // When
        $response = $this->getJson('/api/sitios/plaza-de-armas/aforo');

        // Then
        $response->assertStatus(200)
            ->assertJson(['sitio_id' => 'plaza-de-armas', 'nivel_aforo' => 'Alto']);
    }

    public function test_consultar_aforo_retorna_422_cuando_ocupacion_supera_capacidad(): void
    {
        // Given
        Http::fake(['*' => Http::response(['capacidad' => 100, 'ocupacion' => 120], 200)]);

        // When
        $response = $this->getJson('/api/sitios/plaza-de-armas/aforo');

        // Then
        $response->assertStatus(422)->assertJsonStructure(['mensaje']);
    }

    public function test_consultar_aforo_retorna_422_cuando_capacidad_es_cero(): void
    {
        // Given
        Http::fake(['*' => Http::response(['capacidad' => 0, 'ocupacion' => 0], 200)]);

        // When
        $response = $this->getJson('/api/sitios/plaza-de-armas/aforo');

        // Then
        $response->assertStatus(422)->assertJsonStructure(['mensaje']);
    }

    public function test_consultar_aforo_retorna_503_cuando_sensor_no_disponible(): void
    {
        // Given
        Http::fake(['*' => Http::response([], 500)]);

        // When
        $response = $this->getJson('/api/sitios/plaza-de-armas/aforo');

        // Then
        $response->assertStatus(503)->assertJsonStructure(['mensaje']);
    }
}