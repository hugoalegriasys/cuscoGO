<?php

namespace App\Services;

use App\Contracts\ResenaRepository;

class ResenaService
{
    // Recibe el repositorio de reseñas como dependencia.
    public function __construct(
        private ResenaRepository $resenaRepository
    ) {
    }

    // Valida que la calificación esté entre 1 y 5.
    public function validarCalificacion(int $calificacion): bool
    {
        if ($calificacion < 1 || $calificacion > 5) {
            return false;
        }

        return true;
    }

    // Valida que el comentario no esté vacío.
    public function validarComentario(string $comentario): bool
    {
        return trim($comentario) !== '';
    }

    // Obtiene las calificaciones y calcula su promedio.
    public function calcularPromedioCalificaciones(): float
    {
        // Pide las calificaciones al repositorio.
        $calificaciones = $this->resenaRepository->obtenerCalificaciones();

        // Si no existen calificaciones, devuelve 0.0.
        if (count($calificaciones) === 0) {
            return 0.0;
        }

        // Calcula y devuelve el promedio.
        return array_sum($calificaciones) / count($calificaciones);
    }
}