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

    /**
     * Given: se tiene el servicio de reseñas.
     * When: se valida la calificación 1, el valor mínimo permitido.
     * Then: debe devolver true porque 1 está dentro del rango admitido.
     */
    public function test_validar_calificacion_acepta_el_valor_minimo(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida la calificación 1.
        $resultado = $service->validarCalificacion(1);

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    /**
     * Given: se tiene el servicio de reseñas.
     * When: se valida la calificación 3, un valor intermedio.
     * Then: debe devolver true porque 3 está dentro del rango admitido.
     */
    public function test_validar_calificacion_acepta_un_valor_intermedio(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida la calificación 3.
        $resultado = $service->validarCalificacion(3);

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    /**
     * Given: se tiene el servicio de reseñas.
     * When: se valida la calificación 5, el valor máximo permitido.
     * Then: debe devolver true porque 5 está dentro del rango admitido.
     */
    public function test_validar_calificacion_acepta_el_valor_maximo(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida la calificación 5.
        $resultado = $service->validarCalificacion(5);

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    /**
     * Given: se tiene el servicio de reseñas.
     * When: se valida la calificación -1, un número negativo.
     * Then: debe devolver false porque -1 está fuera del rango admitido.
     */
    public function test_validar_calificacion_rechaza_un_numero_negativo(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida la calificación -1.
        $resultado = $service->validarCalificacion(-1);

        // Then: debe devolver false.
        $this->assertFalse($resultado);
    }

    /**
     * Given: se tiene el servicio de reseñas.
     * When: se valida la calificación 0, el límite inferior excluido.
     * Then: debe devolver false porque 0 no es una calificación válida.
     */
    public function test_validar_calificacion_rechaza_el_cero(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida la calificación 0.
        $resultado = $service->validarCalificacion(0);

        // Then: debe devolver false.
        $this->assertFalse($resultado);
    }

    /**
     * Given: se tiene el servicio de reseñas.
     * When: se valida la calificación 6, por encima del máximo permitido.
     * Then: debe devolver false porque 6 está fuera del rango admitido.
     */
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

    /**
     * Given: se tiene el servicio de reseñas.
     * When: se valida el comentario 'Excelente lugar', que contiene texto.
     * Then: debe devolver true porque el comentario no está vacío.
     */
    public function test_validar_comentario_acepta_un_texto(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida un comentario con texto.
        $resultado = $service->validarComentario('Excelente lugar');

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    /**
     * Given: se tiene el servicio de reseñas.
     * When: se valida el comentario 'A', de un solo carácter.
     * Then: debe devolver true porque un carácter ya cuenta como texto válido.
     */
    public function test_validar_comentario_acepta_un_texto_corto(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida un comentario de un solo carácter.
        $resultado = $service->validarComentario('A');

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    /**
     * Given: se tiene el servicio de reseñas.
     * When: se valida el comentario '  Excelente lugar  ', con espacios en los bordes.
     * Then: debe devolver true porque los espacios externos no invalidan el texto.
     */
    public function test_validar_comentario_acepta_texto_con_espacios_en_los_bordes(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida un comentario con espacios al inicio y al final.
        $resultado = $service->validarComentario('  Excelente lugar  ');

        // Then: debe devolver true.
        $this->assertTrue($resultado);
    }

    /**
     * Given: se tiene el servicio de reseñas.
     * When: se valida el comentario vacío ''.
     * Then: debe devolver false porque no hay texto que aceptar.
     */
    public function test_validar_comentario_rechaza_un_texto_vacio(): void
    {
        // Given: se tiene el servicio de reseñas.
        $service = $this->crearServicio();

        // When: se valida un comentario vacío.
        $resultado = $service->validarComentario('');

        // Then: debe devolver false.
        $this->assertFalse($resultado);
    }

    /**
     * Given: se tiene el servicio de reseñas.
     * When: se valida el comentario '     ', formado solo por espacios.
     * Then: debe devolver false porque los espacios no forman un texto válido.
     */
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

    /**
     * Given: el repositorio devuelve las calificaciones 5, 4 y 3.
     * When: se calcula el promedio de calificaciones.
     * Then: el promedio debe ser 4.0.
     */
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

    /**
     * Given: el repositorio devuelve las calificaciones 5, 5, 4 y 4.
     * When: se calcula el promedio de calificaciones.
     * Then: el promedio debe ser 4.5, conservando la parte decimal.
     */
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

    /**
     * Given: el repositorio devuelve las calificaciones 3, 3 y 3.
     * When: se calcula el promedio de calificaciones.
     * Then: el promedio debe ser 3.0, igual a cada calificación.
     */
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

    /**
     * Given: el repositorio devuelve una sola calificación de 1.
     * When: se calcula el promedio de calificaciones.
     * Then: el promedio debe ser 1.0, el valor mínimo.
     */
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

    /**
     * Given: el repositorio devuelve una sola calificación de 5.
     * When: se calcula el promedio de calificaciones.
     * Then: el promedio debe ser 5.0, el valor máximo.
     */
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

    /**
     * Given: el repositorio devuelve las calificaciones extremas 1 y 5.
     * When: se calcula el promedio de calificaciones.
     * Then: el promedio debe ser 3.0, justo en el punto medio.
     */
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

    /**
     * Given: el repositorio no devuelve ninguna calificación.
     * When: se calcula el promedio de calificaciones.
     * Then: debe devolver 0.0 en lugar de fallar por falta de datos.
     */
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
