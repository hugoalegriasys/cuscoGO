<?php

namespace Tests\Unit;

use App\Contracts\SiteSensorInterface;
use App\Services\AforoService;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AforoServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public static function aforoCasosProvider(): array
    {
        return [
            'Ocupación 30 con capacidad 100 es Bajo' => [100, 30, 'Bajo'],
            'Ocupación 40 con capacidad 100 es Bajo' => [100, 40, 'Bajo'],
            'Ocupación 60 con capacidad 100 es Moderado' => [100, 60, 'Moderado'],
            'Ocupación 75 con capacidad 100 es Moderado' => [100, 75, 'Moderado'],
            'Ocupación 90 con capacidad 100 es Alto' => [100, 90, 'Alto'],
        ];
    }

    #[DataProvider('aforoCasosProvider')]
    public function test_calcular_nivel_aforo_retorna_estado_correcto(int $capacidad, int $ocupacion, string $resultadoEsperado): void
    {
        // Given
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->once()->with('machu-picchu')->andReturn($capacidad);
        $sensorMock->shouldReceive('getOcupacion')->once()->with('machu-picchu')->andReturn($ocupacion);
        $aforoService = new AforoService($sensorMock);

        // When
        $resultadoActual = $aforoService->calcularNivelAforo('machu-picchu');

        // Then
        $this->assertSame($resultadoEsperado, $resultadoActual);
    }

    public function test_calcular_nivel_aforo_lanza_error_por_sobrecapacidad(): void
    {
        // Given
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(100);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(120);
        $aforoService = new AforoService($sensorMock);

        // Then
        $this->expectException(InvalidArgumentException::class);

        // When
        $aforoService->calcularNivelAforo('machu-picchu');
    }

    public function test_calcular_nivel_aforo_lanza_error_si_capacidad_es_cero(): void
    {
        // Given
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(0);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(0);
        $aforoService = new AforoService($sensorMock);

        // Then
        $this->expectException(InvalidArgumentException::class);

        // When
        $aforoService->calcularNivelAforo('machu-picchu');
    }

    public function test_calcular_nivel_aforo_lanza_error_si_capacidad_es_negativa(): void
    {
        // Given
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(-1);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(0);
        $aforoService = new AforoService($sensorMock);

        // Then
        $this->expectException(InvalidArgumentException::class);

        // When
        $aforoService->calcularNivelAforo('machu-picchu');
    }
}