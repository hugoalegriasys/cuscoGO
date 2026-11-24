<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\CercaniaServiceInterface;
use App\Http\Requests\BuscarSitiosCercanosRequest;
use App\Models\Sitio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;

class CercaniaController extends Controller
{
    public function __construct(
        private readonly CercaniaServiceInterface $cercaniaService,
    ) {}

    public function index(): View
    {
        return view('sitios.cercanos', [
            'resultado' => session('resultado_cercania'),
        ]);
    }

    public function buscarWeb(BuscarSitiosCercanosRequest $request): RedirectResponse
    {
        $parametros = $this->parametros($request);

        try {
            $sitios = $this->cercaniaService->buscarSitiosCercanos(
                $parametros['latitud'],
                $parametros['longitud'],
                $parametros['radio_km'],
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['cercania' => $exception->getMessage()]);
        }

        return redirect()
            ->route('sitios.cercanos')
            ->with('resultado_cercania', $this->construirResultado($parametros, $sitios))
            ->withInput();
    }

    public function buscarApi(BuscarSitiosCercanosRequest $request): JsonResponse
    {
        $parametros = $this->parametros($request);

        try {
            $sitios = $this->cercaniaService->buscarSitiosCercanos(
                $parametros['latitud'],
                $parametros['longitud'],
                $parametros['radio_km'],
            );
        } catch (InvalidArgumentException $exception) {
            $mensaje = $exception->getMessage();

            return response()->json([
                'status' => 'error',
                'message' => $mensaje,
                'errors' => [
                    'cercania' => [$mensaje],
                ],
            ], 422);
        }

        return response()->json(
            $this->construirResultado($parametros, $sitios),
            200,
            [],
            JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    /**
     * @return array{latitud: float, longitud: float, radio_km: float}
     */
    private function parametros(BuscarSitiosCercanosRequest $request): array
    {
        $datos = $request->validated();

        return [
            'latitud' => (float) $datos['latitud'],
            'longitud' => (float) $datos['longitud'],
            'radio_km' => isset($datos['radio_km']) ? (float) $datos['radio_km'] : 5.0,
        ];
    }

    /**
     * @param  array{latitud: float, longitud: float, radio_km: float}  $parametros
     * @param  Collection<int, Sitio>  $sitios
     * @return array<string, mixed>
     */
    private function construirResultado(array $parametros, $sitios): array
    {
        $data = $sitios->map(static fn (Sitio $sitio): array => [
            'id' => (int) $sitio->id,
            'nombre' => (string) $sitio->nombre,
            'categoria' => $sitio->categoria,
            'ubicacion' => $sitio->ubicacion,
            'latitud' => (float) $sitio->latitud,
            'longitud' => (float) $sitio->longitud,
            'distancia_km' => (float) $sitio->distancia_km,
        ])->values()->all();

        return [
            'status' => 'ok',
            'origen' => [
                'latitud' => $parametros['latitud'],
                'longitud' => $parametros['longitud'],
                'radio_km' => $parametros['radio_km'],
            ],
            'total' => count($data),
            'data' => $data,
        ];
    }
}
