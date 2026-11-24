<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Given: La aplicación está arrancada y la ruta raíz "/" responde sin autenticación ni datos previos.
     * When: Se realiza una petición GET a "/".
     * Then: La respuesta tiene el código HTTP 200, confirmando que el sitio está disponible.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Given: La aplicación está lista para atender la ruta raíz.
        // When: Se solicita la página principal con GET "/".
        $response = $this->get('/');

        // Then: Se verifica que la respuesta sea HTTP 200.
        $response->assertStatus(200);
    }
}
