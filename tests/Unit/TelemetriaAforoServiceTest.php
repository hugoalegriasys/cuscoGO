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

        // Given: cada caso empieza con una caché vacía para que el
        // resultado no dependa del orden en que PHPUnit ejecute los métodos.
        Cache::flush();
    }

    /**
     * Given: GET /sensores/MP-01/aforo responde {"ocupacion": 85} con HTTP 200.
     * When: se obtiene la lectura del sensor MP-01 con el cliente HTTP.
     * Then: se devuelve 85 como entero y la petición se envió a /sensores/MP-01/aforo.
     * Importancia: Fija el contrato entre el proveedor IoT y nuestro DTO.
     */
    public function test_cliente_devuelve_ocupacion_de_respuesta_valida(): void
    {
        // Given: Http::fake() sustituye el transporte real por esta respuesta.
        Http::fake([
            '*' => Http::response(['ocupacion' => 85], 200),
        ]);

        // When: resolvemos el cliente desde el contenedor, igual que la app.
        $cliente = app(SiteSensorClientInterface::class);
        $ocupacion = $cliente->obtenerLecturaSensor('MP-01');

        // Then: el valor es entero y la URL contiene el código del sensor.
        $this->assertSame(85, $ocupacion);
        Http::assertSent(static fn (Request $peticion): bool => str_contains($peticion->url(), '/sensores/MP-01/aforo'));
    }

    /**
     * Given: el sensor responde HTTP 500 después de los reintentos configurados.
     * When: se solicita la lectura del sensor MP-01 con el cliente HTTP.
     * Then: se lanza SensorNoDisponibleException con código 503.
     * Importancia: Es la señal que activa el fallback en el servicio de resiliencia.
     */
    public function test_cliente_lanza_excepcion_ante_http_500(): void
    {
        // Given: throw:false evita RequestException; el cliente detecta !successful().
        Http::fake([
            '*' => Http::response(['error' => 'sensor caído'], 500),
        ]);

        // When: se configura la excepción esperada y se solicita la lectura del sensor MP-01.
        // Then: debe lanzar SensorNoDisponibleException con código 503.
        $this->expectException(SensorNoDisponibleException::class);
        $this->expectExceptionCode(503);

        app(SiteSensorClientInterface::class)->obtenerLecturaSensor('MP-01');
    }

    /**
     * Given: Http::fake() lanza ConnectionException al intentar conectar con el sensor.
     * When: se solicita la lectura del sensor MP-01 con el cliente HTTP.
     * Then: se lanza SensorNoDisponibleException con código 503 en lugar de un error sin controlar.
     * Importancia: Un sensor que nunca responde no debe provocar un 500 sin controlar.
     */
    public function test_cliente_lanza_excepcion_ante_timeout(): void
    {
        // Given: una excepción de conexión simula el timeout de 3 segundos.
        Http::fake(static function (): never {
            throw new ConnectionException('Timeout simulado del sensor.');
        });

        // When: se configura la excepción esperada y se solicita la lectura del sensor MP-01.
        // Then: el cliente la traduce a nuestra excepción.
        $this->expectException(SensorNoDisponibleException::class);
        $this->expectExceptionCode(503);

        app(SiteSensorClientInterface::class)->obtenerLecturaSensor('MP-01');
    }

    /**
     * Given: el sensor responde HTTP 500 y aforo_lecturas guarda una lectura de 70 de hace un minuto para Machu Picchu (MP-01).
     * When: se obtiene el aforo con fallback del sitio Machu Picchu.
     * Then: se devuelve la ocupación histórica 70 con stale=true y origen fallback_bd.
     * Importancia: stale=true informa de que el dato es válido pero no en vivo.
     */
    public function test_fallback_devuelve_ultima_lectura_con_stale_true(): void
    {
        // Given: sitio con sensor y una lectura previa de hace un minuto.
        $sitio = Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);
        AforoLectura::query()->create([
            'sitio_id' => $sitio->id,
            'sensor_id' => 'MP-01',
            'ocupacion' => 70,
            'fuente' => 'iot',
            'registrado_en' => now()->subMinute(),
        ]);
        Http::fake(['*' => Http::response(['error' => 'sin señal'], 500)]);

        // When: el servicio intenta el sensor y cae al respaldo en BD.
        $dto = app(TelemetriaAforoServiceInterface::class)->obtenerAforoConFallback($sitio);

        // Then: ocupación histórica, stale=true y origen fallback_bd.
        $this->assertSame(70, $dto->ocupacion);
        $this->assertTrue($dto->stale);
        $this->assertSame('fallback_bd', $dto->origen);
    }

    /**
     * Given: el sensor está caído (HTTP 500) y aforo_lecturas no tiene lecturas para Machu Picchu (MP-01).
     * When: se obtiene el aforo con fallback de ese sitio sin histórico persistido.
     * Then: se propaga SensorNoDisponibleException con código 503 y código de error SENSOR_NO_DISPONIBLE.
     * Importancia: El HTTP 503 es la respuesta honesta cuando no hay nada que mostrar.
     */
    public function test_fallback_relanza_excepcion_sin_lecturas_previas(): void
    {
        // Given: sitio con sensor, pero sin histórico persistido.
        $sitio = Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);
        Http::fake(['*' => Http::response(['error' => 'sin señal'], 500)]);

        // When: se consulta el servicio de aforo con fallback para el sitio sin histórico.
        // Then: se propaga la excepción original con código 503.
        try {
            app(TelemetriaAforoServiceInterface::class)->obtenerAforoConFallback($sitio);
            $this->fail('Se esperaba SensorNoDisponibleException.');
        } catch (SensorNoDisponibleException $excepcion) {
            $this->assertSame(503, $excepcion->getCode());
            $this->assertSame('SENSOR_NO_DISPONIBLE', $excepcion->codigoError());
        }
    }

    /**
     * Given: el sensor MP-01 de Machu Picchu está activo y responde {"ocupacion": 85} con HTTP 200.
     * When: se consulta dos veces seguidas el aforo del mismo sitio, la segunda dentro de la ventana de caché de 15 segundos.
     * Then: la primera respuesta es iot_vivo y sin stale, la segunda viene de cache con ocupación 85 y sin stale, con una sola petición HTTP y una sola lectura guardada.
     * Importancia: Http::assertSentCount(1) demuestra que la segunda consulta no
     * saturó al proveedor y que solo se guardó una lectura en la BD.
     */
    public function test_lectura_exitosa_se_persiste_y_se_sirve_desde_cache(): void
    {
        // Given: sensor activo con ocupación 85.
        Http::fake(['*' => Http::response(['ocupacion' => 85], 200)]);
        $sitio = Sitio::factory()->create(['nombre' => 'Machu Picchu', 'sensor_id' => 'MP-01']);
        $servicio = app(TelemetriaAforoServiceInterface::class);

        // When: primera consulta en vivo y segunda dentro de la ventana de 15 s.
        $primero = $servicio->obtenerAforoConFallback($sitio);
        $segundo = $servicio->obtenerAforoConFallback($sitio);

        // Then: solo una petición HTTP, una fila guardada y origen cache.
        $this->assertSame('iot_vivo', $primero->origen);
        $this->assertFalse($primero->stale);
        $this->assertSame('cache', $segundo->origen);
        $this->assertSame(85, $segundo->ocupacion);
        $this->assertFalse($segundo->stale);
        $this->assertDatabaseCount('aforo_lecturas', 1);
        Http::assertSentCount(1);
    }
}
