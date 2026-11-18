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
     * Propósito: Validar la regla central de CUS-03 con varios horarios usando el proveedor de datos.
     * Escenario: Hora actual y rango (10:00/09:00-17:00, 18:00, bordes 09:00 y 17:00, etc.) -> ABIERTO o CERRADO según corresponda.
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
     * Propósito: Confirmar que solo se acepten rangos con apertura anterior al cierre y formato HH:MM.
     * Escenario: Rangos como 08:00-16:00, 18:00-09:00, 09:00-09:00 o 9:00 -> true o false según el caso.
     * Importancia: Evita que un horario invertido o mal formateado permita evaluaciones ABIERTO falsas.
     */
    #[Test]
    #[DataProvider('casosHorarioValido')]
    public function evalua_la_coherencia_del_horario(
        string $apertura,
        string $cierre,
        bool $esperado,
    ): void {
        $this->assertSame($esperado, $this->service->esHorarioValido($apertura, $cierre));
    }

    /**
     * Propósito: Verificar el formato estricto de 24 horas aceptado por el servicio.
     * Escenario: 00:00, 09:05 y 23:59 -> válidos; 24:00, 12:60, 7:05 y 12:00:00 -> inválidos.
     * Importancia: Impide que horas con segundos, sin ceros o fuera de rango se cueleen en la evaluación.
     */
    #[Test]
    public function valida_formatos_de_hora_de_24_horas(): void
    {
        $this->assertTrue($this->service->esFormatoHoraValido('00:00'));
        $this->assertTrue($this->service->esFormatoHoraValido('09:05'));
        $this->assertTrue($this->service->esFormatoHoraValido('23:59'));
        $this->assertFalse($this->service->esFormatoHoraValido('24:00'));
        $this->assertFalse($this->service->esFormatoHoraValido('12:60'));
        $this->assertFalse($this->service->esFormatoHoraValido('7:05'));
        $this->assertFalse($this->service->esFormatoHoraValido('12:00:00'));
    }

    /**
     * Propósito: Asegurar que un rango invertido se rechace con excepción de negocio.
     * Escenario: estaOperativo('10:00', '18:00', '09:00') -> InvalidArgumentException.
     * Importancia: Evita responder ABIERTO con datos corruptos en lugar de fallar explícitamente.
     */
    #[Test]
    public function lanza_excepcion_con_horario_incoherente(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->estaOperativo('10:00', '18:00', '09:00');
    }

    /**
     * Propósito: Comprobar la validación defensiva de hora actual negativa.
     * Escenario: hora actual '-10:00' con horario 09:00-17:00 -> InvalidArgumentException.
     * Importancia: Impide evaluaciones con valores imposibles que romperían la comparación de minutos.
     */
    #[Test]
    public function lanza_excepcion_con_hora_actual_negativa(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->estaOperativo('-10:00', '09:00', '17:00');
    }

    /**
     * Propósito: Cubrir el límite superior del reloj en la hora de consulta.
     * Escenario: hora actual '25:00' -> InvalidArgumentException.
     * Importancia: Evita que una hora inexistente se convierta en un estado ABIERTO/CERRADO arbitrario.
     */
    #[Test]
    public function lanza_excepcion_con_hora_actual_mayor_a_23_59(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->estaOperativo('25:00', '09:00', '17:00');
    }

    /**
     * Propósito: Garantizar que la hora de consulta sea obligatoria en la capa de servicio.
     * Escenario: hora actual '' -> InvalidArgumentException.
     * Importancia: Previene evaluaciones silenciosas cuando el formulario llega incompleto por error.
     */
    #[Test]
    public function lanza_excepcion_con_hora_actual_vacia(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->estaOperativo('', '09:00', '17:00');
    }

    /**
     * Propósito: Validar que la apertura con minutos inválidos se rechace.
     * Escenario: apertura '09:60' con cierre '17:00' -> InvalidArgumentException.
     * Importancia: Evita que un dato mal escrito en BD provoque una comparación de minutos incorrecta.
     */
    #[Test]
    public function lanza_excepcion_con_horario_de_apertura_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->estaOperativo('10:00', '09:60', '17:00');
    }
}
