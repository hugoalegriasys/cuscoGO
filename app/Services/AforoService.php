<?php

namespace App\Services;

use App\Contracts\SiteSensorInterface;
use InvalidArgumentException;

class AforoService
{
    public function __construct(private SiteSensorInterface $sensor)
    {
    }

     //Calcula el nivel de aforo en tiempo real utilizando datos de sensores.
    public function calcularNivelAforo(string $sitioId): string
    {
        // Obtenemos los datos a través de nuestra dependencia inyectada (el mock en las pruebas)
        $capacidad = $this->sensor->getCapacidad($sitioId);
        $ocupacion = $this->sensor->getOcupacion($sitioId);

        // Validamos el escenario de error de la tabla
        if ($ocupacion > $capacidad) {
            throw new InvalidArgumentException('Error: La ocupación supera la capacidad máxima.');
        }

        // Calculamos el porcentaje de ocupación
        $porcentaje = ($ocupacion / $capacidad) * 100;

        // Evaluamos según los umbrales deducidos de la historia de usuario
        if ($porcentaje <= 50) {
            return 'Bajo';
        } 
        
        if ($porcentaje <= 80) {
            return 'Moderado';
        } 
        
        return 'Alto';
    }
}