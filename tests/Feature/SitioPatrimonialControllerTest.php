<?php

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

    #[Test]
    public function devuelve_404_para_un_sitio_inexistente(): void
    {
        $respuesta = $this->get(route('sitios.show', ['sitio' => 999999]));

        $respuesta->assertNotFound();
    }

    /* =====================================================================
     * PUT /sitios/{sitio} (update)
     * ===================================================================== */

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

    #[Test]
    public function verificar_devuelve_404_para_un_sitio_inexistente(): void
    {
        $respuesta = $this->postJson(route('sitios.verificar', ['sitio' => 999999]), [
            'hora_consulta' => '10:00',
        ]);

        $respuesta->assertNotFound();
    }

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
