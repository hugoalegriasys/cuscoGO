<?php

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

    #[Test]
    #[DataProvider('casosHorarioValido')]
    public function evalua_la_coherencia_del_horario(
        string $apertura,
        string $cierre,
        bool $esperado,
    ): void {
        $this->assertSame($esperado, $this->service->esHorarioValido($apertura, $cierre));
    }

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

    #[Test]
    public function lanza_excepcion_con_horario_incoherente(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->estaOperativo('10:00', '18:00', '09:00');
    }

    #[Test]
    public function lanza_excepcion_con_hora_actual_negativa(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->estaOperativo('-10:00', '09:00', '17:00');
    }

    #[Test]
    public function lanza_excepcion_con_hora_actual_mayor_a_23_59(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->estaOperativo('25:00', '09:00', '17:00');
    }

    #[Test]
    public function lanza_excepcion_con_hora_actual_vacia(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->estaOperativo('', '09:00', '17:00');
    }

    #[Test]
    public function lanza_excepcion_con_horario_de_apertura_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->estaOperativo('10:00', '09:60', '17:00');
    }
}
