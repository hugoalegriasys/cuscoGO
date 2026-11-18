<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HorarioAtencion;
use App\Models\Sitio;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DisponibilidadFeatureTest extends TestCase
{
    use RefreshDatabase;

    private const ZONA_HORARIA = 'America/Lima';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    #[Test]
    public function muestra_la_vista_interactiva_de_disponibilidad(): void
    {
        $response = $this->get(route('sitios.disponibilidad.index'));

        $response->assertOk();
        $response->assertSee('Evaluación de estado de operatividad');
        $response->assertSee('Consultar disponibilidad');
        $response->assertSee('name="hora_actual"', false);
        $response->assertSee('name="apertura"', false);
        $response->assertSee('name="cierre"', false);
        $response->assertSee('name="sitio_id"', false);
    }

    #[Test]
    public function evalua_un_horario_manual_abierto_y_redirige_con_resultado(): void
    {
        $response = $this->post(route('sitios.disponibilidad.evaluar'), [
            'hora_actual' => '10:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        $response->assertRedirect(route('sitios.disponibilidad.index'));
        $response->assertSessionHas('resultado', static function (array $resultado): bool {
            return $resultado['abierto'] === true
                && $resultado['estado'] === 'ABIERTO'
                && $resultado['hora_actual'] === '10:00'
                && $resultado['origen'] === 'manual';
        });
    }

    #[Test]
    public function muestra_el_badge_abierto_en_la_vista_tras_la_evaluacion(): void
    {
        $response = $this->followingRedirects()->post(route('sitios.disponibilidad.evaluar'), [
            'hora_actual' => '10:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        $response->assertOk();
        $response->assertSee('ABIERTO');
        $response->assertSee('09:00 - 17:00');
    }

    #[Test]
    public function evalua_un_horario_manual_fuera_de_rango_como_cerrado(): void
    {
        $response = $this->followingRedirects()->post(route('sitios.disponibilidad.evaluar'), [
            'hora_actual' => '18:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        $response->assertOk();
        $response->assertSee('CERRADO');
    }

    #[Test]
    public function acepta_las_horas_exactas_de_apertura_y_cierre(): void
    {
        $apertura = $this->post(route('sitios.disponibilidad.evaluar'), [
            'hora_actual' => '09:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);
        $apertura->assertSessionHas('resultado.abierto', true);

        $cierre = $this->post(route('sitios.disponibilidad.evaluar'), [
            'hora_actual' => '17:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);
        $cierre->assertSessionHas('resultado.abierto', true);
    }

    #[Test]
    public function rechaza_una_hora_actual_con_formato_invalido(): void
    {
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'hora_actual' => '25:00',
                'apertura' => '09:00',
                'cierre' => '17:00',
            ]);

        $response->assertRedirect(route('sitios.disponibilidad.index'));
        $response->assertSessionHasErrors('hora_actual');
    }

    #[Test]
    public function rechaza_una_hora_actual_negativa(): void
    {
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'hora_actual' => '-10:00',
                'apertura' => '09:00',
                'cierre' => '17:00',
            ]);

        $response->assertSessionHasErrors('hora_actual');
    }

    #[Test]
    public function rechaza_un_horario_manual_incoherente(): void
    {
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'hora_actual' => '10:00',
                'apertura' => '18:00',
                'cierre' => '09:00',
            ]);

        $response->assertRedirect(route('sitios.disponibilidad.index'));
        $response->assertSessionHasErrors('cierre');
    }

    #[Test]
    public function exige_las_horas_manuales_cuando_no_se_selecciona_un_sitio(): void
    {
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'hora_actual' => '10:00',
            ]);

        $response->assertSessionHasErrors(['apertura', 'cierre']);
    }

    #[Test]
    public function rechaza_un_sitio_inexistente(): void
    {
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'sitio_id' => 999999,
                'hora_actual' => '10:00',
            ]);

        $response->assertSessionHasErrors('sitio_id');
    }

    #[Test]
    public function rechaza_un_estado_enviado_por_el_cliente(): void
    {
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'hora_actual' => '10:00',
                'apertura' => '09:00',
                'cierre' => '17:00',
                'estado' => 'ABIERTO',
            ]);

        $response->assertSessionHasErrors('estado');
    }

    #[Test]
    public function evalua_la_operatividad_utilizando_el_horario_del_sitio(): void
    {
        $fecha = '2026-11-10';
        $diaSemana = CarbonImmutable::createFromFormat('!Y-m-d', $fecha, self::ZONA_HORARIA)->dayOfWeekIso;

        $sitio = Sitio::factory()->create([
            'nombre' => 'Qorikancha de prueba',
            'activo' => true,
        ]);

        HorarioAtencion::factory()->create([
            'sitio_id' => $sitio->id,
            'dia_semana' => $diaSemana,
            'apertura' => '09:00',
            'cierre' => '17:00',
            'activo' => true,
        ]);

        $response = $this->followingRedirects()->post(route('sitios.disponibilidad.evaluar'), [
            'sitio_id' => $sitio->id,
            'fecha' => $fecha,
            'hora_actual' => '12:30',
        ]);

        $response->assertOk();
        $response->assertSee('ABIERTO');
        $response->assertSee('Qorikancha de prueba');
        $response->assertSee('09:00 - 17:00');
    }

    #[Test]
    public function rechaza_un_sitio_sin_horario_para_el_dia_solicitado(): void
    {
        $sitio = Sitio::factory()->create(['activo' => true]);

        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'sitio_id' => $sitio->id,
                'fecha' => '2026-11-10',
                'hora_actual' => '12:30',
            ]);

        $response->assertRedirect(route('sitios.disponibilidad.index'));
        $response->assertSessionHasErrors('disponibilidad');
    }

    #[Test]
    public function rechaza_un_sitio_inactivo(): void
    {
        $sitio = Sitio::factory()->inactivo()->create();

        HorarioAtencion::factory()->create([
            'sitio_id' => $sitio->id,
            'dia_semana' => 2,
            'apertura' => '09:00',
            'cierre' => '17:00',
            'activo' => true,
        ]);

        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'sitio_id' => $sitio->id,
                'fecha' => '2026-11-10',
                'hora_actual' => '12:30',
            ]);

        $response->assertSessionHasErrors('disponibilidad');
    }

    #[Test]
    public function rechaza_datos_persistidos_incoherentes_sin_romper_el_flujo(): void
    {
        $fecha = '2026-11-10';
        $diaSemana = CarbonImmutable::createFromFormat('!Y-m-d', $fecha, self::ZONA_HORARIA)->dayOfWeekIso;

        $sitio = Sitio::factory()->create(['activo' => true]);

        HorarioAtencion::factory()->create([
            'sitio_id' => $sitio->id,
            'dia_semana' => $diaSemana,
            'apertura' => '18:00',
            'cierre' => '09:00',
            'activo' => true,
        ]);

        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'sitio_id' => $sitio->id,
                'fecha' => $fecha,
                'hora_actual' => '12:30',
            ]);

        $response->assertRedirect(route('sitios.disponibilidad.index'));
        $response->assertSessionHasErrors('disponibilidad');
    }

    #[Test]
    public function relaciona_horarios_con_su_sitio_y_normaliza_el_formato_de_hora(): void
    {
        $sitio = Sitio::factory()->create();

        $horario = HorarioAtencion::factory()->create([
            'sitio_id' => $sitio->id,
            'dia_semana' => 3,
            'apertura' => '09:00',
            'cierre' => '17:00',
            'activo' => true,
        ]);

        $this->assertInstanceOf(Sitio::class, $horario->sitio);
        $this->assertSame($sitio->id, $horario->sitio->id);
        $this->assertSame(3, $horario->dia_semana);
        $this->assertSame('09:00', $horario->apertura);
        $this->assertSame('17:00', $horario->cierre);
        $this->assertTrue($horario->activo);
        $this->assertTrue($sitio->horarios->contains($horario));
    }

    #[Test]
    public function impide_dos_horarios_para_el_mismo_sitio_y_dia(): void
    {
        $this->expectException(QueryException::class);

        $sitio = Sitio::factory()->create();

        HorarioAtencion::factory()->create([
            'sitio_id' => $sitio->id,
            'dia_semana' => 4,
        ]);

        HorarioAtencion::factory()->create([
            'sitio_id' => $sitio->id,
            'dia_semana' => 4,
        ]);
    }

    #[Test]
    public function responde_json_desde_la_api_con_el_estado_calculado(): void
    {
        $response = $this->postJson(route('api.disponibilidad.evaluar'), [
            'hora_actual' => '10:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        $response->assertOk();
        $response->assertJsonPath('abierto', true);
        $response->assertJsonPath('estado', 'ABIERTO');
        $response->assertJsonPath('hora_actual', '10:00');
        $response->assertJsonStructure([
            'abierto',
            'estado',
            'mensaje',
            'hora_actual',
            'apertura',
            'cierre',
            'horario',
            'sitio',
            'fecha',
            'dia_semana',
            'dia_semana_nombre',
            'origen',
        ]);
    }

    #[Test]
    public function la_api_responde_422_ante_datos_invalidos(): void
    {
        $response = $this->postJson(route('api.disponibilidad.evaluar'), [
            'hora_actual' => '25:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('hora_actual');
    }

    #[Test]
    public function la_api_responde_422_ante_un_horario_incoherente(): void
    {
        $response = $this->postJson(route('api.disponibilidad.evaluar'), [
            'hora_actual' => '10:00',
            'apertura' => '18:00',
            'cierre' => '09:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('cierre');
    }

    #[Test]
    public function la_api_responde_422_ante_un_sitio_sin_horario(): void
    {
        $sitio = Sitio::factory()->create(['activo' => true]);

        $response = $this->postJson(route('api.disponibilidad.evaluar'), [
            'sitio_id' => $sitio->id,
            'fecha' => '2026-11-10',
            'hora_actual' => '10:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['disponibilidad']]);
    }
}
