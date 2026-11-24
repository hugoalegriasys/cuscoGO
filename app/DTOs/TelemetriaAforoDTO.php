<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Resultado inmutable de una consulta de telemetría de aforo (CUS-06).
 *
 * El flag stale viaja con el DTO para que la capa HTTP y la vista no tengan
 * que reconstruir el estado a partir del origen: cuando es true el valor es
 * histórico y debe comunicarse al usuario como dato degradado.
 */
final readonly class TelemetriaAforoDTO
{
    /**
     * @param  string  $origen  Una de: iot_vivo | cache | fallback_bd.
     */
    public function __construct(
        public int $sitioId,
        public string $sitioNombre,
        public string $sensorId,
        public int $ocupacion,
        public bool $stale,
        public string $origen,
        public string $actualizadoEn,
    ) {}

    /**
     * Convierte el DTO a un arreglo asociativo para respuestas JSON y Blade.
     *
     * @return array<string, int|string|bool>
     */
    public function toArray(): array
    {
        return [
            'sitio_id' => $this->sitioId,
            'sitio_nombre' => $this->sitioNombre,
            'sensor_id' => $this->sensorId,
            'ocupacion' => $this->ocupacion,
            'stale' => $this->stale,
            'origen' => $this->origen,
            'actualizado_en' => $this->actualizadoEn,
        ];
    }
}
