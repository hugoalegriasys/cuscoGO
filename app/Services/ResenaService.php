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

    // Valida que la calificación sea un entero entre 1 y 5.
    public function validarCalificacion(mixed $calificacion): bool
    {
        if (!is_int($calificacion) || $calificacion < 1 || $calificacion > 5) {
            return false;
        }

        return true;
    }

    // Valida que el comentario no esté vacío y no supere 500 caracteres.
    public function validarComentario(string $comentario): bool
    {
        $comentarioLimpio = trim($comentario);

        if ($comentarioLimpio === '') {
            return false;
        }

        return mb_strlen($comentarioLimpio) <= 500;
    }

    // Obtiene las calificaciones y calcula su promedio.
    public function calcularPromedioCalificaciones(): float
    {
        $calificaciones = $this->resenaRepository->obtenerCalificaciones();

        return $this->calcularPromedioDesdeArreglo($calificaciones);
    }

    public function calcularPromedioCalificacionesPorSitio(int $sitioId): float
    {
        $calificaciones = $this->resenaRepository->obtenerCalificacionesPorSitio($sitioId);

        return $this->calcularPromedioDesdeArreglo($calificaciones);
    }

    private function calcularPromedioDesdeArreglo(array $calificaciones): float
    {
        if (count($calificaciones) === 0) {
            return 0.0;
        }

        $promedio = array_sum($calificaciones) / count($calificaciones);

        return round($promedio, 1);
    }
}