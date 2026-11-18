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
        $this->servicio = new TransporteService();
    }

    /**
     * Propósito: Verificar que la modalidad BUS_COLECTIVO cobra la tarifa por cada pasajero.
     * Escenario: Tipo "BUS_COLECTIVO", 2 pasajeros y precio 35.0 -> costo total 70.0.
     * Importancia: Previene regresiones en la multiplicación base de toda la cotización colectiva.
     */
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

    /**
     * Propósito: Confirmar que TREN aplica la misma regla de cobro por pasajero que el bus colectivo.
     * Escenario: Tipo "TREN", 1 pasajero y precio 220.0 -> costo total 220.0.
     * Importancia: Evita que una tarifa alta se cobre por vehículo por confundir la modalidad.
     */
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

    /**
     * Propósito: Validar la excepción de negocio de CUS-04: PRIVADO es tarifa fija por vehículo.
     * Escenario: Tipo "PRIVADO", 3 pasajeros y precio 150.0 -> costo total 150.0 (sin multiplicar).
     * Importancia: Previene sobrecobros si alguien aplica la regla colectiva al servicio privado.
     */
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

    /**
     * Propósito: Comprobar la validación defensiva de pasajeros en el límite inferior exacto.
     * Escenario: 0 pasajeros -> InvalidArgumentException con "La cantidad de pasajeros debe ser mayor a cero".
     * Importancia: Evita cotizaciones en cero que derivarían en reservas o cobros inválidos.
     */
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

    /**
     * Propósito: Extender la validación de pasajeros al rango negativo, no solo al cero.
     * Escenario: TREN con -2 pasajeros -> InvalidArgumentException con el mismo mensaje exacto.
     * Importancia: Impide totales negativos y deja claro que la regla es "mayor a cero", no "distinto de cero".
     */
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

    /**
     * Propósito: Asegurar que solo se acepten las tres modalidades definidas en CUS-04.
     * Escenario: Tipo "HELICOPTERO", 1 pasajero y precio 500.0 -> InvalidArgumentException con "Tipo de transporte no reconocido".
     * Importancia: Evita que un tipo mal escrito o no soportado produzca un cobro silencioso e incorrecto.
     */
    public function test_lanza_excepcion_si_tipo_transporte_no_es_reconocido(): void
    {
        // Given: Un tipoServicio "HELICOPTERO", 1 pasajero y precioUnitario de 500
        $tipoServicio = "HELICOPTERO";
        $pasajeros = 1;
        $precioUnitario = 500.0;

        // Then: Se espera la excepción "Tipo de transporte no reconocido"
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Tipo de transporte no reconocido");

        // When: Se ejecuta la función con un servicio no registrado
        $this->servicio->calcularCostoTransporte($tipoServicio, $pasajeros, $precioUnitario);
    }

    /**
     * Propósito: Verificar precisión con tarifas decimales, como las que se usan en la operación real.
     * Escenario: BUS_COLECTIVO, 3 pasajeros y precio 35.50 -> costo total 106.50.
     * Importancia: Previene pérdidas económicas por truncamiento o redondeo prematuro del precio.
     */
    public function test_calcula_costo_con_precio_unitario_decimal(): void
    {
        $resultado = $this->servicio->calcularCostoTransporte('BUS_COLECTIVO', 3, 35.50);

        $this->assertEquals(106.50, $resultado);
    }

    /**
     * Propósito: Cubrir la validación adicional que prohíbe tarifas negativas en el servicio.
     * Escenario: TREN, 1 pasajero y precio -10.0 -> InvalidArgumentException con "El precio unitario no puede ser negativo".
     * Importancia: Evita que un dato corrupto genere créditos o montos finales negativos.
     */
    public function test_lanza_excepcion_si_precio_unitario_es_negativo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El precio unitario no puede ser negativo');

        $this->servicio->calcularCostoTransporte('TREN', 1, -10.0);
    }

    /**
     * Propósito: Documentar la normalización del tipo de servicio mediante trim().
     * Escenario: Tipo " TREN " (con espacios), 1 pasajero y precio 220.0 -> costo total 220.0.
     * Importancia: Evita rechazos falsos cuando el valor llega copiado/pegado con espacios de un formulario.
     */
    public function test_calcula_costo_cuando_el_tipo_tiene_espacios(): void
    {
        $resultado = $this->servicio->calcularCostoTransporte(' TREN ', 1, 220.0);

        $this->assertEquals(220.0, $resultado);
    }
}
