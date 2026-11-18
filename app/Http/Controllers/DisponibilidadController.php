<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\DisponibilidadServiceInterface;
use App\Http\Requests\ConsultarDisponibilidadRequest;
use App\Models\HorarioAtencion;
use App\Models\Sitio;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class DisponibilidadController extends Controller
{
    private const ZONA_HORARIA = 'America/Lima';

    /**
     * @var array<int, string>
     */
    private const DIAS_SEMANA = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    public function __construct(
        private readonly DisponibilidadServiceInterface $disponibilidadService,
    ) {}

    public function index(): View
    {
        $ahora = CarbonImmutable::now(self::ZONA_HORARIA);

        $sitios = Sitio::query()
            ->where('activo', true)
            ->with(['horariosActivos'])
            ->orderBy('nombre')
            ->get();

        return view('sitios.disponibilidad', [
            'sitios' => $sitios,
            'horaActual' => $ahora->format('H:i'),
            'fechaActual' => $ahora->format('Y-m-d'),
            'diaSemanaActual' => $ahora->dayOfWeekIso,
            'resultado' => session('resultado'),
        ]);
    }

    public function evaluar(ConsultarDisponibilidadRequest $request): RedirectResponse|JsonResponse
    {
        $datos = $request->validated();
        $horaActual = (string) $datos['hora_actual'];

        try {
            $contexto = $this->resolverHorario($datos);

            $abierto = $this->disponibilidadService->estaOperativo(
                $horaActual,
                $contexto['apertura'],
                $contexto['cierre'],
            );

            $resultado = $this->construirResultado($abierto, $horaActual, $contexto);
        } catch (InvalidArgumentException $exception) {
            $mensaje = $exception->getMessage();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $mensaje,
                    'errors' => [
                        'disponibilidad' => [$mensaje],
                    ],
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['disponibilidad' => $mensaje]);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($resultado, 200);
        }

        return redirect()
            ->route('sitios.disponibilidad.index')
            ->with('resultado', $resultado)
            ->withInput();
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{apertura: string, cierre: string, sitio: ?string, dia_semana: int, fecha: string}
     */
    private function resolverHorario(array $datos): array
    {
        $fecha = $this->resolverFecha($datos['fecha'] ?? null);

        if (empty($datos['sitio_id'])) {
            return [
                'apertura' => (string) $datos['apertura'],
                'cierre' => (string) $datos['cierre'],
                'sitio' => null,
                'dia_semana' => $fecha->dayOfWeekIso,
                'fecha' => $fecha->format('Y-m-d'),
            ];
        }

        $sitio = Sitio::query()->find((int) $datos['sitio_id']);

        if (! $sitio instanceof Sitio) {
            throw new InvalidArgumentException('El sitio patrimonial seleccionado no existe.');
        }

        if (! $sitio->activo) {
            throw new InvalidArgumentException(
                sprintf('El sitio patrimonial "%s" no se encuentra habilitado para visitas.', $sitio->nombre),
            );
        }

        $horario = $sitio->horarios()
            ->where('dia_semana', $fecha->dayOfWeekIso)
            ->where('activo', true)
            ->first();

        if (! $horario instanceof HorarioAtencion) {
            throw new InvalidArgumentException(
                sprintf(
                    'El sitio patrimonial "%s" no tiene un horario de atención activo para el día %s.',
                    $sitio->nombre,
                    self::DIAS_SEMANA[$fecha->dayOfWeekIso],
                ),
            );
        }

        return [
            'apertura' => (string) $horario->apertura,
            'cierre' => (string) $horario->cierre,
            'sitio' => $sitio->nombre,
            'dia_semana' => $fecha->dayOfWeekIso,
            'fecha' => $fecha->format('Y-m-d'),
        ];
    }

    private function resolverFecha(mixed $fechaSolicitada): CarbonImmutable
    {
        if ($fechaSolicitada === null || $fechaSolicitada === '') {
            return CarbonImmutable::now(self::ZONA_HORARIA)->startOfDay();
        }

        $fechaTexto = (string) $fechaSolicitada;
        $fecha = CarbonImmutable::createFromFormat('!Y-m-d', $fechaTexto, self::ZONA_HORARIA);

        if ($fecha === false || $fecha->format('Y-m-d') !== $fechaTexto) {
            throw new InvalidArgumentException('La fecha proporcionada no es válida.');
        }

        return $fecha;
    }

    /**
     * @param  array{apertura: string, cierre: string, sitio: ?string, dia_semana: int, fecha: string}  $contexto
     * @return array<string, mixed>
     */
    private function construirResultado(bool $abierto, string $horaActual, array $contexto): array
    {
        $nombreSitio = $contexto['sitio'] ?? 'Horario consultado';
        $mensaje = $abierto
            ? sprintf('El sitio "%s" está ABIERTO en este momento.', $nombreSitio)
            : sprintf('El sitio "%s" está CERRADO en este momento.', $nombreSitio);

        return [
            'abierto' => $abierto,
            'estado' => $abierto ? 'ABIERTO' : 'CERRADO',
            'mensaje' => $mensaje,
            'hora_actual' => $horaActual,
            'apertura' => $contexto['apertura'],
            'cierre' => $contexto['cierre'],
            'horario' => sprintf('%s - %s', $contexto['apertura'], $contexto['cierre']),
            'sitio' => $contexto['sitio'],
            'fecha' => $contexto['fecha'],
            'dia_semana' => $contexto['dia_semana'],
            'dia_semana_nombre' => self::DIAS_SEMANA[$contexto['dia_semana']] ?? '',
            'origen' => $contexto['sitio'] === null ? 'manual' : 'sitio',
        ];
    }
}
