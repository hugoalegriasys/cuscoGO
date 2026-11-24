<?php

/**
 * Capa de pruebas Feature HTTP de CUS-03 para el catálogo de sitios patrimoniales.
 * Recorre el CRUD completo, el estado calculado en listado/detalle y el endpoint AJAX /sitios/{sitio}/verificar
 * contra rutas, validación, base de datos y vistas reales, sin navegador.
 * Detecta desajustes entre contrato, controlador, DTO y respuesta JSON antes del despliegue.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SitioPatrimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de integración HTTP del flujo completo de CUS-03:
 * petición, validación, base de datos, servicio y respuesta de la vista.
 */
class SitioPatrimonialControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $datosValidos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->datosValidos = [
            'nombre' => 'Qorikancha',
            'direccion' => 'Calle Santo Domingo, Cusco',
            'descripcion' => 'Templo del Sol de los incas.',
            'hora_apertura' => '09:00',
            'hora_cierre' => '17:00',
            'activo' => true,
        ];
    }

    /* =====================================================================
     * GET /sitios (index)
     * ===================================================================== */

    /**
     * Given: existen dos sitios registrados, Qorikancha con horario 00:00-23:59 y Sacsayhuamán con 00:00-00:01.
     * When: se solicita GET /sitios para ver el catálogo.
     * Then: responde 200 con la vista 'sitios.index', ambos nombres y el texto "Verificar estado".
     * Importancia: Detecta fallos al evaluar estados en index o al renderizar la vista.
     */
    #[Test]
    public function puede_listar_los_sitios_patrimoniales_con_su_estado(): void
    {
        // Given: Qorikancha 00:00-23:59 y Sacsayhuamán 00:00-00:01 ya están en la base de datos.
        SitioPatrimonial::factory()->create([
            'nombre' => 'Qorikancha',
            'hora_apertura' => '00:00',
            'hora_cierre' => '23:59',
        ]);
        SitioPatrimonial::factory()->create([
            'nombre' => 'Sacsayhuamán',
            'hora_apertura' => '00:00',
            'hora_cierre' => '00:01',
        ]);

        // When: se envía GET /sitios.
        $respuesta = $this->get(route('sitios.index'));

        // Then: el listado responde 200 y muestra ambos nombres y "Verificar estado".
        $respuesta->assertOk();
        $respuesta->assertViewIs('sitios.index');
        $respuesta->assertViewHas('sitios');
        $respuesta->assertSeeText('Qorikancha');
        $respuesta->assertSeeText('Sacsayhuamán');
        $respuesta->assertSee('Verificar estado');
    }

    /**
     * Given: existe el sitio 'Salineras de Maras' con activo=false.
     * When: se solicita GET /sitios.
     * Then: la respuesta es 200 y el HTML contiene 'inactivo'.
     * Importancia: Evita que un sitio cerrado aparezca como abierto por su horario.
     */
    #[Test]
    public function el_listado_muestra_estado_de_sitio_inactivo(): void
    {
        // Given: 'Salineras de Maras' está registrado como sitio inactivo.
        SitioPatrimonial::factory()->create([
            'nombre' => 'Salineras de Maras',
            'activo' => false,
        ]);

        // When: se envía GET /sitios.
        $respuesta = $this->get(route('sitios.index'));

        // Then: el HTML del listado incluye el estado 'inactivo'.
        $respuesta->assertOk();
        $respuesta->assertSeeText('inactivo');
    }

    /**
     * Given: la base de datos no tiene sitios registrados.
     * When: se solicita GET /sitios.
     * Then: responde 200 con 'Aún no hay sitios patrimoniales registrados'.
     * Importancia: Previene pantallas en blanco o errores al recorrer una colección vacía.
     */
    #[Test]
    public function el_listado_muestra_mensaje_cuando_no_existen_sitios(): void
    {
        // Given: no hay sitios en la base de datos.
        // When: se envía GET /sitios.
        $respuesta = $this->get(route('sitios.index'));

        // Then: se muestra el mensaje de catálogo vacío con HTTP 200.
        $respuesta->assertOk();
        $respuesta->assertSeeText('Aún no hay sitios patrimoniales registrados');
    }

    /* =====================================================================
     * POST /sitios (store)
     * ===================================================================== */

    /**
     * Given: se dispone de datos válidos para Qorikancha, con horario 09:00-17:00, y no hay registros previos.
     * When: se envía POST /sitios con esos datos.
     * Then: redirige al detalle del sitio creado, deja 'exito' en sesión y guarda Qorikancha 09:00-17:00 en BD.
     * Importancia: Detecta fallos de validación, asignación masiva o persistencia del horario.
     */
    #[Test]
    public function puede_registrar_un_sitio_patrimonial_valido(): void
    {
        // Given: los datos válidos de Qorikancha 09:00-17:00 están listos para registrarse.
        // When: se envía POST /sitios con $this->datosValidos.
        $respuesta = $this->post(route('sitios.store'), $this->datosValidos);

        $sitio = SitioPatrimonial::query()->where('nombre', 'Qorikancha')->first();

        // Then: la respuesta redirige al detalle, hay sesión 'exito' y la fila quedó guardada.
        $this->assertNotNull($sitio);
        $respuesta->assertRedirect(route('sitios.show', $sitio));
        $respuesta->assertSessionHas('exito');

        $this->assertDatabaseHas('sitios_patrimoniales', [
            'nombre' => 'Qorikancha',
            'hora_apertura' => '09:00',
            'hora_cierre' => '17:00',
        ]);
    }

    /**
     * Given: no hay sitios registrados y se parte de los datos válidos de Qorikancha.
     * When: se envía POST /sitios con hora_apertura 18:00 y hora_cierre 09:00.
     * Then: hay error en hora_cierre, redirige a 'sitios.create' y no se crea ninguna fila.
     * Importancia: Impide guardar horarios imposibles que luego darían estados falsos.
     */
    #[Test]
    public function rechaza_registro_con_horario_incoherente(): void
    {
        // Given: se parte de los datos válidos, sin sitios guardados todavía.
        // When: se envía POST /sitios con el horario incoherente 18:00-09:00.
        $respuesta = $this->from(route('sitios.create'))
            ->post(route('sitios.store'), array_merge($this->datosValidos, [
                'hora_apertura' => '18:00',
                'hora_cierre' => '09:00',
            ]));

        // Then: la validación marca hora_cierre, redirige al formulario y la BD sigue vacía.
        $respuesta->assertSessionHasErrors('hora_cierre');
        $respuesta->assertRedirect(route('sitios.create'));

        $this->assertDatabaseMissing('sitios_patrimoniales', [
            'nombre' => 'Qorikancha',
        ]);
    }

    /**
     * Given: la base de datos está vacía de sitios.
     * When: se envía POST /sitios con nombre, direccion, hora_apertura y hora_cierre en ''.
     * Then: hay errores en los cuatro campos y la tabla 'sitios_patrimoniales' queda con 0 filas.
     * Importancia: Evita registros incompletos que romperían la evaluación de operatividad.
     */
    #[Test]
    public function rechaza_registro_con_campos_vacios(): void
    {
        // Given: no hay sitios registrados antes de enviar el formulario.
        // When: se envía POST /sitios con los cuatro campos obligatorios vacíos.
        $respuesta = $this->from(route('sitios.create'))
            ->post(route('sitios.store'), [
                'nombre' => '',
                'direccion' => '',
                'hora_apertura' => '',
                'hora_cierre' => '',
            ]);

        // Then: los cuatro campos muestran error y la BD permanece vacía.
        $respuesta->assertSessionHasErrors(['nombre', 'direccion', 'hora_apertura', 'hora_cierre']);

        $this->assertDatabaseCount('sitios_patrimoniales', 0);
    }

    /**
     * Given: no hay sitios registrados y se parte de los datos válidos de Qorikancha.
     * When: se envía POST /sitios con hora_apertura '25:00'.
     * Then: hay error en hora_apertura y la BD sigue vacía.
     * Importancia: Previene horas inexistentes desde el formulario web.
     */
    #[Test]
    public function rechaza_registro_con_hora_con_formato_invalido(): void
    {
        // Given: se parte de los datos válidos, sin sitios guardados.
        // When: se envía POST /sitios con la hora de apertura '25:00'.
        $respuesta = $this->from(route('sitios.create'))
            ->post(route('sitios.store'), array_merge($this->datosValidos, [
                'hora_apertura' => '25:00',
            ]));

        // Then: hora_apertura falla la validación y no se guarda ninguna fila.
        $respuesta->assertSessionHasErrors('hora_apertura');
        $this->assertDatabaseCount('sitios_patrimoniales', 0);
    }

    /**
     * Given: ya existe 'Qorikancha' en la base de datos.
     * When: se intenta crear otro sitio con el mismo nombre mediante POST /sitios.
     * Then: hay error en nombre y solo queda 1 fila en BD.
     * Importancia: Evita ambigüedad al buscar sitios por nombre en el catálogo.
     */
    #[Test]
    public function rechaza_registro_con_nombre_duplicado(): void
    {
        // Given: ya existe un sitio llamado 'Qorikancha'.
        SitioPatrimonial::factory()->create(['nombre' => 'Qorikancha']);

        // When: se envía POST /sitios con $this->datosValidos, que repite ese nombre.
        $respuesta = $this->from(route('sitios.create'))
            ->post(route('sitios.store'), $this->datosValidos);

        // Then: la validación rechaza 'nombre' y no se agrega una segunda fila.
        $respuesta->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('sitios_patrimoniales', 1);
    }

    /* =====================================================================
     * GET /sitios/{sitio} (show)
     * ===================================================================== */

    /**
     * Given: existe el sitio registrado 'Machu Picchu'.
     * When: se solicita GET /sitios/{sitio} para ese registro.
     * Then: responde 200 con la vista 'sitios.show', el nombre y 'Evaluación de estado de operatividad'.
     * Importancia: Detecta fallos en route-model binding o en el cálculo del DTO del detalle.
     */
    #[Test]
    public function puede_ver_el_detalle_de_un_sitio_patrimonial(): void
    {
        // Given: Machu Picchu está registrado como sitio patrimonial.
        $sitio = SitioPatrimonial::factory()->create(['nombre' => 'Machu Picchu']);

        // When: se envía GET /sitios/{sitio} usando su identificador.
        $respuesta = $this->get(route('sitios.show', $sitio));

        // Then: la vista de detalle responde 200 y muestra nombre y evaluación de operatividad.
        $respuesta->assertOk();
        $respuesta->assertViewIs('sitios.show');
        $respuesta->assertSeeText('Machu Picchu');
        $respuesta->assertSeeText('Evaluación de estado de operatividad');
    }

    /**
     * Given: no existe ningún sitio con id 999999.
     * When: se solicita GET /sitios/{sitio} con id 999999.
     * Then: la respuesta es 404.
     * Importancia: Evita páginas vacías o excepciones al consultar sitios que no existen.
     */
    #[Test]
    public function devuelve_404_para_un_sitio_inexistente(): void
    {
        // Given: el identificador 999999 no corresponde a ningún sitio.
        // When: se envía GET /sitios/{sitio} con ese id.
        $respuesta = $this->get(route('sitios.show', ['sitio' => 999999]));

        // Then: el binding de modelo devuelve 404.
        $respuesta->assertNotFound();
    }

    /* =====================================================================
     * PUT /sitios/{sitio} (update)
     * ===================================================================== */

    /**
     * Given: existe un sitio registrado que se desea editar.
     * When: se envía PUT /sitios/{sitio} con Machu Picchu y horario 06:00-17:00.
     * Then: redirige al detalle, deja sesión 'exito' y actualiza id, nombre y hora_apertura 06:00 en BD.
     * Importancia: Asegura que el formulario de edición persista los cambios de horario.
     */
    #[Test]
    public function puede_actualizar_un_sitio_patrimonial(): void
    {
        // Given: hay un sitio existente listo para ser editado.
        $sitio = SitioPatrimonial::factory()->create();

        // When: se envía PUT /sitios/{sitio} con los nuevos datos de Machu Picchu 06:00-17:00.
        $respuesta = $this->put(route('sitios.update', $sitio), [
            'nombre' => 'Machu Picchu',
            'direccion' => 'Distrito de Machupicchu, Urubamba',
            'descripcion' => 'Ciudadela inca.',
            'hora_apertura' => '06:00',
            'hora_cierre' => '17:00',
            'activo' => true,
        ]);

        // Then: la respuesta redirige al detalle, guarda 'exito' y persiste el nuevo horario.
        $respuesta->assertRedirect(route('sitios.show', $sitio));
        $respuesta->assertSessionHas('exito');

        $this->assertDatabaseHas('sitios_patrimoniales', [
            'id' => $sitio->getKey(),
            'nombre' => 'Machu Picchu',
            'hora_apertura' => '06:00',
        ]);
    }

    /**
     * Given: existe un sitio operativo con hora_apertura 09:00.
     * When: se envía PUT /sitios/{sitio} con 20:00-08:00.
     * Then: hay error en hora_cierre y la BD conserva 09:00 para ese sitio.
     * Importancia: Evita que una edición introduzca un horario inválido en un sitio ya operativo.
     */
    #[Test]
    public function rechaza_actualizacion_con_horario_incoherente(): void
    {
        // Given: el sitio a editar existe y tiene hora_apertura 09:00.
        $sitio = SitioPatrimonial::factory()->create();

        // When: se envía PUT /sitios/{sitio} con el horario inválido 20:00-08:00.
        $respuesta = $this->from(route('sitios.edit', $sitio))
            ->put(route('sitios.update', $sitio), array_merge($this->datosValidos, [
                'hora_apertura' => '20:00',
                'hora_cierre' => '08:00',
            ]));

        // Then: hora_cierre falla la validación y la apertura sigue siendo 09:00 en BD.
        $respuesta->assertSessionHasErrors('hora_cierre');
        $this->assertDatabaseHas('sitios_patrimoniales', [
            'id' => $sitio->getKey(),
            'hora_apertura' => '09:00',
        ]);
    }

    /* =====================================================================
     * DELETE /sitios/{sitio} (destroy, soft delete)
     * ===================================================================== */

    /**
     * Given: existe el sitio 'Qorikancha' en la base de datos.
     * When: se envía DELETE /sitios/{sitio} para ese registro.
     * Then: redirige al listado, marca deleted_at y conserva la fila en BD.
     * Importancia: Previene pérdida de historial y asegura que el registro no desaparezca del todo.
     */
    #[Test]
    public function puede_eliminar_un_sitio_patrimonial_con_borrado_logico(): void
    {
        // Given: Qorikancha está registrado y puede darse de baja.
        $sitio = SitioPatrimonial::factory()->create(['nombre' => 'Qorikancha']);

        // When: se envía DELETE /sitios/{sitio}.
        $respuesta = $this->delete(route('sitios.destroy', $sitio));

        // Then: hay redirect al índice, sesión 'exito' y borrado lógico sin perder la fila.
        $respuesta->assertRedirect(route('sitios.index'));
        $respuesta->assertSessionHas('exito');

        $this->assertSoftDeleted('sitios_patrimoniales', [
            'id' => $sitio->getKey(),
        ]);
        $this->assertDatabaseHas('sitios_patrimoniales', [
            'id' => $sitio->getKey(),
            'nombre' => 'Qorikancha',
        ]);
    }

    /**
     * Given: el sitio 'Qorikancha' existe y se elimina lógicamente.
     * When: se solicita GET /sitios.
     * Then: responde 200 y el nombre 'Qorikancha' no aparece en el catálogo.
     * Importancia: Evita que sitios dados de baja sigan siendo visitables por el turista.
     */
    #[Test]
    public function un_sitio_eliminado_desaparece_del_listado(): void
    {
        // Given: Qorikancha está dado de baja lógicamente.
        $sitio = SitioPatrimonial::factory()->create(['nombre' => 'Qorikancha']);
        $sitio->delete();

        // When: se envía GET /sitios.
        $respuesta = $this->get(route('sitios.index'));

        // Then: el sitio eliminado queda fuera del listado.
        $respuesta->assertOk();
        $respuesta->assertDontSeeText('Qorikancha');
    }

    /* =====================================================================
     * POST /sitios/{sitio}/verificar (endpoint AJAX, CUS-03)
     * ===================================================================== */

    /**
     * Given: existe un sitio con horario 09:00-17:00.
     * When: se envía POST /sitios/{sitio}/verificar con hora_consulta 10:00.
     * Then: responde 200 con exitoso true y datos ABIERTO: hora_actual 10:00, apertura 09:00 y cierre 17:00.
     * Importancia: Asegura que el widget en el navegador reciba el estado correcto.
     */
    #[Test]
    public function verificar_reporta_un_sitio_abierto_dentro_del_horario(): void
    {
        // Given: el sitio tiene horario 09:00-17:00.
        $sitio = SitioPatrimonial::factory()->create([
            'hora_apertura' => '09:00',
            'hora_cierre' => '17:00',
        ]);

        // When: se consulta vía AJAX con la hora 10:00.
        $respuesta = $this->postJson(route('sitios.verificar', $sitio), [
            'hora_consulta' => '10:00',
        ]);

        // Then: el JSON reporta exitoso true, estado 'abierto' y los horarios enviados.
        $respuesta->assertOk();
        $respuesta->assertJsonPath('exitoso', true);
        $respuesta->assertJsonPath('datos.estado', 'abierto');
        $respuesta->assertJsonPath('datos.operativo', true);
        $respuesta->assertJsonPath('datos.hora_actual', '10:00');
        $respuesta->assertJsonPath('datos.hora_apertura', '09:00');
        $respuesta->assertJsonPath('datos.hora_cierre', '17:00');
    }

    /**
     * Given: existe un sitio con horario 09:00-17:00.
     * When: se envía POST /sitios/{sitio}/verificar con consulta 18:00.
     * Then: responde 200 con datos.estado 'cerrado' y operativo false.
     * Importancia: Evita que el usuario vea ABIERTO después del cierre.
     */
    #[Test]
    public function verificar_reporta_un_sitio_cerrado_fuera_del_horario(): void
    {
        // Given: el sitio atiende de 09:00 a 17:00.
        $sitio = SitioPatrimonial::factory()->create([
            'hora_apertura' => '09:00',
            'hora_cierre' => '17:00',
        ]);

        // When: se consulta vía AJAX a las 18:00, fuera del horario.
        $respuesta = $this->postJson(route('sitios.verificar', $sitio), [
            'hora_consulta' => '18:00',
        ]);

        // Then: el JSON marca el sitio como 'cerrado' y no operativo.
        $respuesta->assertOk();
        $respuesta->assertJsonPath('exitoso', true);
        $respuesta->assertJsonPath('datos.estado', 'cerrado');
        $respuesta->assertJsonPath('datos.operativo', false);
    }

    /**
     * Given: existe un sitio con activo=false.
     * When: se envía POST /sitios/{sitio}/verificar con consulta 10:00.
     * Then: responde 200 con datos.estado 'inactivo' y operativo false.
     * Importancia: Previene que un sitio temporalmente cerrado se muestre disponible.
     */
    #[Test]
    public function verificar_reporta_un_sitio_inactivo_independiente_del_horario(): void
    {
        // Given: el sitio está marcado como inactivo.
        $sitio = SitioPatrimonial::factory()->create(['activo' => false]);

        // When: se consulta vía AJAX a las 10:00.
        $respuesta = $this->postJson(route('sitios.verificar', $sitio), [
            'hora_consulta' => '10:00',
        ]);

        // Then: el estado inactivo prevalece y el sitio no es operativo.
        $respuesta->assertOk();
        $respuesta->assertJsonPath('datos.estado', 'inactivo');
        $respuesta->assertJsonPath('datos.operativo', false);
    }

    /**
     * Given: existe un sitio con horario 00:00-23:59.
     * When: se envía POST /sitios/{sitio}/verificar sin hora_consulta.
     * Then: responde 200 con exitoso true y estado 'abierto' usando Carbon::now().
     * Importancia: Evita fallos cuando el visitante deja el campo vacío y pulsa "Verificar estado".
     */
    #[Test]
    public function verificar_funciona_con_la_hora_actual_cuando_no_se_envia_hora_de_consulta(): void
    {
        // Given: el sitio está abierto todo el día, de 00:00 a 23:59.
        $sitio = SitioPatrimonial::factory()->create([
            'hora_apertura' => '00:00',
            'hora_cierre' => '23:59',
        ]);

        // When: se consulta vía AJAX sin enviar hora_consulta.
        $respuesta = $this->postJson(route('sitios.verificar', $sitio), []);

        // Then: se usa la hora actual y el sitio se reporta 'abierto'.
        $respuesta->assertOk();
        $respuesta->assertJsonPath('exitoso', true);
        $respuesta->assertJsonPath('datos.estado', 'abierto');
    }

    /**
     * Given: existe un sitio al que se puede consultar su estado.
     * When: se envía POST /sitios/{sitio}/verificar con hora_consulta '25:00'.
     * Then: responde 422 con exitoso false y las claves 'exitoso', 'mensaje' y 'errores'.
     * Importancia: Evita que una hora inválida llegue al servicio y rompa el JSON.
     */
    #[Test]
    public function verificar_rechaza_una_hora_de_consulta_con_formato_invalido(): void
    {
        // Given: hay un sitio registrado para consultar.
        $sitio = SitioPatrimonial::factory()->create();

        // When: se consulta vía AJAX con la hora inválida '25:00'.
        $respuesta = $this->postJson(route('sitios.verificar', $sitio), [
            'hora_consulta' => '25:00',
        ]);

        // Then: la validación responde 422 con exitoso false y errores en el JSON.
        $respuesta->assertUnprocessable();
        $respuesta->assertJsonPath('exitoso', false);
        $respuesta->assertJsonStructure([
            'exitoso',
            'mensaje',
            'errores',
        ]);
    }

    /**
     * Given: existe un sitio al que se puede consultar su estado.
     * When: se envía POST /sitios/{sitio}/verificar con hora_consulta '-10:00'.
     * Then: responde 422 con exitoso false.
     * Importancia: Mantiene consistente la validación con el formulario principal.
     */
    #[Test]
    public function verificar_rechaza_una_hora_de_consulta_negativa(): void
    {
        // Given: hay un sitio registrado para consultar.
        $sitio = SitioPatrimonial::factory()->create();

        // When: se consulta vía AJAX con la hora negativa '-10:00'.
        $respuesta = $this->postJson(route('sitios.verificar', $sitio), [
            'hora_consulta' => '-10:00',
        ]);

        // Then: la validación responde 422 con exitoso false.
        $respuesta->assertUnprocessable();
        $respuesta->assertJsonPath('exitoso', false);
    }

    /**
     * Given: no existe sitio con id 999999.
     * When: se envía POST /sitios/999999/verificar con hora_consulta 10:00.
     * Then: la respuesta es 404.
     * Importancia: Evita respuestas ambiguas o excepciones al consultar sitios inexistentes.
     */
    #[Test]
    public function verificar_devuelve_404_para_un_sitio_inexistente(): void
    {
        // Given: el id 999999 no corresponde a ningún sitio.
        // When: se consulta vía AJAX esa ruta con la hora 10:00.
        $respuesta = $this->postJson(route('sitios.verificar', ['sitio' => 999999]), [
            'hora_consulta' => '10:00',
        ]);

        // Then: el binding de modelo devuelve 404.
        $respuesta->assertNotFound();
    }

    /**
     * Given: existe un sitio que se elimina lógicamente.
     * When: se envía POST a su ruta de verificación con hora_consulta 10:00.
     * Then: la respuesta es 404.
     * Importancia: Impide consultar estados de sitios que ya no están en el catálogo.
     */
    #[Test]
    public function verificar_devuelve_404_para_un_sitio_eliminado_logicamente(): void
    {
        // Given: el sitio existe y después se da de baja lógicamente.
        $sitio = SitioPatrimonial::factory()->create();
        $sitio->delete();

        // When: se consulta vía AJAX su ruta con la hora 10:00.
        $respuesta = $this->postJson(route('sitios.verificar', ['sitio' => $sitio->getKey()]), [
            'hora_consulta' => '10:00',
        ]);

        // Then: el sitio eliminado no es verificable y responde 404.
        $respuesta->assertNotFound();
    }
}
