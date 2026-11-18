<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DisponibilidadServiceInterface;
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

    private function horaAMinutos(string $hora): int
    {
        [$horas, $minutos] = explode(':', $hora);

        return ((int) $horas) * 60 + ((int) $minutos);
    }
}
