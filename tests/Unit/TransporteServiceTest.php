<?php

/**
 * Capa de pruebas unitarias de CUS-04.
 * Valida únicamente la lógica pura de TransporteService: cálculo por modalidad,
 * validaciones de negocio y mensajes exactos de excepción, sin HTTP, base de datos ni navegador.
 * Es el primer lugar que debe avisar al equipo si una regla de cotización cambia por error.
 */

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
        $this->servicio = new TransporteService;
    }

    /**
     * Given: Tipo "BUS_COLECTIVO", 2 pasajeros y precio 35.0.
     * When: Se calcula el costo del transporte con esos datos.
     * Then: El costo total es 70.0, porque se cobra 35.0 por pasajero.
     * Importancia: Previene regresiones en la multiplicación base de toda la cotización colectiva.
     */
    public function test_calcula_costo_bus_colectivo(): void
    {
        // Given: Un tipoServicio "BUS_COLECTIVO", 2 pasajeros y precioUnitario de 35
        $tipoServicio = 'BUS_COLECTIVO';
        $pasajeros = 2;
        $precioUnitario = 35.0;

        // When: Se calcula el costo del transporte
        $resultado = $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);

        // Then: El costo esperado debe ser 70
        $this->assertEquals(70.0, $resultado);
    }

    /**
     * Given: Tipo "TREN", 1 pasajero y precio 220.0.
     * When: Se calcula el costo del transporte con esos datos.
     * Then: El costo total es 220.0, aplicando la misma regla por pasajero que el bus colectivo.
     * Importancia: Evita que una tarifa alta se cobre por vehículo por confundir la modalidad.
     */
    public function test_calcula_costo_tren(): void
    {
        // Given: Un tipoServicio "TREN", 1 pasajero y precioUnitario de 220
        $tipoServicio = 'TREN';
        $pasajeros = 1;
        $precioUnitario = 220.0;

        // When: Se calcula el costo del transporte
        $resultado = $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);

        // Then: El costo esperado debe ser 220
        $this->assertEquals(220.0, $resultado);
    }

    /**
     * Given: Tipo "PRIVADO", 3 pasajeros y precio 150.0 como tarifa fija por vehículo.
     * When: Se calcula el costo del transporte con esos datos.
     * Then: El costo total es 150.0, sin multiplicar el precio por los 3 pasajeros.
     * Importancia: Previene sobrecobros si alguien aplica la regla colectiva al servicio privado.
     */
    public function test_calcula_costo_servicio_privado(): void
    {
        // Given: Un tipoServicio "PRIVADO", 3 pasajeros y precioUnitario (fijo por vehículo) de 150
        $tipoServicio = 'PRIVADO';
        $pasajeros = 3;
        $precioUnitario = 150.0;

        // When: Se calcula el costo del transporte
        $resultado = $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);

        // Then: El costo esperado debe ser 150 (tarifa fija por vehículo)
        $this->assertEquals(150.0, $resultado);
    }

    /**
     * Given: Tipo "BUS_COLECTIVO", 0 pasajeros y precio 35.0.
     * When: Se calcula el costo del transporte con 0 pasajeros.
     * Then: Se lanza InvalidArgumentException con "La cantidad de pasajeros debe ser mayor a cero".
     * Importancia: Evita cotizaciones en cero que derivarían en reservas o cobros inválidos.
     */
    public function test_lanza_excepcion_si_pasajeros_es_cero(): void
    {
        // Given: Un tipoServicio "BUS_COLECTIVO", 0 pasajeros y precioUnitario de 35
        $tipoServicio = 'BUS_COLECTIVO';
        $pasajeros = 0;
        $precioUnitario = 35.0;

        // Then: Se espera la excepción "La cantidad de pasajeros debe ser mayor a cero"
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La cantidad de pasajeros debe ser mayor a cero');

        // When: Se ejecuta la función con 0 pasajeros
        $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);
    }

    /**
     * Given: Tipo "TREN", -2 pasajeros y precio 220.0.
     * When: Se calcula el costo del transporte con pasajeros negativos.
     * Then: Se lanza InvalidArgumentException con el mismo mensaje exacto "La cantidad de pasajeros debe ser mayor a cero".
     * Importancia: Impide totales negativos y deja claro que la regla es "mayor a cero", no "distinto de cero".
     */
    public function test_lanza_excepcion_si_pasajeros_es_negativo(): void
    {
        // Given: Un tipoServicio "TREN", pasajeros en -2 y precioUnitario de 220
        $tipoServicio = 'TREN';
        $pasajeros = -2;
        $precioUnitario = 220.0;

        // Then: Se espera la excepción "La cantidad de pasajeros debe ser mayor a cero"
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La cantidad de pasajeros debe ser mayor a cero');

        // When: Se ejecuta la función con pasajeros negativos
        $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);
    }

    /**
     * Given: Tipo "HELICOPTERO", 1 pasajero y precio 500.0.
     * When: Se calcula el costo del transporte con un tipo no reconocido.
     * Then: Se lanza InvalidArgumentException con "Tipo de transporte no reconocido".
     * Importancia: Evita que un tipo mal escrito o no soportado produzca un cobro silencioso e incorrecto.
     */
    public function test_lanza_excepcion_si_tipo_transporte_no_es_reconocido(): void
    {
        // Given: Un tipoServicio "HELICOPTERO", 1 pasajero y precioUnitario de 500
        $tipoServicio = 'HELICOPTERO';
        $pasajeros = 1;
        $precioUnitario = 500.0;

        // Then: Se espera la excepción "Tipo de transporte no reconocido"
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo de transporte no reconocido');

        // When: Se ejecuta la función con un servicio no registrado
        $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);
    }

    /**
     * Given: BUS_COLECTIVO, 3 pasajeros y precio 35.50.
     * When: Se calcula el costo del transporte con esos datos.
     * Then: El costo total es 106.50, sin truncamiento ni redondeo prematuro.
     * Importancia: Previene pérdidas económicas por truncamiento o redondeo prematuro del precio.
     */
    public function test_calcula_costo_con_precio_unitario_decimal(): void
    {
        // Given: BUS_COLECTIVO con 3 pasajeros y precio unitario de 35.50.
        // When: Se calcula el costo del transporte con esos datos.
        $resultado = $this->servicio->calcularCostoTransporte('BUS_COLECTIVO', 3, 35.50);

        // Then: El costo esperado debe ser 106.50.
        $this->assertEquals(106.50, $resultado);
    }

    /**
     * Given: TREN, 1 pasajero y precio -10.0.
     * When: Se calcula el costo del transporte con un precio negativo.
     * Then: Se lanza InvalidArgumentException con "El precio unitario no puede ser negativo".
     * Importancia: Evita que un dato corrupto genere créditos o montos finales negativos.
     */
    public function test_lanza_excepcion_si_precio_unitario_es_negativo(): void
    {
        // Given: TREN con 1 pasajero y precio unitario de -10.0.
        // Then: Se espera la excepción "El precio unitario no puede ser negativo".
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El precio unitario no puede ser negativo');

        // When: Se calcula el costo del transporte con el precio negativo.
        $this->servicio->calcularCostoTransporte('TREN', 1, -10.0);
    }

    /**
     * Given: Tipo " TREN " (con espacios), 1 pasajero y precio 220.0.
     * When: Se calcula el costo del transporte con ese tipo escrito con espacios.
     * Then: El tipo se normaliza con trim() y el costo total es 220.0.
     * Importancia: Evita rechazos falsos cuando el valor llega copiado/pegado con espacios de un formulario.
     */
    public function test_calcula_costo_cuando_el_tipo_tiene_espacios(): void
    {
        // Given: El tipo " TREN " llega con espacios, con 1 pasajero y precio 220.0.
        // When: Se calcula el costo del transporte con ese tipo.
        $resultado = $this->servicio->calcularCostoTransporte(' TREN ', 1, 220.0);

        // Then: El costo esperado debe ser 220.0 tras normalizar el tipo.
        $this->assertEquals(220.0, $resultado);
    }
}
