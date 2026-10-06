<?php

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Services\DisponibilidadService;
use InvalidArgumentException;

class DisponibilidadFeatureTest extends TestCase
{
    private DisponibilidadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Given: Se resuelve el servicio desde el Service Container de Laravel
        $this->service = $this->app->make(DisponibilidadService::class);
    }

    #[Test]
    public function integra_servicio_de_disponibilidad_desde_el_contenedor_de_laravel(): void
    {
        // Given: Un horario de atención válido y una hora actual dentro del rango
        $horaActual = '10:00';
        $apertura = '08:00';
        $cierre = '17:00';

        // When: Se evalúa la operatividad mediante el servicio resuelto por Laravel
        $resultado = $this->service->estaOperativo($horaActual, $apertura, $cierre);

        // Then: El servicio debe ser la instancia correcta y confirmar que el sitio está operativo
        $this->assertInstanceOf(DisponibilidadService::class, $this->service);
        $this->assertTrue($resultado);
    }

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