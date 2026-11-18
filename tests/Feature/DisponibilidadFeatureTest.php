<?php

/**
 * Capa de pruebas Feature HTTP/API de CUS-03.
 * Ejercita las rutas web y API reales con peticiones Laravel: valida formularios, sesión/redirects,
 * consultas a BD, respuesta HTML y JSON, sin navegador.
 * Comprueba que la integración entre FormRequest, controlador, servicio y modelos funcione de extremo a extremo.
 */

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

    /**
     * Propósito: Comprobar que la ruta GET entregue el formulario CUS-03 completo.
     * Escenario: GET /sitios/disponibilidad -> 200 con título, botón y campos hora_actual, apertura, cierre y sitio_id.
     * Importancia: Detecta vistas rotas o campos renombrados que dejarían al usuario sin formulario.
     */
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

    /**
     * Propósito: Validar el flujo POST manual ABIERTO y el resultado guardado en sesión.
     * Escenario: hora 10:00 con horario 09:00-17:00 -> redirect con resultado ABIERTO y origen manual.
     * Importancia: Previene pérdida del resultado entre redirect y vista, que dejaría la tarjeta vacía.
     */
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

    /**
     * Propósito: Verificar que el resultado de sesión se pinte en la tarjeta derecha.
     * Escenario: POST manual 10:00/09:00-17:00 siguiendo redirects -> ve 'ABIERTO' y '09:00 - 17:00'.
     * Importancia: Detecta desajustes entre el array de sesión y lo que Blade espera mostrar.
     */
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

    /**
     * Propósito: Confirmar el estado CERRADO en el HTML tras una consulta manual.
     * Escenario: hora 18:00 con horario 09:00-17:00 -> badge 'CERRADO'.
     * Importancia: Evita que el usuario vea ABIERTO cuando la hora ya pasó el cierre.
     */
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

    /**
     * Propósito: Probar las fronteras inclusivas del rango en el flujo HTTP.
     * Escenario: consultar a las 09:00 y a las 17:00 -> ambas ABIERTO.
     * Importancia: Previene errores de comparación estricta que dejarían fuera la hora exacta de cierre.
     */
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

    /**
     * Propósito: Verificar la validación del FormRequest para hora con formato inválido.
     * Escenario: hora '25:00' -> redirect de vuelta con error en hora_actual.
     * Importancia: Evita que peticiones mal formadas lleguen al servicio y lancen una excepción 500.
     */
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

    /**
     * Propósito: Cubrir el caso negativo en la validación HTTP de hora actual.
     * Escenario: hora '-10:00' -> error de sesión en hora_actual.
     * Importancia: Impide evaluaciones con datos imposibles desde el navegador o API.
     */
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

    /**
     * Propósito: Validar que cierre no pueda ser anterior a apertura en el formulario.
     * Escenario: apertura 18:00 y cierre 09:00 -> error en cierre.
     * Importancia: Previene guardar o evaluar horarios invertidos que darían estados falsos.
     */
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

    /**
     * Propósito: Asegurar que el modo manual requiera apertura y cierre si no hay sitio.
     * Escenario: solo hora_actual 10:00 -> errores en apertura y cierre.
     * Importancia: Evita evaluaciones con horario vacío que derivarían en excepciones del servicio.
     */
    #[Test]
    public function exige_las_horas_manuales_cuando_no_se_selecciona_un_sitio(): void
    {
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'hora_actual' => '10:00',
            ]);

        $response->assertSessionHasErrors(['apertura', 'cierre']);
    }

    /**
     * Propósito: Validar la existencia del sitio seleccionado antes de buscar su horario.
     * Escenario: sitio_id 999999 -> error en sitio_id.
     * Importancia: Previene consultas a registros inexistentes y mensajes confusos al usuario.
     */
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

    /**
     * Propósito: Evitar que el navegador imponga el resultado ABIERTO o CERRADO.
     * Escenario: POST con campo estado='ABIERTO' -> error de validación.
     * Importancia: Protege la regla de negocio de manipulación del cliente (mass assignment / spoofing).
     */
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

    /**
     * Propósito: Probar la ruta con sitio + fecha y su horario guardado para ese día.
     * Escenario: Qorikancha con horario 09:00-17:00 el 2026-11-10, consulta 12:30 -> ABIERTO con nombre y horario.
     * Importancia: Detecta fallos al resolver día de la semana, relaciones o zona horaria (America/Lima).
     */
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

    /**
     * Propósito: Manejar sitios activos pero sin horario para la fecha elegida.
     * Escenario: sitio activo sin HorarioAtencion el 2026-11-10 -> error 'disponibilidad'.
     * Importancia: Evita un error 500 al intentar evaluar con un horario inexistente.
     */
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

    /**
     * Propósito: Confirmar que un sitio inactivo no se evalúe aunque tenga horario.
     * Escenario: sitio factory inactivo con horario 09:00-17:00 -> error 'disponibilidad'.
     * Importancia: Impide mostrar ABIERTO para un sitio temporalmente cerrado.
     */
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

    /**
     * Propósito: Comprobar defensa ante horarios corruptos en BD.
     * Escenario: horario 18:00-09:00 para el día consultado -> error controlado, sin excepción 500.
     * Importancia: Evita que un dato inválido tumbe toda la página de disponibilidad.
     */
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

    /**
     * Propósito: Verificar relaciones Eloquent y casts del modelo HorarioAtencion.
     * Escenario: crear horario 09:00-17:00 para sitio -> pertenece al sitio, dia_semana=3 y horas 'HH:MM'.
     * Importancia: Previene que la consulta por día o la visualización del horario fallen por relaciones rotas.
     */
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

    /**
     * Propósito: Comprobar la restricción de unicidad en BD.
     * Escenario: dos HorarioAtencion para el mismo sitio y dia_semana=4 -> QueryException.
     * Importancia: Evita duplicidad de horarios que haría ambigua la evaluación del sitio.
     */
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

    /**
     * Propósito: Validar la respuesta JSON de la API con todas sus claves.
     * Escenario: POST /api/disponibilidad/evaluar con 10:00/09:00-17:00 -> 200, abierto true y estado ABIERTO.
     * Importancia: Asegura contrato estable para clientes externos o futuras apps móviles.
     */
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

    /**
     * Propósito: Verificar errores de validación en formato API.
     * Escenario: hora '25:00' -> 422 con error en hora_actual.
     * Importancia: Evita que consumidores de la API reciban excepciones en vez de errores estructurados.
     */
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

    /**
     * Propósito: Cubrir horario invertido en la API.
     * Escenario: apertura 18:00 y cierre 09:00 -> 422 con error en cierre.
     * Importancia: Mantiene la misma regla de negocio que el formulario web en todos los canales.
     */
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

    /**
     * Propósito: Confirmar error controlado de la API para sitio sin horario.
     * Escenario: sitio activo sin horario el 2026-11-10 -> 422 con errors.disponibilidad.
     * Importancia: Evita respuestas 500 y documenta el contrato de error para integraciones.
     */
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
