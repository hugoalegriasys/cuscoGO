<?php

declare(strict_types=1);

namespace App\Contracts;

interface DisponibilidadServiceInterface
{
    /**
     * Evalúa si una hora cumple el formato de 24 horas HH:MM.
     */
    public function esFormatoHoraValido(string $hora): bool;

    /**
     * Evalúa si un horario de atención (apertura y cierre) es coherente.
     */
    public function esHorarioValido(string $apertura, string $cierre): bool;

    /**
     * Evalúa si el sitio se encuentra operativo en la hora actual.
     *
     * @throws \InvalidArgumentException Si la hora actual o el horario no son válidos.
     */
    public function estaOperativo(string $horaActual, string $apertura, string $cierre): bool;
}
