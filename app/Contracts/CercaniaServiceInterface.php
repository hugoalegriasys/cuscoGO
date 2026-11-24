<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Sitio;
use Illuminate\Support\Collection;

interface CercaniaServiceInterface
{
    /**
     * Valida que una pareja de coordenadas esté dentro de los rangos geográficos.
     */
    public function validarCoordenadas(float $latitud, float $longitud): bool;

    /**
     * Calcula la distancia esférica entre dos coordenadas con Haversine.
     *
     * @throws \InvalidArgumentException Si alguna pareja de coordenadas no es válida.
     */
    public function calcularDistanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float;

    /**
     * Devuelve los sitios activos dentro del radio, ordenados por cercanía.
     *
     * Cada sitio expone el atributo calculado "distancia_km" (redondeado a 2 decimales).
     *
     * @return Collection<int, Sitio>
     *
     * @throws \InvalidArgumentException Si las coordenadas o el radio no son válidos.
     */
    public function buscarSitiosCercanos(float $latitud, float $longitud, float $radioKm = 5.0): Collection;
}
