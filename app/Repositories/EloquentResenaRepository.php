<?php

namespace App\Repositories;

use App\Contracts\ResenaRepository;
use App\Models\Resena;

class EloquentResenaRepository implements ResenaRepository
{
    public function obtenerCalificaciones(): array
    {
        return Resena::query()->pluck('calificacion')->all();
    }

    public function obtenerCalificacionesPorSitio(int $sitioId): array
    {
        return Resena::query()->where('sitio_id', $sitioId)->pluck('calificacion')->all();
    }
}
