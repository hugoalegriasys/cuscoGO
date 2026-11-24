<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\SensorNoDisponibleException;

/**
 * Contrato del proveedor remoto de telemetría IoT (CUS-06).
 *
 * La interfaz devuelve únicamente un entero de ocupación ya validado, de modo
 * que el servicio de resiliencia no necesita conocer URLs, cabeceras ni el
 * formato del payload del proveedor.
 */
interface SiteSensorClientInterface
{
    /**
     * Obtiene la ocupación (0..100) de un sensor.
     *
     * @throws SensorNoDisponibleException Si el sensor no responde, falla o devuelve un valor inválido.
     */
    public function obtenerLecturaSensor(string $sensorId): int;
}
