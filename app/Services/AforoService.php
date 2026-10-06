<?php

namespace App\Services;

use App\Contracts\SiteSensorInterface;
use InvalidArgumentException;

class AforoService
{
    public function __construct(private SiteSensorInterface $sensor)
    {
    }

    /**
     * Calcula el nivel de aforo en tiempo real utilizando datos de sensores.
     */
    public function calcularNivelAforo(string $sitioId): string
    {
        $capacidad = $this->sensor->getCapacidad($sitioId);
        $ocupacion = $this->sensor->getOcupacion($sitioId);

        // Validaciones defensivas de robustez
        if ($capacidad <= 0) {
            throw new InvalidArgumentException('Error: La capacidad máxima debe ser mayor a cero.');
        }

        if ($ocupacion < 0) {
            throw new InvalidArgumentException('Error: La ocupación no puede ser negativa.');
        }

        if ($ocupacion > $capacidad) {
            throw new InvalidArgumentException('Error: La ocupación supera la capacidad máxima.');
        }

        // Calculamos el porcentaje de ocupación
        $porcentaje = ($ocupacion / $capacidad) * 100;

        // Evaluamos según los umbrales de la regla de negocio
        if ($porcentaje <= 50) {
            return 'Bajo';
        } 

        if ($porcentaje <= 80) {
            return 'Moderado';
        } 

        return 'Alto';
    }
}