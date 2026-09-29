<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Services\DisponibilidadService;
use InvalidArgumentException;

class DisponibilidadServiceTest extends TestCase
{
    private DisponibilidadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DisponibilidadService();
    }

    /* =========================================================================
     * PRUEBAS UNITARIAS DE HU-03 (estaOperativo)
     * ========================================================================= */

    #[Test]
    public function sitio_esta_operativo_en_horario_normal(): void
    {
        // Hora actual: 10:00, apertura: 09:00, cierre: 17:00 -> true
        $this->assertTrue($this->service->estaOperativo('10:00', '09:00', '17:00'));
    }

    #[Test]
    public function sitio_no_esta_operativo_fuera_de_horario(): void
    {
        // Hora actual: 18:00, apertura: 09:00, cierre: 17:00 -> false
        $this->assertFalse($this->service->estaOperativo('18:00', '09:00', '17:00'));
    }

    #[Test]
    public function sitio_esta_operativo_en_hora_exacta_de_apertura(): void
    {
        // Hora actual: 09:00, apertura: 09:00, cierre: 17:00 -> true
        $this->assertTrue($this->service->estaOperativo('09:00', '09:00', '17:00'));
    }

    #[Test]
    public function sitio_esta_operativo_en_hora_exacta_de_cierre(): void
    {
        // Hora actual: 17:00, apertura: 09:00, cierre: 17:00 -> true
        $this->assertTrue($this->service->estaOperativo('17:00', '09:00', '17:00'));
    }

    /* =========================================================================
     * PRUEBAS DE VALIDACIÓN DE HORARIOS Y ERRORES (DATOS NEGATIVOS / ILÓGICOS)
     * ========================================================================= */

    #[Test]
    public function valida_correctamente_un_horario_de_atencion_coherente(): void
    {
        $this->assertTrue($this->service->esHorarioValido('08:00', '16:00'));
    }

    #[Test]
    public function detecta_un_horario_de_atencion_invalido(): void
    {
        // Cierre antes de apertura
        $this->assertFalse($this->service->esHorarioValido('18:00', '09:00'));
    }

    #[Test]
    public function lanza_excepcion_al_evaluar_operatividad_con_horario_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->estaOperativo('10:00', '18:00', '09:00');
    }

    #[Test]
    public function lanza_excepcion_si_la_hora_actual_es_negativa(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->estaOperativo('-10:00', '09:00', '17:00');
    }

    #[Test]
    public function lanza_excepcion_si_la_hora_actual_supera_las_24_horas(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->estaOperativo('25:00', '09:00', '17:00');
    }
}