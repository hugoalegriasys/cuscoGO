<?php

/**
 * Capa de pruebas unitarias de CUS-06.
 *
 * Http::fake() intercepta las peticiones de Laravel antes de que salgan a la
 * red: devuelve respuestas programadas y registra cada llamada. Eso permite
 * probar el cliente HTTP y la resiliencia sin depender de un sensor real, y
 * asserts como Http::assertSentCount(1) comprueban que la caché evitó una
 * segunda petición en menos de 15 segundos.
 */

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\SiteSensorClientInterface;
use App\Contracts\TelemetriaAforoServiceInterface;
use App\Exceptions\SensorNoDisponibleException;
use App\Models\AforoLectura;
use App\Models\Sitio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelemetriaAforoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Preparación: cada caso empieza con una caché vacía para que el
        // resultado no dependa del orden en que PHPUnit ejecute los métodos.
        Cache::flush();
    }

    /**
     * Propósito: Validar que el cliente extrae la ocupación de un payload válido.
     * Escenario: GET /sensores/MP-01/aforo responde {"ocupacion": 85} con HTTP 200.
     * Importancia: Fija el contrato entre el proveedor IoT y nuestro DTO.
     */
    public function test_cliente_devuelve_ocupacion_de_respuesta_valida(): void
    {
        // Preparación: Http::fake() sustituye el transporte real por esta respuesta.
        Http::fake([
            '*' => Http::response(['ocupacion' => 85], 200),
        ]);

        // Ejecución: resolvemos el cliente desde el contenedor, igual que la app.
        $cliente = app(SiteSensorClientInterface::class);
        $ocupacion = $cliente->obtenerLecturaSensor('MP-01');

        // Verificación: el valor es entero y la URL contiene el código del sensor.
        $this->assertSame(85, $ocupacion);
        Http::assertSent(static fn (Request $peticion): bool => str_contains($peticion->url(), '/sensores/MP-01/aforo'));
    }

    /**
     * Propósito: Convertir un HTTP 500 del proveedor en error de dominio.
     * Escenario: El sensor responde 500 después de los reintentos configurados.
     * Importancia: Es la señal que activa el fallback en el servicio de resiliencia.
     */
    public function test_cliente_lanza_excepcion_ante_http_500(): void
    {
        // Preparación: throw:false evita RequestException; el cliente detecta !successful().
        Http::fake([
            '*' => Http::response(['error' => 'sensor caído'], 500),
        ]);

        // Ejecución y verificación: debe lanzar SensorNoDisponibleException con código 503.
        $this->expectException(SensorNoDisponibleException::class);
        $this->expectExceptionCode(503);

        app(SiteSensorClientInterface::class)->obtenerLecturaSensor('MP-01');
    }

    /**
     * Propósito: Cubrir un timeout de conexión, no solo un código HTTP de error.
     * Escenario: Http::fake() lanza ConnectionException al intentar conectar.
     * Importancia: Un sensor que nunca responde no debe provocar un 500 sin controlar.
     */
    public function test_cliente_lanza_excepcion_ante_timeout(): void
    {
        // Preparación: una excepción de conexión simula el timeout de 3 segundos.
        Http::fake(static function (): never {
            throw new ConnectionException('Timeout simulado del sensor.');
        });

        // Ejecución y verificación: el cliente la traduce a nuestra excepción.
        $this->expectException(SensorNoDisponibleException::class);
        $this->expectExceptionCode(503);

        app(SiteSensorClientInterface::class)->obtenerLecturaSensor('MP-01');
    }

    /**
     * Propósito: Confirmar que ante caída del sensor se usa la última lectura guardada.
     * Escenario: Sensor HTTP 500 y lectura histórica de 70 en aforo_lecturas.
     * Importancia: stale=true informa de que el dato es válido pero no en vivo.
     */
    public function test_fallback_devuelve_ultima_lectura_con_stale_true(): void
    {
        // Preparación: sitio con sensor y una lectura previa de hace un minuto.
        $sitio = Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);
        AforoLectura::query()->create([
            'sitio_id' => $sitio->id,
            'sensor_id' => 'MP-01',
            'ocupacion' => 70,
            'fuente' => 'iot',
            'registrado_en' => now()->subMinute(),
        ]);
        Http::fake(['*' => Http::response(['error' => 'sin señal'], 500)]);

        // Ejecución: el servicio intenta el sensor y cae al respaldo en BD.
        $dto = app(TelemetriaAforoServiceInterface::class)->obtenerAforoConFallback($sitio);

        // Verificación: ocupación histórica, stale=true y origen fallback_bd.
        $this->assertSame(70, $dto->ocupacion);
        $this->assertTrue($dto->stale);
        $this->assertSame('fallback_bd', $dto->origen);
    }

    /**
     * Propósito: Evitar devolver un dato inventado cuando no existe respaldo.
     * Escenario: Sensor caído y tabla aforo_lecturas vacía para ese sitio.
     * Importancia: El HTTP 503 es la respuesta honesta cuando no hay nada que mostrar.
     */
    public function test_fallback_relanza_excepcion_sin_lecturas_previas(): void
    {
        // Preparación: sitio con sensor, pero sin histórico persistido.
        $sitio = Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);
        Http::fake(['*' => Http::response(['error' => 'sin señal'], 500)]);

        // Ejecución y verificación: se propaga la excepción original con código 503.
        try {
            app(TelemetriaAforoServiceInterface::class)->obtenerAforoConFallback($sitio);
            $this->fail('Se esperaba SensorNoDisponibleException.');
        } catch (SensorNoDisponibleException $excepcion) {
            $this->assertSame(503, $excepcion->getCode());
            $this->assertSame('SENSOR_NO_DISPONIBLE', $excepcion->codigoError());
        }
    }

    /**
     * Propósito: Comprobar la persistencia en vivo y la caché de 15 segundos.
     * Escenario: Dos consultas consecutivas al mismo sitio con sensor activo.
     * Importancia: Http::assertSentCount(1) demuestra que la segunda consulta no
     * saturó al proveedor y que solo se guardó una lectura en la BD.
     */
    public function test_lectura_exitosa_se_persiste_y_se_sirve_desde_cache(): void
    {
        // Preparación: sensor activo con ocupación 85.
        Http::fake(['*' => Http::response(['ocupacion' => 85], 200)]);
        $sitio = Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);
        $servicio = app(TelemetriaAforoServiceInterface::class);

        // Ejecución: primera consulta en vivo y segunda dentro de la ventana de 15 s.
        $primero = $servicio->obtenerAforoConFallback($sitio);
        $segundo = $servicio->obtenerAforoConFallback($sitio);

        // Verificación: solo una petición HTTP, una fila guardada y origen cache.
        $this->assertSame('iot_vivo', $primero->origen);
        $this->assertFalse($primero->stale);
        $this->assertSame('cache', $segundo->origen);
        $this->assertSame(85, $segundo->ocupacion);
        $this->assertFalse($segundo->stale);
        $this->assertDatabaseCount('aforo_lecturas', 1);
        Http::assertSentCount(1);
    }
}
