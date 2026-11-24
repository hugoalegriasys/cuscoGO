<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SiteSensorClientInterface;
use App\Exceptions\SensorNoDisponibleException;

/**
 * Cliente de demostración para el panel web de CUS-06.
 *
 * No sustituye al cliente HTTP real: solo permite probar el flujo en local
 * sin depender de que exista un proveedor IoT accesible. La API siempre usa
 * HttpSiteSensorClient; la vista puede elegir este cliente mediante el
 * parámetro modo=vivo o modo=fallback.
 */
class SensorDemoClient implements SiteSensorClientInterface
{
    public function __construct(
        private readonly bool $simularCaida,
    ) {}

    /**
     * Devuelve 85 cuando el sensor "está activo" o lanza el error de dominio
     * cuando se simula la caída, para que el servicio active el respaldo.
     *
     * @throws SensorNoDisponibleException Si se solicitó el modo de resiliencia.
     */
    public function obtenerLecturaSensor(string $sensorId): int
    {
        if ($this->simularCaida) {
            throw new SensorNoDisponibleException('Sensor sin señal · Mostrando última lectura registrada');
        }

        // Valor fijo y documentado: hace que la demo y Cypress sean deterministas.
        return 85;
    }
}
