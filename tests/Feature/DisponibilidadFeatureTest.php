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
     * Given: La aplicación expone la ruta GET /sitios/disponibilidad sin datos previos.
     * When: Se solicita esa página con una petición GET.
     * Then: Se responde 200 con el título "Evaluación de estado de operatividad", el botón "Consultar disponibilidad" y los campos hora_actual, apertura, cierre y sitio_id.
     * Importancia: Detecta vistas rotas o campos renombrados que dejarían al usuario sin formulario.
     */
    #[Test]
    public function muestra_la_vista_interactiva_de_disponibilidad(): void
    {
        // Given: La aplicación está lista y no hay horarios ni sitios preparados.
        // When: Se solicita el formulario con GET /sitios/disponibilidad.
        $response = $this->get(route('sitios.disponibilidad.index'));

        // Then: La vista responde 200 con título, botón y los cuatro campos esperados.
        $response->assertOk();
        $response->assertSee('Evaluación de estado de operatividad');
        $response->assertSee('Consultar disponibilidad');
        $response->assertSee('name="hora_actual"', false);
        $response->assertSee('name="apertura"', false);
        $response->assertSee('name="cierre"', false);
        $response->assertSee('name="sitio_id"', false);
    }

    /**
     * Given: No se selecciona sitio y se dispone de la hora 10:00 con horario manual 09:00-17:00.
     * When: Se envía un POST a /sitios/disponibilidad/evaluar con esos tres datos.
     * Then: Se redirige a la vista y la sesión guarda resultado con abierto true, estado ABIERTO, hora_actual 10:00 y origen manual.
     * Importancia: Previene pérdida del resultado entre redirect y vista, que dejaría la tarjeta vacía.
     */
    #[Test]
    public function evalua_un_horario_manual_abierto_y_redirige_con_resultado(): void
    {
        // Given: El modo manual usa la hora 10:00 y el horario 09:00-17:00 sin sitio seleccionado.
        // When: Se envía el POST de evaluación con esos valores.
        $response = $this->post(route('sitios.disponibilidad.evaluar'), [
            'hora_actual' => '10:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        // Then: Se redirige al formulario y la sesión conserva el resultado ABIERTO de origen manual.
        $response->assertRedirect(route('sitios.disponibilidad.index'));
        $response->assertSessionHas('resultado', static function (array $resultado): bool {
            return $resultado['abierto'] === true
                && $resultado['estado'] === 'ABIERTO'
                && $resultado['hora_actual'] === '10:00'
                && $resultado['origen'] === 'manual';
        });
    }

    /**
     * Given: Existe un horario manual 09:00-17:00 y la hora actual es 10:00.
     * When: Se envía el POST de evaluación siguiendo los redirects.
     * Then: La vista responde 200 y muestra el badge ABIERTO junto al rango 09:00 - 17:00.
     * Importancia: Detecta desajustes entre el array de sesión y lo que Blade espera mostrar.
     */
    #[Test]
    public function muestra_el_badge_abierto_en_la_vista_tras_la_evaluacion(): void
    {
        // Given: El horario manual es 09:00-17:00 y la hora consultada es 10:00.
        // When: Se evalúa siguiendo los redirects para llegar hasta la vista final.
        $response = $this->followingRedirects()->post(route('sitios.disponibilidad.evaluar'), [
            'hora_actual' => '10:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        // Then: La tarjeta derecha muestra ABIERTO y el texto 09:00 - 17:00.
        $response->assertOk();
        $response->assertSee('ABIERTO');
        $response->assertSee('09:00 - 17:00');
    }

    /**
     * Given: El horario manual es 09:00-17:00 y la hora actual ya pasó el cierre: 18:00.
     * When: Se envía el POST de evaluación siguiendo los redirects.
     * Then: La vista responde 200 y muestra el badge CERRADO.
     * Importancia: Evita que el usuario vea ABIERTO cuando la hora ya pasó el cierre.
     */
    #[Test]
    public function evalua_un_horario_manual_fuera_de_rango_como_cerrado(): void
    {
        // Given: Se usa la hora 18:00 frente al horario 09:00-17:00.
        // When: Se evalúa manualmente siguiendo los redirects hasta la vista.
        $response = $this->followingRedirects()->post(route('sitios.disponibilidad.evaluar'), [
            'hora_actual' => '18:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        // Then: La vista responde 200 con el badge CERRADO.
        $response->assertOk();
        $response->assertSee('CERRADO');
    }

    /**
     * Given: El horario manual es 09:00-17:00 para probar las fronteras inclusivas del rango.
     * When: Se evalúan dos POST independientes con hora_actual 09:00 y 17:00.
     * Then: Ambas respuestas guardan en sesión resultado.abierto en true.
     * Importancia: Previene errores de comparación estricta que dejarían fuera la hora exacta de cierre.
     */
    #[Test]
    public function acepta_las_horas_exactas_de_apertura_y_cierre(): void
    {
        // Given: El rango compartido para ambas consultas es 09:00-17:00.
        // When: Se evalúa la hora exacta de apertura, 09:00.
        $apertura = $this->post(route('sitios.disponibilidad.evaluar'), [
            'hora_actual' => '09:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        // Then: La sesión confirma que está abierto a las 09:00.
        $apertura->assertSessionHas('resultado.abierto', true);

        // When: Se evalúa la hora exacta de cierre, 17:00.
        $cierre = $this->post(route('sitios.disponibilidad.evaluar'), [
            'hora_actual' => '17:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        // Then: La sesión confirma que también está abierto a las 17:00.
        $cierre->assertSessionHas('resultado.abierto', true);
    }

    /**
     * Given: El formulario web usa el horario manual 09:00-17:00 y se llega desde /sitios/disponibilidad.
     * When: Se envía un POST con hora_actual '25:00', apertura '09:00' y cierre '17:00'.
     * Then: Se redirige al formulario y la sesión registra un error en hora_actual.
     * Importancia: Evita que peticiones mal formadas lleguen al servicio y lancen una excepción 500.
     */
    #[Test]
    public function rechaza_una_hora_actual_con_formato_invalido(): void
    {
        // Given: Se parte del formulario con horario manual 09:00-17:00.
        // When: Se envía la hora inválida '25:00'.
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'hora_actual' => '25:00',
                'apertura' => '09:00',
                'cierre' => '17:00',
            ]);

        // Then: Se vuelve al formulario y la sesión marca el error de hora_actual.
        $response->assertRedirect(route('sitios.disponibilidad.index'));
        $response->assertSessionHasErrors('hora_actual');
    }

    /**
     * Given: El formulario web parte del horario manual 09:00-17:00.
     * When: Se envía un POST con la hora imposible '-10:00'.
     * Then: La sesión registra un error en hora_actual y la evaluación no continúa.
     * Importancia: Impide evaluaciones con datos imposibles desde el navegador o API.
     */
    #[Test]
    public function rechaza_una_hora_actual_negativa(): void
    {
        // Given: Se usa el horario manual válido 09:00-17:00 como contexto.
        // When: Se envía una hora con valor negativo: '-10:00'.
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'hora_actual' => '-10:00',
                'apertura' => '09:00',
                'cierre' => '17:00',
            ]);

        // Then: La sesión conserva el error de validación en hora_actual.
        $response->assertSessionHasErrors('hora_actual');
    }

    /**
     * Given: El formulario recibe una hora actual 10:00 y un horario invertido con apertura 18:00 y cierre 09:00.
     * When: Se envía ese POST desde /sitios/disponibilidad.
     * Then: Se redirige al formulario y la sesión registra un error en cierre.
     * Importancia: Previene guardar o evaluar horarios invertidos que darían estados falsos.
     */
    #[Test]
    public function rechaza_un_horario_manual_incoherente(): void
    {
        // Given: El horario manual está invertido: apertura 18:00 y cierre 09:00.
        // When: Se envía la evaluación con hora_actual 10:00.
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'hora_actual' => '10:00',
                'apertura' => '18:00',
                'cierre' => '09:00',
            ]);

        // Then: Se vuelve al formulario y la sesión marca el error en cierre.
        $response->assertRedirect(route('sitios.disponibilidad.index'));
        $response->assertSessionHasErrors('cierre');
    }

    /**
     * Given: No se selecciona ningún sitio y solo se conoce la hora actual 10:00.
     * When: Se envía un POST con únicamente hora_actual 10:00.
     * Then: La sesión registra errores en apertura y cierre.
     * Importancia: Evita evaluaciones con horario vacío que derivarían en excepciones del servicio.
     */
    #[Test]
    public function exige_las_horas_manuales_cuando_no_se_selecciona_un_sitio(): void
    {
        // Given: No hay sitio elegido ni horario manual completo.
        // When: Se envía solo la hora actual 10:00.
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'hora_actual' => '10:00',
            ]);

        // Then: La validación exige los campos apertura y cierre.
        $response->assertSessionHasErrors(['apertura', 'cierre']);
    }

    /**
     * Given: No existe ningún sitio con el identificador 999999 y la hora consultada es 10:00.
     * When: Se envía un POST con sitio_id 999999 y hora_actual 10:00.
     * Then: La sesión registra un error en sitio_id antes de buscar horarios.
     * Importancia: Previene consultas a registros inexistentes y mensajes confusos al usuario.
     */
    #[Test]
    public function rechaza_un_sitio_inexistente(): void
    {
        // Given: El sitio 999999 no está registrado en la base de datos.
        // When: Se intenta evaluar la hora 10:00 usando ese sitio_id.
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'sitio_id' => 999999,
                'hora_actual' => '10:00',
            ]);

        // Then: La validación rechaza el sitio con un error en sitio_id.
        $response->assertSessionHasErrors('sitio_id');
    }

    /**
     * Given: El cliente intenta imponer el resultado con el campo estado='ABIERTO' junto al horario 10:00/09:00-17:00.
     * When: Se envía ese POST desde el formulario web.
     * Then: La sesión registra un error en estado y el servidor calcula la operatividad.
     * Importancia: Protege la regla de negocio de manipulación del cliente (mass assignment / spoofing).
     */
    #[Test]
    public function rechaza_un_estado_enviado_por_el_cliente(): void
    {
        // Given: El cliente añade el campo manipulado estado='ABIERTO'.
        // When: Se envía la evaluación con hora 10:00 y horario 09:00-17:00.
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'hora_actual' => '10:00',
                'apertura' => '09:00',
                'cierre' => '17:00',
                'estado' => 'ABIERTO',
            ]);

        // Then: La validación rechaza el campo estado enviado por el cliente.
        $response->assertSessionHasErrors('estado');
    }

    /**
     * Given: Existe Qorikancha de prueba activo con un HorarioAtencion 09:00-17:00 para el día de 2026-11-10 en America/Lima.
     * When: Se consulta con POST siguiendo redirects usando sitio_id, fecha 2026-11-10 y hora_actual 12:30.
     * Then: La vista responde 200 y muestra ABIERTO, "Qorikancha de prueba" y "09:00 - 17:00".
     * Importancia: Detecta fallos al resolver día de la semana, relaciones o zona horaria (America/Lima).
     */
    #[Test]
    public function evalua_la_operatividad_utilizando_el_horario_del_sitio(): void
    {
        // Given: Se prepara la fecha 2026-11-10, el sitio activo y su horario de ese día.
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

        // When: Se evalúa la operatividad del sitio a las 12:30 siguiendo redirects.
        $response = $this->followingRedirects()->post(route('sitios.disponibilidad.evaluar'), [
            'sitio_id' => $sitio->id,
            'fecha' => $fecha,
            'hora_actual' => '12:30',
        ]);

        // Then: La vista muestra ABIERTO con el nombre del sitio y su horario.
        $response->assertOk();
        $response->assertSee('ABIERTO');
        $response->assertSee('Qorikancha de prueba');
        $response->assertSee('09:00 - 17:00');
    }

    /**
     * Given: Existe un sitio activo sin HorarioAtencion para la fecha 2026-11-10.
     * When: Se envía un POST desde el formulario con sitio_id, fecha 2026-11-10 y hora_actual 12:30.
     * Then: Se redirige al índice y la sesión registra un error en disponibilidad, sin llegar a 500.
     * Importancia: Evita un error 500 al intentar evaluar con un horario inexistente.
     */
    #[Test]
    public function rechaza_un_sitio_sin_horario_para_el_dia_solicitado(): void
    {
        // Given: Se crea un sitio activo sin horarios guardados.
        $sitio = Sitio::factory()->create(['activo' => true]);

        // When: Se consulta ese sitio para el 2026-11-10 a las 12:30.
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'sitio_id' => $sitio->id,
                'fecha' => '2026-11-10',
                'hora_actual' => '12:30',
            ]);

        // Then: Se redirige al formulario y la sesión explica la falta de disponibilidad.
        $response->assertRedirect(route('sitios.disponibilidad.index'));
        $response->assertSessionHasErrors('disponibilidad');
    }

    /**
     * Given: Existe un sitio inactivo que sí tiene un horario 09:00-17:00 registrado.
     * When: Se consulta ese sitio con fecha 2026-11-10 y hora_actual 12:30.
     * Then: La sesión registra un error en disponibilidad aunque el horario exista.
     * Importancia: Impide mostrar ABIERTO para un sitio temporalmente cerrado.
     */
    #[Test]
    public function rechaza_un_sitio_inactivo(): void
    {
        // Given: Se crea un sitio inactivo y su horario 09:00-17:00.
        $sitio = Sitio::factory()->inactivo()->create();

        HorarioAtencion::factory()->create([
            'sitio_id' => $sitio->id,
            'dia_semana' => 2,
            'apertura' => '09:00',
            'cierre' => '17:00',
            'activo' => true,
        ]);

        // When: Se intenta evaluar su operatividad para el 2026-11-10 a las 12:30.
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'sitio_id' => $sitio->id,
                'fecha' => '2026-11-10',
                'hora_actual' => '12:30',
            ]);

        // Then: La validación informa que el sitio no está disponible.
        $response->assertSessionHasErrors('disponibilidad');
    }

    /**
     * Given: Existe un sitio activo con un horario corrupto 18:00-09:00 para el día de 2026-11-10.
     * When: Se consulta ese sitio con fecha 2026-11-10 y hora_actual 12:30.
     * Then: Se redirige al índice y la sesión registra un error en disponibilidad, sin excepción 500.
     * Importancia: Evita que un dato inválido tumbe toda la página de disponibilidad.
     */
    #[Test]
    public function rechaza_datos_persistidos_incoherentes_sin_romper_el_flujo(): void
    {
        // Given: Se prepara la fecha, el sitio activo y un horario persistido invertido.
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

        // When: Se evalúa la operatividad del sitio a las 12:30.
        $response = $this->from(route('sitios.disponibilidad.index'))
            ->post(route('sitios.disponibilidad.evaluar'), [
                'sitio_id' => $sitio->id,
                'fecha' => $fecha,
                'hora_actual' => '12:30',
            ]);

        // Then: Se redirige y se muestra un error controlado en disponibilidad.
        $response->assertRedirect(route('sitios.disponibilidad.index'));
        $response->assertSessionHasErrors('disponibilidad');
    }

    /**
     * Given: Existe un sitio y se registra un HorarioAtencion con dia_semana 3, apertura 09:00, cierre 17:00 y activo true.
     * When: Se persiste el horario y se consultan sus relaciones y valores desde Eloquent.
     * Then: Pertenece al sitio, conserva dia_semana=3 y horas 'HH:MM', está activo y aparece en $sitio->horarios.
     * Importancia: Previene que la consulta por día o la visualización del horario fallen por relaciones rotas.
     */
    #[Test]
    public function relaciona_horarios_con_su_sitio_y_normaliza_el_formato_de_hora(): void
    {
        // Given: Se crea el sitio que será dueño del horario.
        $sitio = Sitio::factory()->create();

        // When: Se persiste el horario 09:00-17:00 del día 3 asociado a ese sitio.
        $horario = HorarioAtencion::factory()->create([
            'sitio_id' => $sitio->id,
            'dia_semana' => 3,
            'apertura' => '09:00',
            'cierre' => '17:00',
            'activo' => true,
        ]);

        // Then: La relación, el día, las horas y la colección de horarios son correctos.
        $this->assertInstanceOf(Sitio::class, $horario->sitio);
        $this->assertSame($sitio->id, $horario->sitio->id);
        $this->assertSame(3, $horario->dia_semana);
        $this->assertSame('09:00', $horario->apertura);
        $this->assertSame('17:00', $horario->cierre);
        $this->assertTrue($horario->activo);
        $this->assertTrue($sitio->horarios->contains($horario));
    }

    /**
     * Given: Existe un sitio sin horarios duplicados para el día 4.
     * When: Se crea un primer HorarioAtencion y luego un segundo con el mismo sitio y dia_semana 4.
     * Then: Se lanza QueryException por la restricción de unicidad de la base de datos.
     * Importancia: Evita duplicidad de horarios que haría ambigua la evaluación del sitio.
     */
    #[Test]
    public function impide_dos_horarios_para_el_mismo_sitio_y_dia(): void
    {
        // Then: La restricción única debe provocar QueryException.
        $this->expectException(QueryException::class);

        // Given: Se crea el sitio que tendrá los horarios.
        $sitio = Sitio::factory()->create();

        // Given: Se registra el primer horario con dia_semana 4.
        HorarioAtencion::factory()->create([
            'sitio_id' => $sitio->id,
            'dia_semana' => 4,
        ]);

        // When: Se intenta registrar un segundo horario igual para el mismo sitio y día.
        HorarioAtencion::factory()->create([
            'sitio_id' => $sitio->id,
            'dia_semana' => 4,
        ]);
    }

    /**
     * Given: La API está disponible y se usa la hora 10:00 con horario manual 09:00-17:00.
     * When: Se envía un POST JSON a /api/disponibilidad/evaluar con esos datos.
     * Then: Se responde 200 con abierto true, estado ABIERTO, hora_actual 10:00 y la estructura JSON completa.
     * Importancia: Asegura contrato estable para clientes externos o futuras apps móviles.
     */
    #[Test]
    public function responde_json_desde_la_api_con_el_estado_calculado(): void
    {
        // Given: No se necesita sitio: el horario manual es 09:00-17:00 y la hora es 10:00.
        // When: Se consulta el estado calculado mediante POST JSON.
        $response = $this->postJson(route('api.disponibilidad.evaluar'), [
            'hora_actual' => '10:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        // Then: El JSON responde 200 con estado ABIERTO y todas sus claves.
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
     * Given: La API recibe un horario manual 09:00-17:00 y una hora imposible '25:00'.
     * When: Se envía un POST JSON a /api/disponibilidad/evaluar con esa hora.
     * Then: Se responde 422 con un error estructurado en hora_actual.
     * Importancia: Evita que consumidores de la API reciban excepciones en vez de errores estructurados.
     */
    #[Test]
    public function la_api_responde_422_ante_datos_invalidos(): void
    {
        // Given: El horario base es 09:00-17:00 y la hora enviada será inválida.
        // When: Se envía '25:00' en el cuerpo JSON.
        $response = $this->postJson(route('api.disponibilidad.evaluar'), [
            'hora_actual' => '25:00',
            'apertura' => '09:00',
            'cierre' => '17:00',
        ]);

        // Then: La API responde 422 y señala el campo hora_actual.
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('hora_actual');
    }

    /**
     * Given: La API recibe un horario invertido con apertura 18:00 y cierre 09:00 para la hora 10:00.
     * When: Se envía un POST JSON a /api/disponibilidad/evaluar con ese horario.
     * Then: Se responde 422 con un error estructurado en cierre.
     * Importancia: Mantiene la misma regla de negocio que el formulario web en todos los canales.
     */
    #[Test]
    public function la_api_responde_422_ante_un_horario_incoherente(): void
    {
        // Given: El horario está invertido: apertura 18:00 y cierre 09:00.
        // When: Se consulta la API con hora_actual 10:00 y ese horario.
        $response = $this->postJson(route('api.disponibilidad.evaluar'), [
            'hora_actual' => '10:00',
            'apertura' => '18:00',
            'cierre' => '09:00',
        ]);

        // Then: La API responde 422 y señala el campo cierre.
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('cierre');
    }

    /**
     * Given: Existe un sitio activo sin horario registrado para el 2026-11-10.
     * When: Se envía un POST JSON con sitio_id, fecha 2026-11-10 y hora_actual 10:00.
     * Then: Se responde 422 con errors.disponibilidad, sin llegar a un 500.
     * Importancia: Evita respuestas 500 y documenta el contrato de error para integraciones.
     */
    #[Test]
    public function la_api_responde_422_ante_un_sitio_sin_horario(): void
    {
        // Given: Se crea un sitio activo sin HorarioAtencion.
        $sitio = Sitio::factory()->create(['activo' => true]);

        // When: Se consulta su disponibilidad para el 2026-11-10 a las 10:00.
        $response = $this->postJson(route('api.disponibilidad.evaluar'), [
            'sitio_id' => $sitio->id,
            'fecha' => '2026-11-10',
            'hora_actual' => '10:00',
        ]);

        // Then: La API responde 422 con el error controlado en disponibilidad.
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['disponibilidad']]);
    }
}
