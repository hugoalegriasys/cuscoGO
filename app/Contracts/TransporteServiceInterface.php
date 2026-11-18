<?php

declare(strict_types=1);

namespace App\Contracts;

interface TransporteServiceInterface
{
    /**
     * Calcula el costo total del servicio de transporte turístico.
     *
     * @throws \InvalidArgumentException Si alguna validación de negocio falla.
     */
    public function calcularCostoTransporte(string $tipoServicio, int $pasajeros, float $precioUnitario): float;

    /**
     * Devuelve las rutas turísticas de referencia para precargar la interfaz web.
     *
     * @return array<int, array{nombre: string, descripcion: string, tipo_servicio: string, precio_unitario: float}>
     */
    public function obtenerServiciosReferencia(): array;
}
