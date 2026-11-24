<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\TelemetriaAforoDTO;
use App\Exceptions\SensorNoDisponibleException;
use App\Models\Sitio;

/**
 * Contrato del servicio de telemetría con caché y respaldo histórico (CUS-06).
 */
interface TelemetriaAforoServiceInterface
{
    /**
     * Devuelve el aforo vigente o, si el sensor falla, la última lectura guardada.
     *
     * @throws SensorNoDisponibleException Si el sensor falla y no existe ninguna lectura previa.
     */
    public function obtenerAforoConFallback(Sitio $sitio): TelemetriaAforoDTO;
}
