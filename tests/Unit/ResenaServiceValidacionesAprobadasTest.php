<?php

namespace Tests\Unit;

use App\Contracts\ResenaRepository;
use App\Services\ResenaService;
use Mockery;
use PHPUnit\Framework\TestCase;

class ResenaServiceValidacionesAprobadasTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_validar_calificacion_rechaza_valor_no_entero_decimal(): void
    {
        // Given
        $service = $this->crearServicio();

        // When
        $resultado = $service->validarCalificacion(4.5);

        // Then
        $this->assertFalse($resultado);
    }

    public function test_validar_calificacion_rechaza_valor_no_numerico(): void
    {
        // Given
        $service = $this->crearServicio();

        // When
        $resultado = $service->validarCalificacion('abc');

        // Then
        $this->assertFalse($resultado);
    }

    public function test_validar_calificacion_rechaza_valor_nulo(): void
    {
        // Given
        $service = $this->crearServicio();

        // When
        $resultado = $service->validarCalificacion(null);

        // Then
        $this->assertFalse($resultado);
    }

    public function test_validar_calificacion_rechaza_valor_booleano(): void
    {
        // Given
        $service = $this->crearServicio();

        // When
        $resultado = $service->validarCalificacion(true);

        // Then
        $this->assertFalse($resultado);
    }

    public function test_validar_comentario_rechaza_texto_mayor_a_500_caracteres(): void
    {
        // Given
        $service = $this->crearServicio();
        $comentario = str_repeat('a', 501);

        // When
        $resultado = $service->validarComentario($comentario);

        // Then
        $this->assertFalse($resultado);
    }

    public function test_validar_comentario_acepta_texto_de_500_caracteres(): void
    {
        // Given
        $service = $this->crearServicio();
        $comentario = str_repeat('a', 500);

        // When
        $resultado = $service->validarComentario($comentario);

        // Then
        $this->assertTrue($resultado);
    }

    public function test_calcular_promedio_redondea_a_un_decimal(): void
    {
        // Given
        $repository = Mockery::mock(ResenaRepository::class);
        $repository->shouldReceive('obtenerCalificaciones')->once()->andReturn([5, 4, 4]);
        $service = new ResenaService($repository);

        // When
        $resultado = $service->calcularPromedioCalificaciones();

        // Then
        $this->assertSame(4.3, $resultado);
    }

    public function test_calcular_promedio_por_sitio_redondea_a_un_decimal(): void
    {
        // Given
        $repository = Mockery::mock(ResenaRepository::class);
        $repository->shouldReceive('obtenerCalificacionesPorSitio')->once()->with(1)->andReturn([5, 4, 4]);
        $service = new ResenaService($repository);

        // When
        $resultado = $service->calcularPromedioCalificacionesPorSitio(1);

        // Then
        $this->assertSame(4.3, $resultado);
    }

    public function test_calcular_promedio_por_sitio_devuelve_cero_sin_calificaciones(): void
    {
        // Given
        $repository = Mockery::mock(ResenaRepository::class);
        $repository->shouldReceive('obtenerCalificacionesPorSitio')->once()->with(2)->andReturn([]);
        $service = new ResenaService($repository);

        // When
        $resultado = $service->calcularPromedioCalificacionesPorSitio(2);

        // Then
        $this->assertSame(0.0, $resultado);
    }

    private function crearServicio(): ResenaService
    {
        return new ResenaService(Mockery::mock(ResenaRepository::class));
    }
}
