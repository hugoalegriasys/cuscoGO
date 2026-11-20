<?php

namespace App\Services;

use App\Contracts\SiteSensorInterface;
use InvalidArgumentException;

class AforoService
{
    public function __construct(private SiteSensorInterface $sensor)
    {
    }

    public function calcularNivelAforo(string $sitioId): string
    {
        $capacidad = $this->sensor->getCapacidad($sitioId);
        $ocupacion = $this->sensor->getOcupacion($sitioId);

        if ($capacidad <= 0) {
            throw new InvalidArgumentException('La capacidad debe ser mayor a cero.');
        }

        if ($ocupacion > $capacidad) {
            throw new InvalidArgumentException('La ocupación supera la capacidad máxima.');
        }

        $porcentaje = ($ocupacion / $capacidad) * 100;

        if ($porcentaje <= 40) {
            return 'Bajo';
        }

        if ($porcentaje <= 75) {
            return 'Moderado';
        }

        return 'Alto';
    }
}