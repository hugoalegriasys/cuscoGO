<?php

namespace App\Contracts;

use App\Models\Resena;

// Define qué métodos debe tener cualquier repositorio de reseñas.
interface ResenaRepository
{
    // Guarda una nueva reseña en el repositorio.
    public function guardar(array $datos): Resena;

    // Obtiene las calificaciones de las reseñas (opcionalmente filtradas por sitio).
    public function obtenerCalificaciones(?string $sitioId = null): array;

    // Obtiene todas las reseñas registradas (opcionalmente filtradas por sitio).
    public function obtenerTodas(?string $sitioId = null): array;
}
