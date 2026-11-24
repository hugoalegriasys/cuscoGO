<?php

namespace Tests\Unit;

use App\Contracts\SiteSensorInterface;
use App\Services\AforoService;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase; // Base pura de PHPUnit (sin framework)

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

    /**
     * Given: Un sensor simulado que reporta capacidad y ocupación para 'machu-picchu' según el proveedor (100/30, 100/50, 100/60, 100/80, 100/90 y 100/100).
     * When: Se calcula el nivel de aforo de 'machu-picchu' con AforoService.
     * Then: El nivel obtenido es $resultadoEsperado: 'Bajo' hasta 50%, 'Moderado' hasta 80% y 'Alto' desde 90%.
     */
    #[DataProvider('aforoCasosProvider')]
    public function test_calcular_nivel_aforo_retorna_estado_correcto(int $capacidad, int $ocupacion, string $resultadoEsperado): void
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

        // WHEN: Instanciamos directamente la unidad a probar
        $aforoService = new AforoService($sensorMock);

        // ACT: Ejecutamos el método
        $resultadoActual = $aforoService->calcularNivelAforo('machu-picchu');

        // THEN: Comprobamos el resultado
        $this->assertEquals($resultadoEsperado, $resultadoActual);
    }

    /**
     * Given: Un sensor simulado que reporta capacidad 100 y ocupación 120 en 'Qoricancha'.
     * When: Se calcula el nivel de aforo de 'Qoricancha'.
     * Then: Se lanza InvalidArgumentException con "Error: La ocupación supera la capacidad máxima.".
     */
    public function test_calcular_nivel_aforo_lanza_error_por_sobrecapacidad(): void
    {
        // Given: Un sensor con capacidad 100 y una ocupación de 120 por encima del máximo.
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(100);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(120);

        $aforoService = new AforoService($sensorMock);

        // Then: Se espera InvalidArgumentException con el mensaje de sobrecapacidad.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error: La ocupación supera la capacidad máxima.');

        // When: Se calcula el nivel de aforo de 'Qoricancha'.
        $aforoService->calcularNivelAforo('Qoricancha');
    }

    /**
     * Given: Un sensor con lectura defectuosa que reporta capacidad 100 y ocupación -5 en 'machu-picchu'.
     * When: Se calcula el nivel de aforo de 'machu-picchu'.
     * Then: Se lanza InvalidArgumentException con "Error: La ocupación no puede ser negativa.".
     */
    public function test_lanza_excepcion_si_ocupacion_es_negativa(): void
    {
        // Given: Simulación de lectura defectuosa con número negativo (-5).
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(100);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(-5);

        $aforoService = new AforoService($sensorMock);

        // Then: Se espera InvalidArgumentException con el mensaje de ocupación negativa.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error: La ocupación no puede ser negativa.');

        // When: Se calcula el nivel de aforo de 'machu-picchu'.
        $aforoService->calcularNivelAforo('machu-picchu');
    }

    /**
     * Given: Un sensor que reporta capacidad 0 y ocupación 10 en 'machu-picchu'.
     * When: Se calcula el nivel de aforo de 'machu-picchu'.
     * Then: Se lanza InvalidArgumentException con "Error: La capacidad máxima debe ser mayor a cero.".
     */
    public function test_lanza_excepcion_si_capacidad_es_invalida(): void
    {
        // Given: Simulación de capacidad en 0 con ocupación 10.
        $sensorMock = Mockery::mock(SiteSensorInterface::class);
        $sensorMock->shouldReceive('getCapacidad')->andReturn(0);
        $sensorMock->shouldReceive('getOcupacion')->andReturn(10);

        $aforoService = new AforoService($sensorMock);

        // Then: Se espera InvalidArgumentException con el mensaje de capacidad inválida.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error: La capacidad máxima debe ser mayor a cero.');

        // When: Se calcula el nivel de aforo de 'machu-picchu'.
        $aforoService->calcularNivelAforo('machu-picchu');
    }
}
