<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\EstadoOperatividadDTO;
use App\Models\SitioPatrimonial;
use App\Services\DisponibilidadService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias de los métodos de sitio patrimonial del
 * servicio de disponibilidad (CUS-03: Evaluación de Estado de
 * Operatividad). Se usan modelos en memoria, sin base de datos.
 */
class EstadoOperatividadSitioServiceTest extends TestCase
{
    private DisponibilidadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DisponibilidadService;
    }

    private function crearSitio(array $atributos = []): SitioPatrimonial
    {
        $porOmision = [
            'nombre' => 'Qorikancha',
            'direccion' => 'Calle Santo Domingo, Cusco',
            'descripcion' => 'Templo del Sol',
            'hora_apertura' => '09:00',
            'hora_cierre' => '17:00',
            'activo' => true,
        ];

        return new SitioPatrimonial(array_merge($porOmision, $atributos));
    }

    /* =====================================================================
     * verificarSitioAbierto()
     * ===================================================================== */

    #[Test]
    public function sitio_esta_abierto_dentro_del_horario_de_atencion(): void
    {
        $sitio = $this->crearSitio();

        $this->assertTrue($this->service->verificarSitioAbierto($sitio, '10:00'));
    }

    #[Test]
    public function sitio_no_esta_abierto_fuera_del_horario_de_atencion(): void
    {
        $sitio = $this->crearSitio();

        $this->assertFalse($this->service->verificarSitioAbierto($sitio, '18:00'));
    }

    #[Test]
    public function sitio_inactivo_nunca_se_reporta_como_abierto(): void
    {
        $sitio = $this->crearSitio(['activo' => false]);

        $this->assertFalse($this->service->verificarSitioAbierto($sitio, '10:00'));
    }

    #[Test]
    public function sitio_esta_abierto_cuando_se_consulta_con_la_hora_actual_del_sistema(): void
    {
        // Horario 00:00 - 23:59 cubre cualquier hora actual del sistema.
        $sitio = $this->crearSitio([
            'hora_apertura' => '00:00',
            'hora_cierre' => '23:59',
        ]);

        $this->assertTrue($this->service->verificarSitioAbierto($sitio));
    }

    /* =====================================================================
     * evaluarEstadoOperatividad()
     * ===================================================================== */

    #[Test]
    public function evaluacion_devuelve_dto_abierto_con_los_datos_correctos(): void
    {
        $sitio = $this->crearSitio(['nombre' => 'Sacsayhuamán']);

        $estado = $this->service->evaluarEstadoOperatividad($sitio, '10:00');

        $this->assertInstanceOf(EstadoOperatividadDTO::class, $estado);
        $this->assertSame('abierto', $estado->estado);
        $this->assertTrue($estado->operativo);
        $this->assertSame('Sacsayhuamán', $estado->nombreSitio);
        $this->assertSame('10:00', $estado->horaActual);
        $this->assertSame('09:00', $estado->horaApertura);
        $this->assertSame('17:00', $estado->horaCierre);
        $this->assertStringContainsStringIgnoringCase('abierto', $estado->mensaje);
    }

    #[Test]
    public function evaluacion_devuelve_dto_cerrado_con_los_datos_correctos(): void
    {
        $sitio = $this->crearSitio();

        $estado = $this->service->evaluarEstadoOperatividad($sitio, '18:00');

        $this->assertSame('cerrado', $estado->estado);
        $this->assertFalse($estado->operativo);
        $this->assertSame('18:00', $estado->horaActual);
        $this->assertStringContainsStringIgnoringCase('cerrado', $estado->mensaje);
    }

    #[Test]
    public function evaluacion_devuelve_dto_inactivo_cuando_el_sitio_esta_desactivado(): void
    {
        $sitio = $this->crearSitio(['activo' => false]);

        $estado = $this->service->evaluarEstadoOperatividad($sitio, '10:00');

        $this->assertSame('inactivo', $estado->estado);
        $this->assertFalse($estado->operativo);
        $this->assertSame('El sitio patrimonial se encuentra temporalmente inactivo.', $estado->mensaje);
    }

    #[Test]
    public function evaluacion_convierte_el_dto_a_un_arreglo_asociativo_completo(): void
    {
        $sitio = $this->crearSitio();

        $arreglo = $this->service->evaluarEstadoOperatividad($sitio, '10:00')->toArray();

        $this->assertArrayHasKey('sitio_id', $arreglo);
        $this->assertArrayHasKey('nombre_sitio', $arreglo);
        $this->assertArrayHasKey('estado', $arreglo);
        $this->assertArrayHasKey('operativo', $arreglo);
        $this->assertArrayHasKey('hora_actual', $arreglo);
        $this->assertArrayHasKey('hora_apertura', $arreglo);
        $this->assertArrayHasKey('hora_cierre', $arreglo);
        $this->assertArrayHasKey('mensaje', $arreglo);
        $this->assertTrue($arreglo['operativo']);
    }

    /* =====================================================================
     * Casos de borde y manejo defensivo de errores
     * ===================================================================== */

    #[Test]
    public function evaluacion_lanza_excepcion_si_la_hora_de_consulta_es_invalida(): void
    {
        $sitio = $this->crearSitio();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('hora de consulta');

        $this->service->evaluarEstadoOperatividad($sitio, '25:00');
    }

    #[Test]
    public function evaluacion_lanza_excepcion_si_la_hora_de_consulta_tiene_formato_incorrecto(): void
    {
        $sitio = $this->crearSitio();

        $this->expectException(InvalidArgumentException::class);

        $this->service->evaluarEstadoOperatividad($sitio, '-10:00');
    }

    #[Test]
    public function evaluacion_lanza_excepcion_si_el_sitio_no_tiene_horario_registrado(): void
    {
        $sitio = $this->crearSitio();
        $sitio->setRawAttributes([
            'nombre' => 'Sitio sin horario',
            'direccion' => 'Cusco',
            'hora_apertura' => null,
            'hora_cierre' => null,
            'activo' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no tiene un horario de atención');

        $this->service->evaluarEstadoOperatividad($sitio, '10:00');
    }

    #[Test]
    public function evaluacion_lanza_excepcion_si_el_horario_registrado_es_incoherente(): void
    {
        // Simula datos corruptos en base de datos que eludieron la validación:
        // se usa setRawAttributes para omitir los mutators del modelo.
        $sitio = $this->crearSitio();
        $sitio->setRawAttributes([
            'nombre' => 'Sitio corrupto',
            'direccion' => 'Cusco',
            'hora_apertura' => '18:00',
            'hora_cierre' => '09:00',
            'activo' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('horario de atención');

        $this->service->evaluarEstadoOperatividad($sitio, '10:00');
    }

    #[Test]
    public function el_modelo_rechaza_horas_con_formato_invalido_al_persistir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('hora de apertura');

        $sitio = $this->crearSitio();
        $sitio->hora_apertura = '25:00';
    }

    #[Test]
    public function el_modelo_normaliza_horas_con_segundos_al_leer_de_base_de_datos(): void
    {
        $sitio = $this->crearSitio();
        $sitio->setRawAttributes([
            'nombre' => 'Machu Picchu',
            'direccion' => 'Urubamba',
            'hora_apertura' => '06:00:00',
            'hora_cierre' => '17:00:00',
            'activo' => true,
        ]);

        $this->assertSame('06:00', $sitio->hora_apertura);
        $this->assertSame('17:00', $sitio->hora_cierre);
        $this->assertSame('06:00 - 17:00', $sitio->horarioAtencion());
    }
}
