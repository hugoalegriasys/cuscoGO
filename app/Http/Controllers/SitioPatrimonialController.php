<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\DisponibilidadServiceInterface;
use App\Http\Requests\SitioPatrimonialRequest;
use App\Http\Requests\VerificarOperatividadRequest;
use App\Models\SitioPatrimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class SitioPatrimonialController extends Controller
{
    public function __construct(
        private readonly DisponibilidadServiceInterface $disponibilidadService
    ) {}

    /**
     * Muestra el listado de sitios patrimoniales con su estado
     * de operatividad evaluado con la hora actual del sistema.
     */
    public function index(Request $request): View
    {
        $sitios = SitioPatrimonial::query()
            ->orderBy('nombre')
            ->get();

        $estados = [];

        foreach ($sitios as $sitio) {
            try {
                $estados[$sitio->getKey()] = $this->disponibilidadService->evaluarEstadoOperatividad($sitio);
            } catch (InvalidArgumentException $excepcion) {
                $estados[$sitio->getKey()] = null;
            }
        }

        return view('sitios.index', [
            'sitios' => $sitios,
            'estados' => $estados,
        ]);
    }

    /**
     * Muestra el formulario de registro de un sitio patrimonial.
     */
    public function create(Request $request): View
    {
        return view('sitios.create');
    }

    /**
     * Registra un nuevo sitio patrimonial en la base de datos.
     */
    public function store(SitioPatrimonialRequest $request): RedirectResponse
    {
        $sitio = SitioPatrimonial::query()->create($request->datosSitio());

        return redirect()
            ->route('sitios.show', ['sitio' => $sitio])
            ->with('exito', sprintf('El sitio patrimonial "%s" fue registrado correctamente.', $sitio->nombre));
    }

    /**
     * Muestra el detalle de un sitio patrimonial con su estado actual.
     */
    public function show(Request $request, SitioPatrimonial $sitio): View
    {
        try {
            $estado = $this->disponibilidadService->evaluarEstadoOperatividad($sitio);
        } catch (InvalidArgumentException $excepcion) {
            $estado = null;
        }

        return view('sitios.show', [
            'sitio' => $sitio,
            'estado' => $estado,
        ]);
    }

    /**
     * Muestra el formulario de edición de un sitio patrimonial.
     */
    public function edit(Request $request, SitioPatrimonial $sitio): View
    {
        return view('sitios.edit', [
            'sitio' => $sitio,
        ]);
    }

    /**
     * Actualiza los datos de un sitio patrimonial existente.
     */
    public function update(SitioPatrimonialRequest $request, SitioPatrimonial $sitio): RedirectResponse
    {
        $sitio->update($request->datosSitio());

        return redirect()
            ->route('sitios.show', ['sitio' => $sitio])
            ->with('exito', sprintf('El sitio patrimonial "%s" fue actualizado correctamente.', $sitio->nombre));
    }

    /**
     * Elimina (soft delete) un sitio patrimonial.
     */
    public function destroy(Request $request, SitioPatrimonial $sitio): RedirectResponse
    {
        $nombreSitio = $sitio->nombre;

        $sitio->delete();

        return redirect()
            ->route('sitios.index')
            ->with('exito', sprintf('El sitio patrimonial "%s" fue eliminado correctamente.', $nombreSitio));
    }

    /**
     * Evalúa el estado de operatividad de un sitio patrimonial (endpoint AJAX).
     *
     * Respuesta estandarizada:
     * - 200: { exitoso: true, mensaje, datos }
     * - 422: { exitoso: false, mensaje, errores }
     */
    public function verificar(VerificarOperatividadRequest $request, SitioPatrimonial $sitio): JsonResponse
    {
        try {
            $estado = $this->disponibilidadService->evaluarEstadoOperatividad(
                $sitio,
                $request->horaConsulta()
            );
        } catch (InvalidArgumentException $excepcion) {
            return response()->json(
                [
                    'exitoso' => false,
                    'mensaje' => 'No fue posible evaluar el estado de operatividad del sitio patrimonial.',
                    'errores' => [$excepcion->getMessage()],
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return response()->json([
            'exitoso' => true,
            'mensaje' => $estado->mensaje,
            'datos' => $estado->toArray(),
        ]);
    }
}
