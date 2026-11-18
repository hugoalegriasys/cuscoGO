<?php

/**
 * Capa de pruebas Feature de integración de CUS-03 con el contenedor de Laravel.
 * Verifica que DisponibilidadServiceInterface se resuelva correctamente y que el servicio
 * aplique las mismas reglas de operatividad y excepciones al usar la app real, sin navegador.
 * Previene fallos de configuración de bindings que las pruebas unitarias no detectan.
 */

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Contracts\DisponibilidadServiceInterface;
use App\Services\DisponibilidadService;
use InvalidArgumentException;

class DisponibilidadModelTest extends TestCase
{
    use RefreshDatabase;

    private DisponibilidadServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Given: Se resuelve el servicio utilizando la Interfaz / Contrato desde el Container
        $this->service = $this->app->make(DisponibilidadServiceInterface::class);
    }

    /**
     * Propósito: Verificar que Laravel resuelva el contrato con la implementación correcta.
     * Escenario: app()->make(DisponibilidadServiceInterface) con 10:00/08:00-17:00 -> DisponibilidadService y true.
     * Importancia: Previene fallos de binding en producción que las pruebas unitarias no detectan.
     */
    #[Test]
    public function integra_servicio_de_disponibilidad_desde_el_contenedor_de_laravel(): void
    {
        // Given: Un horario de atención válido y una hora actual dentro del rango
        $horaActual = '10:00';
        $apertura = '08:00';
        $cierre = '17:00';

        // When: Se evalúa la operatividad mediante el servicio resuelto por Laravel
        $resultado = $this->service->estaOperativo($horaActual, $apertura, $cierre);

        // Then: El servicio debe ser la instancia del contrato y confirmar que está operativo
        $this->assertInstanceOf(DisponibilidadService::class, $this->service);
        $this->assertTrue($resultado);
    }

    /**
     * Propósito: Confirmar que el servicio inyectado por el container mantenga sus validaciones.
     * Escenario: hora '-05:00' -> InvalidArgumentException.
     * Importancia: Evita que una mala configuración del contenedor permita evaluaciones inválidas.
     */
    #[Test]
    public function rechaza_horarios_invalidos_en_el_flujo_de_integracion(): void
    {
        // Given: Un formato de hora actual inválido (valor negativo)
        $horaInvalida = '-05:00';
        $apertura = '08:00';
        $cierre = '17:00';

        // Then: Se espera que el flujo lance una excepción de argumento inválido
        $this->expectException(InvalidArgumentException::class);

        // When: Se intenta evaluar la operatividad con el dato erróneo
        $this->service->estaOperativo($horaInvalida, $apertura, $cierre);
    }
}