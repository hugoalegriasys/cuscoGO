<?php

namespace Tests\Unit;

use App\Contracts\ResenaRepository;
use App\Services\ResenaService;
use Mockery;
use PHPUnit\Framework\TestCase;

class ResenaServiceTest extends TestCase
{
    // Cierra los mocks después de cada prueba.
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ================= validarCalificacion() =================

    public function test_validar_calificacion_acepta_el_valor_minimo(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida la calificación 1.
        $resultado = $service->validarCalificacion(1);

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    public function test_validar_calificacion_acepta_un_valor_intermedio(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida la calificación 3.
        $resultado = $service->validarCalificacion(3);

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    public function test_validar_calificacion_acepta_el_valor_maximo(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida la calificación 5.
        $resultado = $service->validarCalificacion(5);

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    public function test_validar_calificacion_rechaza_un_numero_negativo(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida la calificación -1.
        $resultado = $service->validarCalificacion(-1);

        // Then: debe devolver false.
        $this->assertFalse($resultado);
    }

    public function test_validar_calificacion_rechaza_el_cero(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida la calificación 0.
        $resultado = $service->validarCalificacion(0);

        // Then: debe devolver false.
        $this->assertFalse($resultado);
    }

    public function test_validar_calificacion_rechaza_un_valor_mayor_al_maximo(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida la calificación 6.
        $resultado = $service->validarCalificacion(6);

        // Then: debe devolver false.
        $this->assertFalse($resultado);
    }

    // ================= validarComentario() =================

    public function test_validar_comentario_acepta_un_texto(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida un comentario con texto.
        $resultado = $service->validarComentario('Excelente lugar');

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    public function test_validar_comentario_acepta_un_texto_corto(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida un comentario de un solo carácter.
        $resultado = $service->validarComentario('A');

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    public function test_validar_comentario_acepta_texto_con_espacios_en_los_bordes(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida un comentario con espacios al inicio y al final.
        $resultado = $service->validarComentario('  Excelente lugar  ');

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    public function test_validar_comentario_rechaza_un_texto_vacio(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida un comentario vacío.
        $resultado = $service->validarComentario('');

        // Then: debe devolver false.
        $this->assertFalse($resultado);
    }

    public function test_validar_comentario_rechaza_solo_espacios(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida un comentario con espacios.
        $resultado = $service->validarComentario('     ');

        // Then: debe devolver false.
        $this->assertFalse($resultado);
    }

    // ================= calcularPromedioCalificaciones() =================

    public function test_calcula_promedio_de_calificaciones(): void
    {
        // Given: el repositorio devuelve 5, 4 y 3.
        $repository = Mockery::mock(ResenaRepository::class);

        $repository->shouldReceive('obtenerCalificaciones')
            ->once()
            ->andReturn([5, 4, 3]);

        $service = new ResenaService($repository);

        // When: se calcula el promedio.
        $resultado = $service->calcularPromedioCalificaciones();

        // Then: el promedio debe ser 4.0.
        $this->assertSame(4.0, $resultado);
    }

    public function test_calcula_un_promedio_decimal(): void
    {
        // Given: el repositorio devuelve 5, 5, 4 y 4.
        $repository = Mockery::mock(ResenaRepository::class);

        $repository->shouldReceive('obtenerCalificaciones')
            ->once()
            ->andReturn([5, 5, 4, 4]);

        $service = new ResenaService($repository);

        // When: se calcula el promedio.
        $resultado = $service->calcularPromedioCalificaciones();

        // Then: el promedio debe ser 4.5.
        $this->assertSame(4.5, $resultado);
    }

    public function test_calcula_promedio_de_calificaciones_iguales(): void
    {
        // Given: el repositorio devuelve 3, 3 y 3.
        $repository = Mockery::mock(ResenaRepository::class);

        $repository->shouldReceive('obtenerCalificaciones')
            ->once()
            ->andReturn([3, 3, 3]);

        $service = new ResenaService($repository);

        // When: se calcula el promedio.
        $resultado = $service->calcularPromedioCalificaciones();

        // Then: el promedio debe ser 3.0.
        $this->assertSame(3.0, $resultado);
    }

    public function test_calcula_promedio_de_una_calificacion_minima(): void
    {
        // Given: el repositorio devuelve una calificación de 1.
        $repository = Mockery::mock(ResenaRepository::class);

        $repository->shouldReceive('obtenerCalificaciones')
            ->once()
            ->andReturn([1]);

        $service = new ResenaService($repository);

        // When: se calcula el promedio.
        $resultado = $service->calcularPromedioCalificaciones();

        // Then: el promedio debe ser 1.0.
        $this->assertSame(1.0, $resultado);
    }

    public function test_calcula_promedio_de_una_calificacion_maxima(): void
    {
        // Given: el repositorio devuelve una calificación de 5.
        $repository = Mockery::mock(ResenaRepository::class);

        $repository->shouldReceive('obtenerCalificaciones')
            ->once()
            ->andReturn([5]);

        $service = new ResenaService($repository);

        // When: se calcula el promedio.
        $resultado = $service->calcularPromedioCalificaciones();

        // Then: el promedio debe ser 5.0.
        $this->assertSame(5.0, $resultado);
    }

    public function test_calcula_promedio_de_calificaciones_extremas(): void
    {
        // Given: el repositorio devuelve 1 y 5.
        $repository = Mockery::mock(ResenaRepository::class);

        $repository->shouldReceive('obtenerCalificaciones')
            ->once()
            ->andReturn([1, 5]);

        $service = new ResenaService($repository);

        // When: se calcula el promedio.
        $resultado = $service->calcularPromedioCalificaciones();

        // Then: el promedio debe ser 3.0.
        $this->assertSame(3.0, $resultado);
    }

    public function test_calcula_promedio_devuelve_cero_sin_calificaciones(): void
    {
        // Given: el repositorio no devuelve calificaciones.
        $repository = Mockery::mock(ResenaRepository::class);

        $repository->shouldReceive('obtenerCalificaciones')
            ->once()
            ->andReturn([]);

        $service = new ResenaService($repository);

        // When: se calcula el promedio.
        $resultado = $service->calcularPromedioCalificaciones();

        // Then: debe devolver 0.0.
        $this->assertSame(0.0, $resultado);
    }

    // Crea el servicio con un repositorio simulado.
    private function crearServicio(): ResenaService
    {
        return new ResenaService(
            Mockery::mock(ResenaRepository::class)
        );
    }
}