<?php

namespace App\Services;

use App\Contracts\SiteSensorInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpSiteSensor implements SiteSensorInterface
{
    public function getCapacidad(string $sitioId): int
    {
        return (int) $this->obtenerDatos($sitioId)['capacidad'];
    }

    public function getOcupacion(string $sitioId): int
    {
        return (int) $this->obtenerDatos($sitioId)['ocupacion'];
    }

    private function obtenerDatos(string $sitioId): array
    {
        $baseUrl = rtrim((string) env('SENSOR_URL', 'http://sensor.test'), '/');
        $respuesta = Http::get($baseUrl . '/sitios/' . $sitioId);

        if (!$respuesta->successful()) {
            throw new RuntimeException('Sensor no disponible.');
        }

        $datos = $respuesta->json();

        if (!is_array($datos) || !array_key_exists('capacidad', $datos) || !array_key_exists('ocupacion', $datos)) {
            throw new RuntimeException('Sensor no disponible.');
        }

        return $datos;
    }
}