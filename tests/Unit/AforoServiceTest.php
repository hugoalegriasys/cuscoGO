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
    // Limpieza de Mockery al finalizar cada prueba
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public static function aforoCasosProvider(): array
    {
        return [
            'Ocupacion 30' => [100, 30, 'Bajo'],
            'Ocupacion 40' => [100, 40, 'Bajo'],
            'Ocupacion 60' => [100, 60, 'Moderado'],
            'Ocupacion 75' => [100, 75, 'Moderado'],
            'Ocupacion 90' => [100, 90, 'Alto'],
        ];
    }

    #[DataProvider('aforoCasosProvider')]
    public function test_calcular_nivel_aforo_retorna_estado_correcto(int $capacidad, int $ocupacion, string $resultadoEsperado): void
    {
        // GIVEN: Tenemos un sensor mockeado que devuelve los datos de la tabla
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        
        $sensorMock->shouldReceive('getCapacidad')
                   ->once()
                   ->with('machu-picchu')
                   ->andReturn($capacidad);
                   
        $sensorMock->shouldReceive('getOcupacion')
                   ->once()
                   ->with('machu-picchu')
                   ->andReturn($ocupacion);

        $aforoService = new AforoService($sensorMock);

        // WHEN: Consultamos el nivel de aforo del sitio
        $resultadoActual = $aforoService->calcularNivelAforo('machu-picchu');

        // THEN: El nivel calculado debe coincidir con el resultado esperado de la tabla
        $this->assertEquals($resultadoEsperado, $resultadoActual);
    }

    public function test_calcular_nivel_aforo_lanza_error_por_sobrecapacidad(): void
    {
        // GIVEN: El mock devuelve 120 de ocupación para una capacidad de 100
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(100);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(120);

        $aforoService = new AforoService($sensorMock);

        // THEN: Esperamos que el sistema lance una excepción indicando "Error"
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error: La ocupación supera la capacidad máxima.');

        // WHEN: Ejecutamos el cálculo
        $aforoService->calcularNivelAforo('machu-picchu');
    }
}