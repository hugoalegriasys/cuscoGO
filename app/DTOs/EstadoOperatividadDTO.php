<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Resultado inmutable de la evaluación de estado de operatividad
 * de un sitio patrimonial (CUS-03).
 */
final readonly class EstadoOperatividadDTO
{
    public function __construct(
        public int $sitioId,
        public string $nombreSitio,
        public string $estado,
        public bool $operativo,
        public string $horaActual,
        public string $horaApertura,
        public string $horaCierre,
        public string $mensaje,
    ) {}

    /**
     * Convierte el DTO a un arreglo asociativo para respuestas JSON.
     */
    public function toArray(): array
    {
        return [
            'sitio_id' => $this->sitioId,
            'nombre_sitio' => $this->nombreSitio,
            'estado' => $this->estado,
            'operativo' => $this->operativo,
            'hora_actual' => $this->horaActual,
            'hora_apertura' => $this->horaApertura,
            'hora_cierre' => $this->horaCierre,
            'mensaje' => $this->mensaje,
        ];
    }
}
