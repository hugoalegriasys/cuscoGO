<?php

namespace Tests\Feature;

use App\Contracts\SiteSensorInterface;
use App\Services\AforoService;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AforoModelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public static function aforoCasosProvider(): array
    {
        return [
            'Ocupación 30%' => [100, 30, 'Bajo'],
            'Ocupación 50% (Límite Bajo)' => [100, 50, 'Bajo'],
            'Ocupación 60%' => [100, 60, 'Moderado'],
            'Ocupación 80% (Límite Moderado)' => [100, 80, 'Moderado'],
            'Ocupación 90%' => [100, 90, 'Alto'],
            'Ocupación 100% (Capacidad llena)' => [100, 100, 'Alto'],
        ];
    }

    #[DataProvider('aforoCasosProvider')]
    public function test_calcular_nivel_aforo_retorna_estado_correcto_mediante_ioc(int $capacidad, int $ocupacion, string $resultadoEsperado): void
    {
        // Given: Inyección del Mock en el contenedor IoC de Laravel
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->once()->with('machu-picchu')->andReturn($capacidad);
        $sensorMock->shouldReceive('getOcupacion')->once()->with('machu-picchu')->andReturn($ocupacion);

        $this->instance(SiteSensorInterface::class, $sensorMock);

        // When: Resolución del servicio mediante el Service Container
        $aforoService = $this->app->make(AforoService::class);
        $resultadoActual = $aforoService->calcularNivelAforo('machu-picchu');

        // Then: Validación del resultado integrado
        $this->assertEquals($resultadoEsperado, $resultadoActual);
    }

    public function test_calcular_nivel_aforo_lanza_error_por_sobrecapacidad(): void
    {
        // Given: Mock con sobrecapacidad registrado en el contenedor IoC
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(100);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(120);

        $this->instance(SiteSensorInterface::class, $sensorMock);
        $aforoService = $this->app->make(AforoService::class);

        // Then: Se espera excepción capturada por la arquitectura
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error: La ocupación supera la capacidad máxima.');

        // When: Ejecución del servicio resuelto por IoC
        $aforoService->calcularNivelAforo('Qoricancha');
    }

    public function test_lanza_excepcion_si_ocupacion_es_negativa(): void
    {
        // Given: Mock con valor negativo registrado en el contenedor IoC
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(100);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(-5);

        $this->instance(SiteSensorInterface::class, $sensorMock);
        $aforoService = $this->app->make(AforoService::class);

        // Then: Se espera excepción por ocupación negativa
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error: La ocupación no puede ser negativa.');

        // When: Ejecución del servicio resuelto por IoC
        $aforoService->calcularNivelAforo('machu-picchu');
    }

    public function test_lanza_excepcion_si_capacidad_es_invalida(): void
    {
        // Given: Mock con capacidad cero registrado en el contenedor IoC
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(0);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(10);

        $this->instance(SiteSensorInterface::class, $sensorMock);
        $aforoService = $this->app->make(AforoService::class);

        // Then: Se espera excepción por capacidad en cero
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error: La capacidad máxima debe ser mayor a cero.');

        // When: Ejecución del servicio resuelto por IoC
        $aforoService->calcularNivelAforo('machu-picchu');
    }
}