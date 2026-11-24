<?php

/**
 * Capa de pruebas unitarias de CUS-03.
 * Valida únicamente la lógica pura de DisponibilidadService: formato de hora, coherencia de rangos,
 * límites inclusivos de apertura/cierre y mensajes exactos de excepción, sin HTTP, base de datos ni navegador.
 * Es el primer lugar que debe avisar al equipo si una regla de operatividad cambia por error.
 */

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DisponibilidadService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DisponibilidadServiceTest extends TestCase
{
    private DisponibilidadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DisponibilidadService;
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: bool}>
     */
    public static function casosOperatividad(): array
    {
        return [
            'horario normal' => ['10:00', '09:00', '17:00', true],
            'fuera de horario' => ['18:00', '09:00', '17:00', false],
            'hora exacta de apertura' => ['09:00', '09:00', '17:00', true],
            'hora exacta de cierre' => ['17:00', '09:00', '17:00', true],
            'un minuto antes de la apertura' => ['08:59', '09:00', '17:00', false],
            'un minuto despues del cierre' => ['17:01', '09:00', '17:00', false],
            'jornada completa' => ['00:00', '00:00', '23:59', true],
        ];
    }

    /**
     * Given: Una hora de consulta y un rango del proveedor: 10:00/09:00-17:00, 18:00, bordes 09:00 y 17:00, 08:59, 17:01 o jornada 00:00-23:59.
     * When: Se evalúa la operatividad del sitio con estaOperativo() usando la hora y el rango del caso.
     * Then: El resultado es ABIERTO (true) en 10:00, 09:00, 17:00 y 00:00, y CERRADO (false) en 18:00, 08:59 y 17:01.
     * Importancia: Previene errores en los límites inclusivos y en comparaciones fuera del horario.
     */
    #[Test]
    #[DataProvider('casosOperatividad')]
    public function evalua_la_operatividad_del_sitio(
        string $horaActual,
        string $apertura,
        string $cierre,
        bool $esperado,
    ): void {
        // Given: El proveedor entrega la hora actual, la apertura, el cierre y el resultado esperado del caso.
        // When: Se consulta estaOperativo() con esa hora y ese rango.
        // Then: El estado devuelto debe coincidir con $esperado.
        $this->assertSame(
            $esperado,
            $this->service->estaOperativo($horaActual, $apertura, $cierre),
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function casosHorarioValido(): array
    {
        return [
            'rango coherente' => ['08:00', '16:00', true],
            'cierre previo a la apertura' => ['18:00', '09:00', false],
            'apertura igual al cierre' => ['09:00', '09:00', false],
            'apertura con formato invalido' => ['9:00', '16:00', false],
            'hora mayor a 23' => ['24:00', '16:00', false],
            'minutos mayores a 59' => ['08:60', '16:00', false],
            'apertura vacia' => ['', '16:00', false],
        ];
    }

    /**
     * Given: Rangos del proveedor como 08:00-16:00, 18:00-09:00, 09:00-09:00, 9:00, 24:00, 08:60 o apertura vacía.
     * When: Se valida cada rango con esHorarioValido() usando su apertura y cierre.
     * Then: Solo 08:00-16:00 devuelve true; los rangos invertidos, iguales o mal formateados devuelven false.
     * Importancia: Evita que un horario invertido o mal formateado permita evaluaciones ABIERTO falsas.
     */
    #[Test]
    #[DataProvider('casosHorarioValido')]
    public function evalua_la_coherencia_del_horario(
        string $apertura,
        string $cierre,
        bool $esperado,
    ): void {
        // Given: El proveedor entrega la apertura, el cierre y la validez esperada del rango.
        // When: Se valida el rango con esHorarioValido().
        // Then: El resultado debe coincidir con $esperado.
        $this->assertSame($esperado, $this->service->esHorarioValido($apertura, $cierre));
    }

    /**
     * Given: Las horas de prueba 00:00, 09:05, 23:59, 24:00, 12:60, 7:05 y 12:00:00.
     * When: Se valida cada una con esFormatoHoraValido().
     * Then: 00:00, 09:05 y 23:59 son válidas; 24:00, 12:60, 7:05 y 12:00:00 son inválidas.
     * Importancia: Impide que horas con segundos, sin ceros o fuera de rango se cueleen en la evaluación.
     */
    #[Test]
    public function valida_formatos_de_hora_de_24_horas(): void
    {
        // Given: Se dispone de las horas de prueba 00:00, 09:05, 23:59, 24:00, 12:60, 7:05 y 12:00:00.
        // When: Se valida cada hora con esFormatoHoraValido().
        // Then: Las tres primeras deben ser válidas y las cuatro últimas inválidas.
        $this->assertTrue($this->service->esFormatoHoraValido('00:00'));
        $this->assertTrue($this->service->esFormatoHoraValido('09:05'));
        $this->assertTrue($this->service->esFormatoHoraValido('23:59'));
        $this->assertFalse($this->service->esFormatoHoraValido('24:00'));
        $this->assertFalse($this->service->esFormatoHoraValido('12:60'));
        $this->assertFalse($this->service->esFormatoHoraValido('7:05'));
        $this->assertFalse($this->service->esFormatoHoraValido('12:00:00'));
    }

    /**
     * Given: Un horario invertido con apertura '18:00' y cierre '09:00'.
     * When: Se consulta estaOperativo('10:00', '18:00', '09:00').
     * Then: Se lanza InvalidArgumentException en lugar de responder ABIERTO.
     * Importancia: Evita responder ABIERTO con datos corruptos en lugar de fallar explícitamente.
     */
    #[Test]
    public function lanza_excepcion_con_horario_incoherente(): void
    {
        // Given: El servicio ya está instanciado y el horario va de '18:00' a '09:00'.
        // Then: Se espera InvalidArgumentException por el rango invertido.
        $this->expectException(InvalidArgumentException::class);

        // When: Se evalúa la operatividad a las '10:00' con ese horario.
        $this->service->estaOperativo('10:00', '18:00', '09:00');
    }

    /**
     * Given: Una hora de consulta imposible, '-10:00', con el horario 09:00-17:00.
     * When: Se consulta estaOperativo('-10:00', '09:00', '17:00').
     * Then: Se lanza InvalidArgumentException antes de comparar minutos.
     * Importancia: Impide evaluaciones con valores imposibles que romperían la comparación de minutos.
     */
    #[Test]
    public function lanza_excepcion_con_hora_actual_negativa(): void
    {
        // Given: El servicio ya está instanciado y la hora de consulta es '-10:00'.
        // Then: Se espera InvalidArgumentException por la hora negativa.
        $this->expectException(InvalidArgumentException::class);

        // When: Se evalúa la operatividad con el horario 09:00-17:00.
        $this->service->estaOperativo('-10:00', '09:00', '17:00');
    }

    /**
     * Given: Una hora de consulta fuera del reloj, '25:00', con el horario 09:00-17:00.
     * When: Se consulta estaOperativo('25:00', '09:00', '17:00').
     * Then: Se lanza InvalidArgumentException en lugar de devolver ABIERTO o CERRADO.
     * Importancia: Evita que una hora inexistente se convierta en un estado ABIERTO/CERRADO arbitrario.
     */
    #[Test]
    public function lanza_excepcion_con_hora_actual_mayor_a_23_59(): void
    {
        // Given: El servicio ya está instanciado y la hora de consulta es '25:00'.
        // Then: Se espera InvalidArgumentException por exceder las 23:59.
        $this->expectException(InvalidArgumentException::class);

        // When: Se evalúa la operatividad con el horario 09:00-17:00.
        $this->service->estaOperativo('25:00', '09:00', '17:00');
    }

    /**
     * Given: Una hora de consulta vacía, '', con el horario 09:00-17:00.
     * When: Se consulta estaOperativo('', '09:00', '17:00').
     * Then: Se lanza InvalidArgumentException porque la hora es obligatoria.
     * Importancia: Previene evaluaciones silenciosas cuando el formulario llega incompleto por error.
     */
    #[Test]
    public function lanza_excepcion_con_hora_actual_vacia(): void
    {
        // Given: El servicio ya está instanciado y la hora de consulta está vacía.
        // Then: Se espera InvalidArgumentException por la hora obligatoria.
        $this->expectException(InvalidArgumentException::class);

        // When: Se evalúa la operatividad con el horario 09:00-17:00.
        $this->service->estaOperativo('', '09:00', '17:00');
    }

    /**
     * Given: Una apertura con minutos inválidos, '09:60', y un cierre '17:00'.
     * When: Se consulta estaOperativo('10:00', '09:60', '17:00').
     * Then: Se lanza InvalidArgumentException antes de comparar minutos.
     * Importancia: Evita que un dato mal escrito en BD provoque una comparación de minutos incorrecta.
     */
    #[Test]
    public function lanza_excepcion_con_horario_de_apertura_invalido(): void
    {
        // Given: El servicio ya está instanciado y la apertura registrada es '09:60'.
        // Then: Se espera InvalidArgumentException por los minutos de apertura.
        $this->expectException(InvalidArgumentException::class);

        // When: Se evalúa la operatividad a las '10:00' con ese horario.
        $this->service->estaOperativo('10:00', '09:60', '17:00');
    }
}
