<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\TelemetriaAforoServiceInterface;
use App\Exceptions\SensorNoDisponibleException;
use App\Models\Sitio;
use App\Services\SensorDemoClient;
use App\Services\TelemetriaAforoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Controlador de telemetría IoT y resiliencia de aforo (CUS-06).
 *
 * Se limita a traducir la petición HTTP: el servicio decide si el dato es
 * vivo, de caché o histórico. Así la vista y la API comparten una sola
 * fuente de verdad y no duplican reglas de negocio.
 */
class TelemetriaController extends Controller
{
    public function __construct(
        private readonly TelemetriaAforoServiceInterface $telemetriaService,
    ) {}

    /**
     * Endpoint REST: devuelve el DTO envuelto en "data" con HTTP 200.
     *
     * Si no hay sensor ni histórico, capturamos la excepción de dominio para
     * garantizar HTTP 503 y el contrato { message, error } esperado por los
     * clientes. El render() de la excepción sigue actuando como red de
     * seguridad para peticiones no controladas.
     */
    public function showApi(Sitio $sitio): JsonResponse
    {
        try {
            $dto = $this->telemetriaService->obtenerAforoConFallback($sitio);
        } catch (SensorNoDisponibleException $excepcion) {
            return response()->json([
                'message' => $excepcion->getMessage(),
                'error' => $excepcion->codigoError(),
            ], $excepcion->getCode());
        }

        return response()->json(['data' => $dto->toArray()], 200);
    }

    /**
     * Panel web de consulta y demostración de resiliencia.
     *
     * Solo consulta cuando el formulario envía consultar=1: de este modo la
     * carga inicial no dispara peticiones al proveedor externo. Los modos
     * "vivo" y "fallback" usan un cliente determinista para que cualquier
     * visitante pueda probar el estado degradado sin configurar un sensor.
     */
    public function index(Request $request): View
    {
        $sitios = Sitio::query()
            ->whereNotNull('sensor_id')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'sensor_id']);

        $sitioId = (int) $request->query('sitio_id', (string) $sitios->first()?->id);
        $sitio = $sitios->firstWhere('id', $sitioId);
        // Por defecto mostramos el modo "En vivo" demo: así el panel nunca
        // arranca en "Sensor real" y cae a respaldo por un proveedor inexistente.
        $modo = (string) $request->query('modo', 'vivo');
        $resultado = null;
        $error = null;

        if ($request->boolean('consultar') && $sitio !== null) {
            $servicio = $this->telemetriaService;

            if ($modo === 'vivo' || $modo === 'fallback') {
                /*
                 * Demo determinista: invalidamos la clave de 15 segundos en
                 * ambos modos. Así "En vivo" no se ve convertido en "Caché" ni
                 * "Fallback" queda oculto por una lectura viva anterior.
                 */
                Cache::forget("telemetria:sitio:{$sitio->id}");

                $servicio = new TelemetriaAforoService(
                    new SensorDemoClient($modo === 'fallback'),
                );
            }

            try {
                $resultado = $servicio->obtenerAforoConFallback($sitio)->toArray();
            } catch (SensorNoDisponibleException $excepcion) {
                $error = [
                    'message' => $excepcion->getMessage(),
                    'error' => $excepcion->codigoError(),
                    'codigo_http' => $excepcion->getCode(),
                ];
            }
        }

        return view('sitios.telemetria', [
            'sitios' => $sitios,
            'sitio' => $sitio,
            'sitioId' => $sitioId,
            'modo' => $modo,
            'resultado' => $resultado,
            'error' => $error,
        ]);
    }
}
