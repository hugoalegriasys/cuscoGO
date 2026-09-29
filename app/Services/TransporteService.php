<?php

namespace App\Services;

use InvalidArgumentException;

class TransporteService
{
    /**
     * Lista de tipos de servicio de transporte reconocidos.
     */
    private array $tiposServicioValidos = [
        'BUS_COLECTIVO',
        'TREN',
        'PRIVADO',
    ];

    /**
     * Calcula el costo total del servicio de transporte turístico.
     *
     * @param string $tipoServicio Tipo de servicio (BUS_COLECTIVO, TREN, PRIVADO)
     * @param int $pasajeros Cantidad de pasajeros
     * @param float $precioUnitario Precio por persona o por vehículo
     * @return float Costo total del transporte
     * 
     * @throws InvalidArgumentException Si alguna validación falla
     */
    public function calcularCostoTransporte(string $tipoServicio, int $pasajeros, float $precioUnitario): float
    {
        $tipoLimpio = trim($tipoServicio);

        // 1. Validar que la cantidad de pasajeros sea mayor a cero
        if ($pasajeros <= 0) {
            throw new InvalidArgumentException('La cantidad de pasajeros debe ser mayor a cero');
        }

        // 2. Validar que el tipo de transporte sea reconocido
        if (!in_array($tipoLimpio, $this->tiposServicioValidos, true)) {
            throw new InvalidArgumentException('Tipo de transporte no reconocido');
        }

        // 3. Aplicar tarifa fija por vehículo si es servicio PRIVADO
        if ($tipoLimpio === 'PRIVADO') {
            return $precioUnitario;
        }

        // 4. Calcular costo por pasajero para servicios colectivos y tren
        return $pasajeros * $precioUnitario;
    }
}