<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\EstadoOperatividadDTO;
use App\Models\SitioPatrimonial;

interface DisponibilidadServiceInterface
{
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

    /**
     * Verifica si un sitio patrimonial está abierto según su horario de atención.
     *
     * @param  string|null  $horaActual  Hora de consulta en formato HH:MM; si es null se usa la hora actual del sistema.
     *
     * @throws \InvalidArgumentException Si la hora de consulta o el horario del sitio no son válidos.
     */
    public function verificarSitioAbierto(SitioPatrimonial $sitio, ?string $horaActual = null): bool;

    /**
     * Calcula el estado de operatividad de un sitio patrimonial según la hora actual.
     *
     * @param  string|null  $horaActual  Hora de consulta en formato HH:MM; si es null se usa la hora actual del sistema.
     *
     * @throws \InvalidArgumentException Si la hora de consulta o el horario del sitio no son válidos.
     */
    public function evaluarEstadoOperatividad(SitioPatrimonial $sitio, ?string $horaActual = null): EstadoOperatividadDTO;
}
