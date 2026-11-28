<?php

namespace App\Repositories;

use App\Contracts\ResenaRepository;
use App\Models\Resena;

class EloquentResenaRepository implements ResenaRepository
{
    /**
     * Guarda una nueva reseña en la base de datos.
     */
    public function guardar(array $datos): Resena
    {
        return Resena::create($datos);
    }

    /**
     * Obtiene únicamente el listado de calificaciones numéricas.
     */
    public function obtenerCalificaciones(?string $sitioId = null): array
    {
        $query = Resena::query();
        if ($sitioId !== null) {
            $query->where('sitio_id', $sitioId);
        }
        return $query->pluck('calificacion')->map(fn($c) => (int) $c)->toArray();
    }

    /**
     * Obtiene todas las reseñas registradas en la base de datos.
     */
    public function obtenerTodas(?string $sitioId = null): array
    {
        $query = Resena::query()->latest();
        if ($sitioId !== null) {
            $query->where('sitio_id', $sitioId);
        }
        return $query->get()->toArray();
    }
}
