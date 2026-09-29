<?php

namespace App\Contracts;

// Define qué métodos debe tener cualquier repositorio de reseñas.
interface ResenaRepository
{
    // Obtiene las calificaciones de las reseñas.
    // El resultado siempre debe ser un arreglo.
    public function obtenerCalificaciones(): array;
}