<?php

namespace Tests\Feature;

use App\Contracts\SiteSensorInterface;
use App\Services\AforoService;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase; // Importante: TestCase del framework Laravel

class AforoServiceTest extends TestCase
{
    // Limpieza de Mockery al finalizar cada prueba
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public static function aforoCasosProvider(): array
    {
        return [
            'Ocupacion 30%' => [100, 30, 'Bajo'],
            'Ocupacion 50% (Límite Bajo)' => [100, 50, 'Bajo'],
            'Ocupacion 60%' => [100, 60, 'Moderado'],
            'Ocupacion 80% (Límite Moderado)' => [100, 80, 'Moderado'],
            'Ocupacion 90%' => [100, 90, 'Alto'],
            'Ocupacion 100% (Capacidad llena)' => [100, 100, 'Alto'],
        ];
    }

    #[DataProvider('aforoCasosProvider')]
    public function test_calcular_nivel_aforo_retorna_estado_correcto_mediante_ioc(int $capacidad, int $ocupacion, string $resultadoEsperado): void
    {
        // GIVEN: Preparamos el doble de prueba (Mock) del sensor
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        
        $sensorMock->shouldReceive('getCapacidad')
                   ->once()
                   ->with('machu-picchu')
                   ->andReturn($capacidad);
                   
        $sensorMock->shouldReceive('getOcupacion')
                   ->once()
                   ->with('machu-picchu')
                   ->andReturn($ocupacion);

        // INTEGRACIÓN LARAVEL: Registramos el contrato mockeado en el contenedor IoC
        $this->instance(SiteSensorInterface::class, $sensorMock);

        // Resolvemos el servicio desde el Service Container de Laravel
        $aforoService = $this->app->make(AforoService::class);

        // WHEN: Ejecutamos el método
        $resultadoActual = $aforoService->calcularNivelAforo('machu-picchu');

        // THEN: Comprobamos el resultado
        $this->assertEquals($resultadoEsperado, $resultadoActual);
    }

    // Archivo: tests/Feature/AforoServiceTest.php
    public function test_calcular_nivel_aforo_lanza_error_por_sobrecapacidad(): void
    {
        // Arrange: Simulación de lecturas de sensores de entrada/salida en Qorikancha
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(100);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(120);

        // Act & Assert
        $this->instance(SiteSensorInterface::class, $sensorMock);
        $aforoService = $this->app->make(AforoService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error: La ocupación supera la capacidad máxima.');

        $aforoService->calcularNivelAforo('Qoricancha');
    }

    public function test_lanza_excepcion_si_ocupacion_es_negativa(): void
    {
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(100);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(-5);

        $this->instance(SiteSensorInterface::class, $sensorMock);
        $aforoService = $this->app->make(AforoService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error: La ocupación no puede ser negativa.');

        $aforoService->calcularNivelAforo('machu-picchu');
    }

    public function test_lanza_excepcion_si_capacidad_es_invalida(): void
    {
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(0);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(10);

        $this->instance(SiteSensorInterface::class, $sensorMock);
        $aforoService = $this->app->make(AforoService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error: La capacidad máxima debe ser mayor a cero.');

        $aforoService->calcularNivelAforo('machu-picchu');
    }
}