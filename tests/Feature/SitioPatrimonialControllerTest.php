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
     * Propósito: Verificar que el listado muestre sitios y su estado calculado.
     * Escenario: Qorikancha 00:00-23:59 y Sacsayhuamán 00:00-00:01 -> 200 con ambos nombres y "Verificar estado".
     * Importancia: Detecta fallos al evaluar estados en index o al renderizar la vista.
     */
    #[Test]
    public function puede_listar_los_sitios_patrimoniales_con_su_estado(): void
    {
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

        $respuesta = $this->get(route('sitios.index'));

        $respuesta->assertOk();
        $respuesta->assertViewIs('sitios.index');
        $respuesta->assertViewHas('sitios');
        $respuesta->assertSeeText('Qorikancha');
        $respuesta->assertSeeText('Sacsayhuamán');
        $respuesta->assertSee('Verificar estado');
    }

    /**
     * Propósito: Comprobar que el estado INACTIVO llegue a la vista de listado.
     * Escenario: sitio activo=false -> el HTML contiene 'inactivo'.
     * Importancia: Evita que un sitio cerrado aparezca como abierto por su horario.
     */
    #[Test]
    public function el_listado_muestra_estado_de_sitio_inactivo(): void
    {
        SitioPatrimonial::factory()->create([
            'nombre' => 'Salineras de Maras',
            'activo' => false,
        ]);

        $respuesta = $this->get(route('sitios.index'));

        $respuesta->assertOk();
        $respuesta->assertSeeText('inactivo');
    }

    /**
     * Propósito: Validar el estado vacío del catálogo.
     * Escenario: BD sin sitios -> 200 con 'Aún no hay sitios patrimoniales registrados'.
     * Importancia: Previene pantallas en blanco o errores al recorrer una colección vacía.
     */
    #[Test]
    public function el_listado_muestra_mensaje_cuando_no_existen_sitios(): void
    {
        $respuesta = $this->get(route('sitios.index'));

        $respuesta->assertOk();
        $respuesta->assertSeeText('Aún no hay sitios patrimoniales registrados');
    }

    /* =====================================================================
     * POST /sitios (store)
     * ===================================================================== */

    /**
     * Propósito: Probar el alta de un sitio con datos válidos.
     * Escenario: POST /sitios con Qorikancha 09:00-17:00 -> redirect al detalle, sesión 'exito' y fila en BD.
     * Importancia: Detecta fallos de validación, asignación masiva o persistencia del horario.
     */
    #[Test]
    public function puede_registrar_un_sitio_patrimonial_valido(): void
    {
        $respuesta = $this->post(route('sitios.store'), $this->datosValidos);

        $sitio = SitioPatrimonial::query()->where('nombre', 'Qorikancha')->first();

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
     * Propósito: Validar la regla de cierre posterior a la apertura en el registro.
     * Escenario: POST con 18:00-09:00 -> error en hora_cierre y ninguna fila creada.
     * Importancia: Impide guardar horarios imposibles que luego darían estados falsos.
     */
    #[Test]
    public function rechaza_registro_con_horario_incoherente(): void
    {
        $respuesta = $this->from(route('sitios.create'))
            ->post(route('sitios.store'), array_merge($this->datosValidos, [
                'hora_apertura' => '18:00',
                'hora_cierre' => '09:00',
            ]));

        $respuesta->assertSessionHasErrors('hora_cierre');
        $respuesta->assertRedirect(route('sitios.create'));

        $this->assertDatabaseMissing('sitios_patrimoniales', [
            'nombre' => 'Qorikancha',
        ]);
    }

    /**
     * Propósito: Asegurar que los campos obligatorios no se dejen vacíos.
     * Escenario: nombre, dirección y horas en '' -> errores en los cuatro campos y BD vacía.
     * Importancia: Evita registros incompletos que romperían la evaluación de operatividad.
     */
    #[Test]
    public function rechaza_registro_con_campos_vacios(): void
    {
        $respuesta = $this->from(route('sitios.create'))
            ->post(route('sitios.store'), [
                'nombre' => '',
                'direccion' => '',
                'hora_apertura' => '',
                'hora_cierre' => '',
            ]);

        $respuesta->assertSessionHasErrors(['nombre', 'direccion', 'hora_apertura', 'hora_cierre']);

        $this->assertDatabaseCount('sitios_patrimoniales', 0);
    }

    /**
     * Propósito: Verificar el formato HH:MM en la creación.
     * Escenario: hora_apertura '25:00' -> error en hora_apertura y BD vacía.
     * Importancia: Previene horas inexistentes desde el formulario web.
     */
    #[Test]
    public function rechaza_registro_con_hora_con_formato_invalido(): void
    {
        $respuesta = $this->from(route('sitios.create'))
            ->post(route('sitios.store'), array_merge($this->datosValidos, [
                'hora_apertura' => '25:00',
            ]));

        $respuesta->assertSessionHasErrors('hora_apertura');
        $this->assertDatabaseCount('sitios_patrimoniales', 0);
    }

    /**
     * Propósito: Comprobar la unicidad del nombre en BD.
     * Escenario: ya existe 'Qorikancha' y se intenta crear otro -> error en nombre, solo 1 fila.
     * Importancia: Evita ambigüedad al buscar sitios por nombre en el catálogo.
     */
    #[Test]
    public function rechaza_registro_con_nombre_duplicado(): void
    {
        SitioPatrimonial::factory()->create(['nombre' => 'Qorikancha']);

        $respuesta = $this->from(route('sitios.create'))
            ->post(route('sitios.store'), $this->datosValidos);

        $respuesta->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('sitios_patrimoniales', 1);
    }

    /* =====================================================================
     * GET /sitios/{sitio} (show)
     * ===================================================================== */

    /**
     * Propósito: Validar la vista de detalle con su estado al lado.
     * Escenario: GET /sitios/{sitio} de Machu Picchu -> 200 con nombre y 'Evaluación de estado de operatividad'.
     * Importancia: Detecta fallos en route-model binding o en el cálculo del DTO del detalle.
     */
    #[Test]
    public function puede_ver_el_detalle_de_un_sitio_patrimonial(): void
    {
        $sitio = SitioPatrimonial::factory()->create(['nombre' => 'Machu Picchu']);

        $respuesta = $this->get(route('sitios.show', $sitio));

        $respuesta->assertOk();
        $respuesta->assertViewIs('sitios.show');
        $respuesta->assertSeeText('Machu Picchu');
        $respuesta->assertSeeText('Evaluación de estado de operatividad');
    }

    /**
     * Propósito: Confirmar el 404 del binding de modelo.
     * Escenario: GET con id 999999 -> 404.
     * Importancia: Evita páginas vacías o excepciones al consultar sitios que no existen.
     */
    #[Test]
    public function devuelve_404_para_un_sitio_inexistente(): void
    {
        $respuesta = $this->get(route('sitios.show', ['sitio' => 999999]));

        $respuesta->assertNotFound();
    }

    /* =====================================================================
     * PUT /sitios/{sitio} (update)
     * ===================================================================== */

    /**
     * Propósito: Probar la edición de un sitio existente.
     * Escenario: PUT con Machu Picchu 06:00-17:00 -> redirect, sesión 'exito' y BD actualizada.
     * Importancia: Asegura que el formulario de edición persista los cambios de horario.
     */
    #[Test]
    public function puede_actualizar_un_sitio_patrimonial(): void
    {
        $sitio = SitioPatrimonial::factory()->create();

        $respuesta = $this->put(route('sitios.update', $sitio), [
            'nombre' => 'Machu Picchu',
            'direccion' => 'Distrito de Machupicchu, Urubamba',
            'descripcion' => 'Ciudadela inca.',
            'hora_apertura' => '06:00',
            'hora_cierre' => '17:00',
            'activo' => true,
        ]);

        $respuesta->assertRedirect(route('sitios.show', $sitio));
        $respuesta->assertSessionHas('exito');

        $this->assertDatabaseHas('sitios_patrimoniales', [
            'id' => $sitio->getKey(),
            'nombre' => 'Machu Picchu',
            'hora_apertura' => '06:00',
        ]);
    }

    /**
     * Propósito: Aplicar la misma regla de horario al actualizar.
     * Escenario: PUT con 20:00-08:00 -> error en hora_cierre y BD conserva 09:00.
     * Importancia: Evita que una edición introduzca un horario inválido en un sitio ya operativo.
     */
    #[Test]
    public function rechaza_actualizacion_con_horario_incoherente(): void
    {
        $sitio = SitioPatrimonial::factory()->create();

        $respuesta = $this->from(route('sitios.edit', $sitio))
            ->put(route('sitios.update', $sitio), array_merge($this->datosValidos, [
                'hora_apertura' => '20:00',
                'hora_cierre' => '08:00',
            ]));

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
     * Propósito: Verificar el soft delete del sitio.
     * Escenario: DELETE /sitios/{sitio} -> redirect, deleted_at marcado y fila conservada en BD.
     * Importancia: Previene pérdida de historial y asegura que el registro no desaparezca del todo.
     */
    #[Test]
    public function puede_eliminar_un_sitio_patrimonial_con_borrado_logico(): void
    {
        $sitio = SitioPatrimonial::factory()->create(['nombre' => 'Qorikancha']);

        $respuesta = $this->delete(route('sitios.destroy', $sitio));

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
     * Propósito: Comprobar que SoftDeletes excluya al sitio del catálogo.
     * Escenario: sitio borrado y GET /sitios -> el nombre 'Qorikancha' no aparece.
     * Importancia: Evita que sitios dados de baja sigan siendo visitables por el turista.
     */
    #[Test]
    public function un_sitio_eliminado_desaparece_del_listado(): void
    {
        $sitio = SitioPatrimonial::factory()->create(['nombre' => 'Qorikancha']);
        $sitio->delete();

        $respuesta = $this->get(route('sitios.index'));

        $respuesta->assertOk();
        $respuesta->assertDontSeeText('Qorikancha');
    }

    /* =====================================================================
     * POST /sitios/{sitio}/verificar (endpoint AJAX, CUS-03)
     * ===================================================================== */

    /**
     * Propósito: Validar el endpoint AJAX con hora de consulta ABIERTA.
     * Escenario: POST /sitios/{sitio}/verificar con 10:00 y horario 09:00-17:00 -> exitoso true y datos ABIERTO.
     * Importancia: Asegura que el widget en el navegador reciba el estado correcto.
     */
    #[Test]
    public function verificar_reporta_un_sitio_abierto_dentro_del_horario(): void
    {
        $sitio = SitioPatrimonial::factory()->create([
            'hora_apertura' => '09:00',
            'hora_cierre' => '17:00',
        ]);

        $respuesta = $this->postJson(route('sitios.verificar', $sitio), [
            'hora_consulta' => '10:00',
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('exitoso', true);
        $respuesta->assertJsonPath('datos.estado', 'abierto');
        $respuesta->assertJsonPath('datos.operativo', true);
        $respuesta->assertJsonPath('datos.hora_actual', '10:00');
        $respuesta->assertJsonPath('datos.hora_apertura', '09:00');
        $respuesta->assertJsonPath('datos.hora_cierre', '17:00');
    }

    /**
     * Propósito: Validar el endpoint AJAX con hora CERRADA.
     * Escenario: consulta 18:00 con horario 09:00-17:00 -> datos.estado 'cerrado' y operativo false.
     * Importancia: Evita que el usuario vea ABIERTO después del cierre.
     */
    #[Test]
    public function verificar_reporta_un_sitio_cerrado_fuera_del_horario(): void
    {
        $sitio = SitioPatrimonial::factory()->create([
            'hora_apertura' => '09:00',
            'hora_cierre' => '17:00',
        ]);

        $respuesta = $this->postJson(route('sitios.verificar', $sitio), [
            'hora_consulta' => '18:00',
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('exitoso', true);
        $respuesta->assertJsonPath('datos.estado', 'cerrado');
        $respuesta->assertJsonPath('datos.operativo', false);
    }

    /**
     * Propósito: Confirmar que INACTIVO prevalezca en la respuesta JSON.
     * Escenario: sitio activo=false consultado a 10:00 -> estado 'inactivo'.
     * Importancia: Previene que un sitio temporalmente cerrado se muestre disponible.
     */
    #[Test]
    public function verificar_reporta_un_sitio_inactivo_independiente_del_horario(): void
    {
        $sitio = SitioPatrimonial::factory()->create(['activo' => false]);

        $respuesta = $this->postJson(route('sitios.verificar', $sitio), [
            'hora_consulta' => '10:00',
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('datos.estado', 'inactivo');
        $respuesta->assertJsonPath('datos.operativo', false);
    }

    /**
     * Propósito: Probar el comportamiento por defecto del widget sin hora.
     * Escenario: POST sin hora_consulta en sitio 00:00-23:59 -> estado 'abierto' usando Carbon::now().
     * Importancia: Evita fallos cuando el visitante deja el campo vacío y pulsa "Verificar estado".
     */
    #[Test]
    public function verificar_funciona_con_la_hora_actual_cuando_no_se_envia_hora_de_consulta(): void
    {
        $sitio = SitioPatrimonial::factory()->create([
            'hora_apertura' => '00:00',
            'hora_cierre' => '23:59',
        ]);

        $respuesta = $this->postJson(route('sitios.verificar', $sitio), []);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('exitoso', true);
        $respuesta->assertJsonPath('datos.estado', 'abierto');
    }

    /**
     * Propósito: Verificar la validación del Request AJAX.
     * Escenario: hora_consulta '25:00' -> 422 con exitoso false y errores.
     * Importancia: Evita que una hora inválida llegue al servicio y rompa el JSON.
     */
    #[Test]
    public function verificar_rechaza_una_hora_de_consulta_con_formato_invalido(): void
    {
        $sitio = SitioPatrimonial::factory()->create();

        $respuesta = $this->postJson(route('sitios.verificar', $sitio), [
            'hora_consulta' => '25:00',
        ]);

        $respuesta->assertUnprocessable();
        $respuesta->assertJsonPath('exitoso', false);
        $respuesta->assertJsonStructure([
            'exitoso',
            'mensaje',
            'errores',
        ]);
    }

    /**
     * Propósito: Cubrir el caso negativo en el endpoint AJAX.
     * Escenario: hora_consulta '-10:00' -> 422 con exitoso false.
     * Importancia: Mantiene consistente la validación con el formulario principal.
     */
    #[Test]
    public function verificar_rechaza_una_hora_de_consulta_negativa(): void
    {
        $sitio = SitioPatrimonial::factory()->create();

        $respuesta = $this->postJson(route('sitios.verificar', $sitio), [
            'hora_consulta' => '-10:00',
        ]);

        $respuesta->assertUnprocessable();
        $respuesta->assertJsonPath('exitoso', false);
    }

    /**
     * Propósito: Confirmar 404 al verificar un id que no existe.
     * Escenario: POST /sitios/999999/verificar -> 404.
     * Importancia: Evita respuestas ambiguas o excepciones al consultar sitios inexistentes.
     */
    #[Test]
    public function verificar_devuelve_404_para_un_sitio_inexistente(): void
    {
        $respuesta = $this->postJson(route('sitios.verificar', ['sitio' => 999999]), [
            'hora_consulta' => '10:00',
        ]);

        $respuesta->assertNotFound();
    }

    /**
     * Propósito: Asegurar que un sitio soft-deleted no sea verificable.
     * Escenario: sitio eliminado y POST a su ruta -> 404.
     * Importancia: Impide consultar estados de sitios que ya no están en el catálogo.
     */
    #[Test]
    public function verificar_devuelve_404_para_un_sitio_eliminado_logicamente(): void
    {
        $sitio = SitioPatrimonial::factory()->create();
        $sitio->delete();

        $respuesta = $this->postJson(route('sitios.verificar', ['sitio' => $sitio->getKey()]), [
            'hora_consulta' => '10:00',
        ]);

        $respuesta->assertNotFound();
    }
}
