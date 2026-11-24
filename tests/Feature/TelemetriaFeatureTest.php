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

        // Given: sin caché residual entre casos de prueba.
        Cache::flush();
    }

    /**
     * Given: existe el sitio 'Machu Picchu' con sensor MP-01 y un proveedor IoT simulado con ocupación 85.
     * When: se solicita GET /api/sitios/{id}/telemetria.
     * Then: responde 200 con origen 'iot_vivo', stale false, ocupación 85 y una lectura guardada en aforo_lecturas.
     * Importancia: Detecta rutas mal enlazadas, bindings de modelo incorrectos y
     * fallos al guardar la lectura en aforo_lecturas.
     */
    #[Test]
    public function la_api_devuelve_aforo_en_vivo_y_lo_persiste(): void
    {
        // Given: sitio con sensor y proveedor IoT simulado con Http::fake().
        $sitio = Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);
        Http::fake(['*' => Http::response(['ocupacion' => 85], 200)]);

        // When: petición HTTP real al endpoint de la API.
        $respuesta = $this->getJson(route('api.sitios.telemetria', $sitio));

        // Then: HTTP 200, DTO sin stale y una lectura guardada.
        $respuesta->assertOk();
        $respuesta->assertJsonPath('data.sitio_id', $sitio->id);
        $respuesta->assertJsonPath('data.ocupacion', 85);
        $respuesta->assertJsonPath('data.stale', false);
        $respuesta->assertJsonPath('data.origen', 'iot_vivo');
        $this->assertDatabaseCount('aforo_lecturas', 1);
    }

    /**
     * Given: existe 'Qorikancha' con sensor QK-02, una lectura histórica de 70 y el sensor responde HTTP 500.
     * When: se solicita GET /api/sitios/{id}/telemetria.
     * Then: responde 200 con ocupación 70, origen 'fallback_bd' y stale=true.
     * Importancia: El cliente recibe un dato útil y la marca stale=true para no
     * confundirlo con una medición reciente.
     */
    #[Test]
    public function la_api_devuelve_respaldo_historico_con_stale_true(): void
    {
        // Given: histórico de 70 y fallo del sensor.
        $sitio = Sitio::factory()->create(['nombre' => 'Qorikancha', 'sensor_id' => 'QK-02']);
        AforoLectura::query()->create([
            'sitio_id' => $sitio->id,
            'sensor_id' => 'QK-02',
            'ocupacion' => 70,
            'fuente' => 'iot',
            'registrado_en' => now()->subMinute(),
        ]);
        Http::fake(['*' => Http::response(['error' => 'sin señal'], 500)]);

        // When: la excepción del cliente se convierte en fallback.
        $respuesta = $this->getJson(route('api.sitios.telemetria', $sitio));

        // Then: HTTP 200 con el valor histórico y flag de degradación.
        $respuesta->assertOk();
        $respuesta->assertJsonPath('data.ocupacion', 70);
        $respuesta->assertJsonPath('data.stale', true);
        $respuesta->assertJsonPath('data.origen', 'fallback_bd');
    }

    /**
     * Given: existe 'Machu Picchu' con sensor MP-01 caído y sin lecturas previas en la base de datos.
     * When: se solicita GET /api/sitios/{id}/telemetria.
     * Then: responde 503 con error 'SENSOR_NO_DISPONIBLE' y la estructura { message, error }.
     * Importancia: Fija el contrato de error { message, error } para integraciones.
     */
    #[Test]
    public function la_api_devuelve_503_sin_lecturas_previas(): void
    {
        // Given: sitio sin histórico y sensor que falla.
        $sitio = Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);
        Http::fake(['*' => Http::response(['error' => 'sin señal'], 500)]);

        // When: petición HTTP que debe terminar en error controlado.
        $respuesta = $this->getJson(route('api.sitios.telemetria', $sitio));

        // Then: 503 y código de error estable.
        $respuesta->assertStatus(503);
        $respuesta->assertJsonPath('error', 'SENSOR_NO_DISPONIBLE');
        $respuesta->assertJsonStructure(['message', 'error']);
    }

    /**
     * Given: existe 'Machu Picchu' con sensor MP-01 como opción del selector.
     * When: se solicita GET /sitios/telemetria sin parámetros de consulta.
     * Then: responde 200 con el título CUS-06, los selectores data-cy y el estado vacío inicial.
     * Importancia: Detecta vista que no compila, ruta ausente o data-cy renombrado.
     */
    #[Test]
    public function carga_la_vista_del_panel_de_telemetria(): void
    {
        // Given: sitio con sensor para que exista opción en el selector.
        Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);

        // When: carga inicial, sin consultar al proveedor IoT.
        $respuesta = $this->get(route('sitios.telemetria.index'));

        // Then: HTTP 200, controles visibles y estado vacío inicial.
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
