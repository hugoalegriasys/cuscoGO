<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AforoService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use RuntimeException;

class SitioAforoController extends Controller
{
    public function __construct(private AforoService $aforoService)
    {
    }

    public function show(string $sitioId): JsonResponse
    {
        try {
            $nivelAforo = $this->aforoService->calcularNivelAforo($sitioId);

            return response()->json([
                'sitio_id' => $sitioId,
                'nivel_aforo' => $nivelAforo,
            ], 200);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'mensaje' => $exception->getMessage(),
            ], 422);
        } catch (RuntimeException $exception) {
            return response()->json([
                'mensaje' => 'Sensor no disponible.',
            ], 503);
        }
    }
}