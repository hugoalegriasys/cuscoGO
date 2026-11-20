<?php

namespace App\Services;

use App\Contracts\SiteSensorInterface;

class SimulatedSiteSensor implements SiteSensorInterface
{
    /** @var array<string, array{capacidad:int, ocupacion:int}> */
    private array $datos = [
        'plaza-de-armas' => ['capacidad' => 100, 'ocupacion' => 30],
        'qorikancha' => ['capacidad' => 100, 'ocupacion' => 40],
        'catedral-de-cusco' => ['capacidad' => 100, 'ocupacion' => 60],
        'sacsayhuaman' => ['capacidad' => 100, 'ocupacion' => 75],
        'tambomachay' => ['capacidad' => 100, 'ocupacion' => 90],
        'puka-pukara' => ['capacidad' => 100, 'ocupacion' => 20],
        'qenqo' => ['capacidad' => 100, 'ocupacion' => 55],
        'museo-inka' => ['capacidad' => 100, 'ocupacion' => 70],
        'mercado-san-pedro' => ['capacidad' => 100, 'ocupacion' => 85],
    ];

    public function getCapacidad(string $sitioId): int
    {
        return $this->obtenerSitio($sitioId)['capacidad'];
    }

    public function getOcupacion(string $sitioId): int
    {
        return $this->obtenerSitio($sitioId)['ocupacion'];
    }

    private function obtenerSitio(string $sitioId): array
    {
        return $this->datos[$sitioId] ?? ['capacidad' => 100, 'ocupacion' => 50];
    }
}