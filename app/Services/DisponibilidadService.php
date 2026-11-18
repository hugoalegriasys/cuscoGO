<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DisponibilidadServiceInterface;
use App\DTOs\EstadoOperatividadDTO;
use App\Models\SitioPatrimonial;
use Carbon\Carbon;
use InvalidArgumentException;

class DisponibilidadService implements DisponibilidadServiceInterface
{
    private const PATRON_HORA = '/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/';

    public function esFormatoHoraValido(string $hora): bool
    {
        return preg_match(self::PATRON_HORA, $hora) === 1;
    }

    public function esHorarioValido(string $apertura, string $cierre): bool
    {
        if (! $this->esFormatoHoraValido($apertura) || ! $this->esFormatoHoraValido($cierre)) {
            return false;
        }

        return $this->horaAMinutos($apertura) < $this->horaAMinutos($cierre);
    }

    public function estaOperativo(string $horaActual, string $apertura, string $cierre): bool
    {
        if (! $this->esFormatoHoraValido($horaActual)) {
            throw new InvalidArgumentException(
                sprintf(
                    'La hora actual "%s" no es válida. Debe usar el formato HH:MM dentro del rango 00:00 - 23:59.',
                    $horaActual,
                ),
            );
        }

        if (! $this->esHorarioValido($apertura, $cierre)) {
            throw new InvalidArgumentException(
                sprintf(
                    'El horario de atención "%s - %s" no es válido. La apertura debe ser anterior al cierre y ambos deben usar el formato HH:MM.',
                    $apertura,
                    $cierre,
                ),
            );
        }

        $actual = $this->horaAMinutos($horaActual);
        $inicio = $this->horaAMinutos($apertura);
        $fin = $this->horaAMinutos($cierre);

        return $actual >= $inicio && $actual <= $fin;
    }

    public function verificarSitioAbierto(SitioPatrimonial $sitio, ?string $horaActual = null): bool
    {
        return $this->evaluarEstadoOperatividad($sitio, $horaActual)->operativo;
    }

    public function evaluarEstadoOperatividad(SitioPatrimonial $sitio, ?string $horaActual = null): EstadoOperatividadDTO
    {
        if ($horaActual === null) {
            $horaActual = Carbon::now()->format('H:i');
        }

        if (! $this->esFormatoHoraValido($horaActual)) {
            throw new InvalidArgumentException('La hora de consulta ingresada no es válida o tiene un formato incorrecto.');
        }

        $apertura = $sitio->hora_apertura;
        $cierre = $sitio->hora_cierre;
        $nombreSitio = $sitio->nombre ?? 'Sitio sin nombre registrado';

        if ($apertura === null || $cierre === null || trim($apertura) === '' || trim($cierre) === '') {
            throw new InvalidArgumentException('El sitio patrimonial no tiene un horario de atención registrado completo.');
        }

        if (! $sitio->activo) {
            return new EstadoOperatividadDTO(
                sitioId: (int) $sitio->getKey(),
                nombreSitio: $nombreSitio,
                estado: 'inactivo',
                operativo: false,
                horaActual: $horaActual,
                horaApertura: $apertura,
                horaCierre: $cierre,
                mensaje: 'El sitio patrimonial se encuentra temporalmente inactivo.',
            );
        }

        if (! $this->estaOperativo($horaActual, $apertura, $cierre)) {
            return new EstadoOperatividadDTO(
                sitioId: (int) $sitio->getKey(),
                nombreSitio: $nombreSitio,
                estado: 'cerrado',
                operativo: false,
                horaActual: $horaActual,
                horaApertura: $apertura,
                horaCierre: $cierre,
                mensaje: sprintf(
                    'El sitio patrimonial está cerrado a las %s. Atiende de %s a %s.',
                    $horaActual,
                    $apertura,
                    $cierre
                ),
            );
        }

        return new EstadoOperatividadDTO(
            sitioId: (int) $sitio->getKey(),
            nombreSitio: $nombreSitio,
            estado: 'abierto',
            operativo: true,
            horaActual: $horaActual,
            horaApertura: $apertura,
            horaCierre: $cierre,
            mensaje: sprintf(
                'El sitio patrimonial está abierto a las %s. Atiende de %s a %s.',
                $horaActual,
                $apertura,
                $cierre
            ),
        );
    }

    private function horaAMinutos(string $hora): int
    {
        [$horas, $minutos] = explode(':', $hora);

        return ((int) $horas) * 60 + ((int) $minutos);
    }
}
