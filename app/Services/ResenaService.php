<?php

namespace App\Services;

use App\Contracts\ResenaRepository;
use App\Models\Resena;
use InvalidArgumentException;

class ResenaService
{
    // Recibe el repositorio de reseñas como dependencia.
    public function __construct(
        private ResenaRepository $resenaRepository
    ) {
    }

    // Registra una nueva reseña validando calificación y comentario.
    public function registrarResena(int $calificacion, string $comentario, ?string $sitioId = null): Resena
    {
        if (!$this->validarCalificacion($calificacion)) {
            throw new InvalidArgumentException('La calificación debe estar entre 1 y 5.');
        }

        if (!$this->validarComentario($comentario)) {
            throw new InvalidArgumentException('El comentario no puede estar vacío.');
        }

        $datos = [
            'calificacion' => $calificacion,
            'comentario' => trim($comentario),
        ];

        if ($sitioId !== null) {
            $datos['sitio_id'] = $sitioId;
        }

        return $this->resenaRepository->guardar($datos);
    }

    // Obtiene todas las reseñas registradas a través del repositorio.
    public function obtenerResenas(?string $sitioId = null): array
    {
        return $this->resenaRepository->obtenerTodas($sitioId);
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
    public function calcularPromedioCalificaciones(?string $sitioId = null): float
    {
        // Pide las calificaciones al repositorio.
        $calificaciones = $this->resenaRepository->obtenerCalificaciones($sitioId);

        // Si no existen calificaciones, devuelve 0.0.
        if (count($calificaciones) === 0) {
            return 0.0;
        }

        // Calcula y devuelve el promedio redondeado a 1 decimal.
        return round(array_sum($calificaciones) / count($calificaciones), 1);
    }
}
