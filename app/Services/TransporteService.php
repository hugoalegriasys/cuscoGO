<?php

namespace App\Services;

use App\Contracts\TransporteServiceInterface;
use InvalidArgumentException;

class TransporteService implements TransporteServiceInterface
{
    public const BUS_COLECTIVO = 'BUS_COLECTIVO';

    public const TREN = 'TREN';

    public const PRIVADO = 'PRIVADO';

    /**
     * Lista de tipos de servicio de transporte reconocidos.
     */
    private array $tiposServicioValidos = [
        self::BUS_COLECTIVO,
        self::TREN,
        self::PRIVADO,
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

        // 3. Validar que el precio unitario no sea negativo
        if ($precioUnitario < 0) {
            throw new InvalidArgumentException('El precio unitario no puede ser negativo');
        }

        // 4. Aplicar tarifa fija por vehículo si es servicio PRIVADO
        if ($tipoLimpio === self::PRIVADO) {
            return $precioUnitario;
        }

        // 5. Calcular costo por pasajero para servicios colectivos y tren
        return $pasajeros * $precioUnitario;
    }

    /**
     * Devuelve rutas turísticas reales de Cusco con su modalidad y tarifa de referencia.
     *
     * @return array<int, array{nombre: string, descripcion: string, tipo_servicio: string, precio_unitario: float}>
     */
    public function obtenerServiciosReferencia(): array
    {
        return [
            [
                'nombre' => 'Bus Colectivo Cusco – Ollantaytambo',
                'descripcion' => 'Salida diaria desde el terminal terrestre de Cusco hacia el Valle Sagrado.',
                'tipo_servicio' => self::BUS_COLECTIVO,
                'precio_unitario' => 35.0,
            ],
            [
                'nombre' => 'Tren Turístico Ollantaytambo – Machu Picchu',
                'descripcion' => 'Tren turístico desde Ollantaytambo hasta la estación de Aguas Calientes.',
                'tipo_servicio' => self::TREN,
                'precio_unitario' => 220.0,
            ],
            [
                'nombre' => 'Transporte Privado Valle Sagrado',
                'descripcion' => 'Vehículo privado para recorrer Pisac, Ollantaytambo y Chinchero.',
                'tipo_servicio' => self::PRIVADO,
                'precio_unitario' => 150.0,
            ],
        ];
    }
}