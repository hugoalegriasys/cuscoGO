<?php

namespace Tests\Unit;

use App\Services\TransporteService;
use InvalidArgumentException;
use Tests\TestCase;

class TransporteServiceTest extends TestCase
{
    private TransporteService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new TransporteService();
    }

    public function test_calcula_costo_bus_colectivo(): void
    {
        // Given: Un tipoServicio "BUS_COLECTIVO", 2 pasajeros y precioUnitario de 35
        $tipoServicio = "BUS_COLECTIVO";
        $pasajeros = 2;
        $precioUnitario = 35.0;

        // When: Se calcula el costo del transporte
        $resultado = $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);

        // Then: El costo esperado debe ser 70
        $this->assertEquals(70.0, $resultado);
    }

    public function test_calcula_costo_tren(): void
    {
        // Given: Un tipoServicio "TREN", 1 pasajero y precioUnitario de 220
        $tipoServicio = "TREN";
        $pasajeros = 1;
        $precioUnitario = 220.0;

        // When: Se calcula el costo del transporte
        $resultado = $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);

        // Then: El costo esperado debe ser 220
        $this->assertEquals(220.0, $resultado);
    }

    public function test_calcula_costo_servicio_privado(): void
    {
        // Given: Un tipoServicio "PRIVADO", 3 pasajeros y precioUnitario (fijo por vehículo) de 150
        $tipoServicio = "PRIVADO";
        $pasajeros = 3;
        $precioUnitario = 150.0;

        // When: Se calcula el costo del transporte
        $resultado = $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);

        // Then: El costo esperado debe ser 150 (tarifa fija por vehículo)
        $this->assertEquals(150.0, $resultado);
    }

    public function test_lanza_excepcion_si_pasajeros_es_cero(): void
    {
        // Given: Un tipoServicio "BUS_COLECTIVO", 0 pasajeros y precioUnitario de 35
        $tipoServicio = "BUS_COLECTIVO";
        $pasajeros = 0;
        $precioUnitario = 35.0;

        // Then: Se espera la excepción "La cantidad de pasajeros debe ser mayor a cero"
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("La cantidad de pasajeros debe ser mayor a cero");

        // When: Se ejecuta la función con 0 pasajeros
        $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);
    }

    public function test_lanza_excepcion_si_pasajeros_es_negativo(): void
    {
        // Given: Un tipoServicio "TREN", pasajeros en -2 y precioUnitario de 220
        $tipoServicio = "TREN";
        $pasajeros = -2;
        $precioUnitario = 220.0;

        // Then: Se espera la excepción "La cantidad de pasajeros debe ser mayor a cero"
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("La cantidad de pasajeros debe ser mayor a cero");

        // When: Se ejecuta la función con pasajeros negativos
        $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);
    }

    public function test_lanza_excepcion_si_tipo_transporte_no_es_reconocido(): void
    {
        // Given: Un tipoServicio "HELICOPTERO", 2 pasajeros y precioUnitario de 500
        $tipoServicio = "HELICOPTERO";
        $pasajeros = 1;
        $precioUnitario = 500.0;

        // Then: Se espera la excepción "Tipo de transporte no reconocido"
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Tipo de transporte no reconocido");

        // When: Se ejecuta la función con un servicio no registrado
        $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);
    }
}