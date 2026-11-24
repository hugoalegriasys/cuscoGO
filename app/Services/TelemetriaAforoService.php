<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SiteSensorClientInterface;
use App\Contracts\TelemetriaAforoServiceInterface;
use App\DTOs\TelemetriaAforoDTO;
use App\Exceptions\SensorNoDisponibleException;
use App\Models\AforoLectura;
use App\Models\Sitio;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de resiliencia de aforo (CUS-06).
 *
 * Orquesta tres capas: caché muy corta, sensor en vivo con persistencia y
 * respaldo histórico. El objetivo es que un fallo externo degrade el dato,
 * no la experiencia: el usuario ve la última lectura válida con stale=true
 * en vez de un error 500.
 */
class TelemetriaAforoService implements TelemetriaAforoServiceInterface
{
    /** Ventana de caché: 15 segundos evitan saturar al proveedor sin entregar datos añejos. */
    private const TTL_CACHE_SEGUNDOS = 15;

    private const ORIGEN_CACHE = 'cache';

    private const ORIGEN_IOT_VIVO = 'iot_vivo';

    private const ORIGEN_FALLBACK_BD = 'fallback_bd';

    public function __construct(
        private readonly SiteSensorClientInterface $sensorClient,
    ) {}

    /**
     * Resuelve el aforo aplicando caché, consulta en vivo y fallback.
     *
     * @throws SensorNoDisponibleException Si el sensor falla y no hay lecturas previas.
     */
    public function obtenerAforoConFallback(Sitio $sitio): TelemetriaAforoDTO
    {
        $sensorId = $sitio->sensor_id;

        if (! is_string($sensorId) || trim($sensorId) === '') {
            throw new SensorNoDisponibleException('El sitio no tiene un código de sensor IoT registrado.');
        }

        /*
         * Etapa 1 — Caché de 15 segundos por sitio.
         * La clave incluye el id del sitio para que un sensor nunca sirva el
         * aforo de otro. Cache::put usa una fecha absoluta (now + 15s) para
         * que el TTL sea explícito y fácil de verificar en las pruebas.
         */
        $claveCache = "telemetria:sitio:{$sitio->id}";
        $cacheado = Cache::get($claveCache);

        if ($cacheado instanceof TelemetriaAforoDTO) {
            return new TelemetriaAforoDTO(
                sitioId: $cacheado->sitioId,
                sitioNombre: (string) $sitio->nombre,
                sensorId: $sensorId,
                ocupacion: $cacheado->ocupacion,
                stale: false,
                origen: self::ORIGEN_CACHE,
                actualizadoEn: $cacheado->actualizadoEn,
            );
        }

        /*
         * Etapa 2 — Consulta en vivo. Capturamos únicamente la excepción del
         * cliente: un error de base de datos al persistir no debe disfrazarse
         * de caída del sensor.
         */
        try {
            $ocupacion = $this->sensorClient->obtenerLecturaSensor($sensorId);
        } catch (SensorNoDisponibleException $excepcion) {
            return $this->lecturaDeRespaldo($sitio, $sensorId, $excepcion);
        }

        $actualizadoEn = now()->toDateTimeString();

        // Guardamos la fotografía del sensor para futuros fallos (Etapa 3).
        AforoLectura::query()->create([
            'sitio_id' => $sitio->id,
            'sensor_id' => $sensorId,
            'ocupacion' => $ocupacion,
            'fuente' => 'iot',
            'registrado_en' => $actualizadoEn,
        ]);

        $dto = new TelemetriaAforoDTO(
            sitioId: (int) $sitio->id,
            sitioNombre: (string) $sitio->nombre,
            sensorId: $sensorId,
            ocupacion: $ocupacion,
            stale: false,
            origen: self::ORIGEN_IOT_VIVO,
            actualizadoEn: $actualizadoEn,
        );

        Cache::put($claveCache, $dto, now()->addSeconds(self::TTL_CACHE_SEGUNDOS));

        return $dto;
    }

    /**
     * Etapa 3 — Respaldo ante fallo del sensor.
     *
     * Buscamos la lectura más reciente ordenando por fecha y por id (el id
     * desempata lecturas registradas en el mismo segundo). No escribimos en
     * caché: así, en cuanto el sensor vuelve, la siguiente petición intenta
     * refresh en vivo y se recupera rápido sin esperar el TTL de 15 segundos.
     *
     * @throws SensorNoDisponibleException Si no existe ninguna lectura previa.
     */
    private function lecturaDeRespaldo(
        Sitio $sitio,
        string $sensorId,
        SensorNoDisponibleException $excepcion,
    ): TelemetriaAforoDTO {
        $ultimaLectura = AforoLectura::query()
            ->where('sitio_id', $sitio->id)
            ->orderByDesc('registrado_en')
            ->orderByDesc('id')
            ->first();

        if ($ultimaLectura === null) {
            // Sin histórico no hay dato que devolver: propagamos el 503.
            throw $excepcion;
        }

        return new TelemetriaAforoDTO(
            sitioId: (int) $sitio->id,
            sitioNombre: (string) $sitio->nombre,
            sensorId: $sensorId,
            ocupacion: (int) $ultimaLectura->ocupacion,
            stale: true,
            origen: self::ORIGEN_FALLBACK_BD,
            actualizadoEn: $ultimaLectura->registrado_en?->toDateTimeString() ?? now()->toDateTimeString(),
        );
    }
}
