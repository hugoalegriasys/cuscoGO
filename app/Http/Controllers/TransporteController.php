<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\TransporteServiceInterface;
use App\Http\Requests\CalcularCostoTransporteRequest;
use App\Services\TransporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class TransporteController extends Controller
{
    public function __construct(
        private readonly TransporteServiceInterface $transporteService,
    ) {}

    public function index(): View
    {
        return view('transporte.calcular', [
            'serviciosReferencia' => $this->transporteService->obtenerServiciosReferencia(),
            'resultado' => session('resultado_transporte'),
        ]);
    }

    public function calcularWeb(CalcularCostoTransporteRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        try {
            $costoTotal = $this->transporteService->calcularCostoTransporte(
                (string) $datos['tipo_servicio'],
                (int) $datos['pasajeros'],
                (float) $datos['precio_unitario'],
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['transporte' => $exception->getMessage()]);
        }

        $resultado = $this->construirResultado(
            (string) $datos['tipo_servicio'],
            (int) $datos['pasajeros'],
            (float) $datos['precio_unitario'],
            $costoTotal,
        );

        return redirect()
            ->route('transporte.calcular')
            ->with('resultado_transporte', $resultado)
            ->withInput();
    }

    public function calcularApi(CalcularCostoTransporteRequest $request): JsonResponse
    {
        $datos = $request->validated();

        try {
            $costoTotal = $this->transporteService->calcularCostoTransporte(
                (string) $datos['tipo_servicio'],
                (int) $datos['pasajeros'],
                (float) $datos['precio_unitario'],
            );
        } catch (InvalidArgumentException $exception) {
            $mensaje = $exception->getMessage();

            return response()->json([
                'status' => 'error',
                'message' => $mensaje,
                'errors' => [
                    'transporte' => [$mensaje],
                ],
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
            'data' => [
                'tipo_servicio' => (string) $datos['tipo_servicio'],
                'pasajeros' => (int) $datos['pasajeros'],
                'precio_unitario' => (float) $datos['precio_unitario'],
                'costo_total' => $costoTotal,
                'modalidad_cobro' => $this->modalidadCobro((string) $datos['tipo_servicio']),
            ],
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @return array<string, mixed>
     */
    private function construirResultado(
        string $tipoServicio,
        int $pasajeros,
        float $precioUnitario,
        float $costoTotal,
    ): array {
        $etiqueta = $this->etiquetaTipo($tipoServicio);

        return [
            'tipo_servicio' => $tipoServicio,
            'tipo_etiqueta' => $etiqueta,
            'pasajeros' => $pasajeros,
            'precio_unitario' => $precioUnitario,
            'costo_total' => $costoTotal,
            'modalidad_cobro' => $this->modalidadCobro($tipoServicio),
            'mensaje' => sprintf('El costo total del servicio %s es de S/ %s.', $etiqueta, number_format($costoTotal, 2, '.', ',')),
        ];
    }

    private function etiquetaTipo(string $tipoServicio): string
    {
        return match ($tipoServicio) {
            TransporteService::BUS_COLECTIVO => 'Bus Colectivo',
            TransporteService::TREN => 'Tren Turístico',
            TransporteService::PRIVADO => 'Transporte Privado',
            default => $tipoServicio,
        };
    }

    private function modalidadCobro(string $tipoServicio): string
    {
        return $tipoServicio === TransporteService::PRIVADO
            ? 'TARIFA FIJA POR VEHÍCULO'
            : 'COBRO POR PASAJERO';
    }
}
