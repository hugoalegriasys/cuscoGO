<?php

/**
 * Capa de pruebas Feature (HTTP/API) de CUS-06.
 *
 * Recorre las rutas reales, el binding condicional de {sitio}, el controlador
 * y la vista Blade. Http::fake() sigue interceptando las peticiones IoT para
 * que los escenarios sean repetibles, pero aquí verificamos ya la respuesta
 * HTTP completa: códigos 200/503, envelope JSON y renderizado de data-cy.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AforoLectura;
use App\Models\Sitio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelemetriaFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Las vistas usan @vite; el manifest no es necesario en pruebas.
        $this->withoutVite();

        // Preparación: sin caché residual entre casos de prueba.
        Cache::flush();
    }

    /**
     * Propósito: Verificar el contrato JSON de éxito y la persistencia.
     * Escenario: GET /api/sitios/{id}/telemetria con sensor activo (ocupación 85).
     * Importancia: Detecta rutas mal enlazadas, bindings de modelo incorrectos y
     * fallos al guardar la lectura en aforo_lecturas.
     */
    #[Test]
    public function la_api_devuelve_aforo_en_vivo_y_lo_persiste(): void
    {
        // Preparación: sitio con sensor y proveedor IoT simulado con Http::fake().
        $sitio = Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);
        Http::fake(['*' => Http::response(['ocupacion' => 85], 200)]);

        // Ejecución: petición HTTP real al endpoint de la API.
        $respuesta = $this->getJson(route('api.sitios.telemetria', $sitio));

        // Verificación: HTTP 200, DTO sin stale y una lectura guardada.
        $respuesta->assertOk();
        $respuesta->assertJsonPath('data.sitio_id', $sitio->id);
        $respuesta->assertJsonPath('data.ocupacion', 85);
        $respuesta->assertJsonPath('data.stale', false);
        $respuesta->assertJsonPath('data.origen', 'iot_vivo');
        $this->assertDatabaseCount('aforo_lecturas', 1);
    }

    /**
     * Propósito: Confirmar que la API degrada a histórico sin cambiar el código 200.
     * Escenario: Sensor HTTP 500 y lectura previa de 70 en aforo_lecturas.
     * Importancia: El cliente recibe un dato útil y la marca stale=true para no
     * confundirlo con una medición reciente.
     */
    #[Test]
    public function la_api_devuelve_respaldo_historico_con_stale_true(): void
    {
        // Preparación: histórico de 70 y fallo del sensor.
        $sitio = Sitio::factory()->create(['nombre' => 'Qorikancha', 'sensor_id' => 'QK-02']);
        AforoLectura::query()->create([
            'sitio_id' => $sitio->id,
            'sensor_id' => 'QK-02',
            'ocupacion' => 70,
            'fuente' => 'iot',
            'registrado_en' => now()->subMinute(),
        ]);
        Http::fake(['*' => Http::response(['error' => 'sin señal'], 500)]);

        // Ejecución: la excepción del cliente se convierte en fallback.
        $respuesta = $this->getJson(route('api.sitios.telemetria', $sitio));

        // Verificación: HTTP 200 con el valor histórico y flag de degradación.
        $respuesta->assertOk();
        $respuesta->assertJsonPath('data.ocupacion', 70);
        $respuesta->assertJsonPath('data.stale', true);
        $respuesta->assertJsonPath('data.origen', 'fallback_bd');
    }

    /**
     * Propósito: Comprobar el HTTP 503 cuando no hay ningún dato de respaldo.
     * Escenario: Sensor caído y base de datos sin lecturas para el sitio.
     * Importancia: Fija el contrato de error { message, error } para integraciones.
     */
    #[Test]
    public function la_api_devuelve_503_sin_lecturas_previas(): void
    {
        // Preparación: sitio sin histórico y sensor que falla.
        $sitio = Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);
        Http::fake(['*' => Http::response(['error' => 'sin señal'], 500)]);

        // Ejecución: petición HTTP que debe terminar en error controlado.
        $respuesta = $this->getJson(route('api.sitios.telemetria', $sitio));

        // Verificación: 503 y código de error estable.
        $respuesta->assertStatus(503);
        $respuesta->assertJsonPath('error', 'SENSOR_NO_DISPONIBLE');
        $respuesta->assertJsonStructure(['message', 'error']);
    }

    /**
     * Propósito: Asegurar que el panel web carga con sus selectores Cypress.
     * Escenario: GET /sitios/telemetria sin parámetros de consulta.
     * Importancia: Detecta vista que no compila, ruta ausente o data-cy renombrado.
     */
    #[Test]
    public function carga_la_vista_del_panel_de_telemetria(): void
    {
        // Preparación: sitio con sensor para que exista opción en el selector.
        Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);

        // Ejecución: carga inicial, sin consultar al proveedor IoT.
        $respuesta = $this->get(route('sitios.telemetria.index'));

        // Verificación: HTTP 200, controles visibles y estado vacío inicial.
        $respuesta->assertOk();
        $respuesta->assertSee('CUS-06 · Telemetría IoT y Resiliencia de Aforo');
        $respuesta->assertSee('data-cy="form-telemetria"', false);
        $respuesta->assertSee('data-cy="selector-sitio"', false);
        $respuesta->assertSee('data-cy="modo"', false);
        $respuesta->assertSee('data-cy="btn-consultar"', false);
        $respuesta->assertSee('data-cy="estado-vacio"', false);
        $respuesta->assertSee('En vivo · demo (85%)');
        $respuesta->assertSee('Fallback · simular caída (resiliencia)');
        $respuesta->assertSee('Machu Picchu');
    }
}
