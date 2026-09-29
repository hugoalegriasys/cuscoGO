<?php

namespace App\Services;

use InvalidArgumentException;

class DisponibilidadService
{
    private function esFormatoHoraValido(string $hora): bool
    {
        // Valida que el formato sea estrictamente HH:MM entre 00:00 y 23:59 (rechaza negativos)
        return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $hora);
    }

    public function esHorarioValido(string $apertura, string $cierre): bool
    {
        if (!$this->esFormatoHoraValido($apertura) || !$this->esFormatoHoraValido($cierre)) {
            return false;
        }

        $inicio = strtotime($apertura);
        $fin = strtotime($cierre);

        return ($inicio !== false && $fin !== false && $inicio < $fin);
    }

    public function estaOperativo(string $horaActual, string $apertura, string $cierre): bool
    {
        if (!$this->esFormatoHoraValido($horaActual)) {
            throw new InvalidArgumentException("La hora actual ingresada no es válida o tiene un formato incorrecto.");
        }

        if (!$this->esHorarioValido($apertura, $cierre)) {
            throw new InvalidArgumentException("El horario de atención especificado no es válido.");
        }

        $actual = strtotime($horaActual);
        $inicio = strtotime($apertura);
        $fin = strtotime($cierre);

        return ($actual >= $inicio && $actual <= $fin);
    }
}