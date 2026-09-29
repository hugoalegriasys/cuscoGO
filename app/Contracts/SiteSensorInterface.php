<?php

namespace App\Contracts;

interface SiteSensorInterface
{
    public function getCapacidad(string $sitioId): int;
    public function getOcupacion(string $sitioId): int;
}