<?php

/**
 * Capa de pruebas Feature (HTTP/API) de CUS-05.
 * Recorre las rutas web y REST, el FormRequest, el controlador, la sesión flash
 * y el renderizado Blade, sin abrir un navegador real.
 * Comprueba que la regla de Haversine llegue al usuario y a los clientes de la API.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Sitio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CercaniaFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Propósito: Confirmar que la página web de búsqueda existe y muestra todos sus controles.
     * Escenario: GET /sitios/cercanos -> HTTP 200 con formulario, coordenadas, radio, GPS y dos presets.
     * Importancia: Detecta rutas mal nombradas, vistas que no compilan o data-cy ausente para Cypress.
     */
    #[Test]
    public function muestra_la_vista_interactiva_de_busqueda_por_cercania(): void
    {
        $response = $this->get(route('sitios.cercanos'));

        $response->assertOk();
        $response->assertSee('CUS-05 · Búsqueda de Sitios por Cercanía (Geolocalización / GPS)');
        $response->assertSee('Buscar sitios cercanos');
        $response->assertSee('Sin búsqueda todavía');
        $response->assertSee('data-cy="form-cercania"', false);
        $response->assertSee('name="latitud"', false);
        $response->assertSee('name="longitud"', false);
        $response->assertSee('name="radio_km"', false);
        $response->assertSee('data-cy="btn-gps"', false);
        $response->assertSee('Plaza de Armas Cusco');
        $response->assertSee('Ollantaytambo');
        $response->assertSee('value="100"', false);
    }

    /**
     * Propósito: Validar el flujo web POST y el contrato del resultado guardado en sesión.
     * Escenario: Plaza de Armas con radio 5 -> redirect con resultado_cercania, total 2 y distancias ascendentes.
     * Importancia: Evita que controlador y vista usen claves distintas y dejen la tarjeta de resultados vacía.
     */
    #[Test]
    public function busca_sitios_cercanos_en_la_web_y_guarda_el_resultado_en_sesion(): void
    {
        Sitio::factory()->enCoordenadas(-13.5167, -71.9781)->create(['nombre' => 'Plaza de Armas Cusco']);
        Sitio::factory()->enCoordenadas(-13.5200, -71.9750)->create(['nombre' => 'Qorikancha']);
        Sitio::factory()->enCoordenadas(-13.1631, -72.5450)->create(['nombre' => 'Machu Picchu']);

        $response = $this->post(route('sitios.cercanos.buscar'), [
            'latitud' => -13.5167,
            'longitud' => -71.9781,
            'radio_km' => 5,
        ]);

        $response->assertRedirect(route('sitios.cercanos'));
        $response->assertSessionHas('resultado_cercania', static function (array $resultado): bool {
            return $resultado['status'] === 'ok'
                && $resultado['total'] === 2
                && $resultado['origen']['latitud'] === -13.5167
                && $resultado['origen']['radio_km'] === 5.0
                && $resultado['data'][0]['nombre'] === 'Plaza de Armas Cusco'
                && $resultado['data'][0]['distancia_km'] === 0.0
                && $resultado['data'][1]['nombre'] === 'Qorikancha'
                && $resultado['data'][1]['distancia_km'] === 0.5;
        });
    }

    /**
     * Propósito: Comprobar que el resultado de la sesión se convierte en tarjetas visibles para el usuario.
     * Escenario: Qorikancha y Sacsayhuamán dentro de 5 km -> HTTP 200 con ambos nombres y badges de distancia.
     * Importancia: Detecta pérdida del flash de sesión o variables Blade desalineadas en la vista.
     */
    #[Test]
    public function muestra_las_tarjetas_de_resultados_ordenadas_en_la_vista(): void
    {
        Sitio::factory()->enCoordenadas(-13.5167, -71.9781)->create(['nombre' => 'Plaza de Armas Cusco']);
        Sitio::factory()->enCoordenadas(-13.5200, -71.9750)->create(['nombre' => 'Qorikancha']);
        Sitio::factory()->enCoordenadas(-13.5098, -71.9817)->create(['nombre' => 'Sacsayhuaman']);
        Sitio::factory()->enCoordenadas(-13.1631, -72.5450)->create(['nombre' => 'Machu Picchu']);

        $response = $this->followingRedirects()->post(route('sitios.cercanos.buscar'), [
            'latitud' => -13.5167,
            'longitud' => -71.9781,
            'radio_km' => 5,
        ]);

        $response->assertOk();
        $response->assertSee('data-cy="resultados-cercania"', false);
        $response->assertSee('data-cy="item-sitio-cercano"', false);
        $response->assertSee('data-cy="badge-distancia"', false);
        $response->assertSee('Plaza de Armas Cusco');
        $response->assertSee('Qorikancha');
        $response->assertSee('Sacsayhuaman');
        $response->assertSee('0.50 km');
        $response->assertDontSee('Machu Picchu', false);
    }

    /**
     * Propósito: Verificar el estado vacío cuando el radio no alcanza ningún sitio.
     * Escenario: (0, 0) con radio 0.01 km -> vista con "Sin resultados en este radio".
     * Importancia: Evita que el usuario interprete una lista vacía como un fallo del sistema.
     */
    #[Test]
    public function muestra_estado_vacio_cuando_no_hay_sitios_dentro_del_radio(): void
    {
        Sitio::factory()->enCoordenadas(-13.5167, -71.9781)->create(['nombre' => 'Plaza de Armas Cusco']);

        $response = $this->followingRedirects()->post(route('sitios.cercanos.buscar'), [
            'latitud' => 0,
            'longitud' => 0,
            'radio_km' => 0.01,
        ]);

        $response->assertOk();
        $response->assertSee('data-cy="estado-vacio"', false);
        $response->assertSee('Sin resultados en este radio');
    }

    /**
     * Propósito: Comprobar que la web rechaza la latitud fuera de rango antes de consultar la BD.
     * Escenario: latitud -95.0000 -> redirect con error en "latitud" y mensaje exacto.
     * Importancia: Asegura que la alerta terracota explique el error al usuario en español.
     */
    #[Test]
    public function rechaza_latitud_fuera_de_rango_en_la_web(): void
    {
        $response = $this->from(route('sitios.cercanos'))
            ->post(route('sitios.cercanos.buscar'), [
                'latitud' => -95.0000,
                'longitud' => -71.9781,
                'radio_km' => 5,
            ]);

        $response->assertRedirect(route('sitios.cercanos'));
        $response->assertSessionHasErrors([
            'latitud' => 'La latitud debe estar entre -90 y 90 grados.',
        ]);
    }

    /**
     * Propósito: Fijar el contrato JSON de éxito de la API REST de CUS-05, ordenado por distancia.
     * Escenario: GET /api/sitios/cercanos con Plaza de Armas y radio 100 -> 200, status "ok", total 2 y Machu Picchu al final.
     * Importancia: Detecta cambios accidentales en claves JSON o pérdida del orden por cercanía en integraciones.
     */
    #[Test]
    public function la_api_devuelve_200_con_sitios_ordenados_por_distancia(): void
    {
        Sitio::factory()->enCoordenadas(-13.5167, -71.9781)->create(['nombre' => 'Plaza de Armas Cusco']);
        Sitio::factory()->enCoordenadas(-13.5200, -71.9750)->create(['nombre' => 'Qorikancha']);
        Sitio::factory()->enCoordenadas(-13.1631, -72.5450)->create(['nombre' => 'Machu Picchu']);

        $response = $this->getJson(route('api.sitios.cercanos', [
            'latitud' => -13.5167,
            'longitud' => -71.9781,
            'radio_km' => 100,
        ]));

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('origen.radio_km', 100.0);
        $response->assertJsonPath('total', 3);
        $response->assertJsonPath('data.0.nombre', 'Plaza de Armas Cusco');
        $response->assertJsonPath('data.1.nombre', 'Qorikancha');
        $response->assertJsonPath('data.2.nombre', 'Machu Picchu');
        $response->assertJsonPath('data.2.distancia_km', 72.86);
        $response->assertJsonStructure([
            'status',
            'origen' => ['latitud', 'longitud', 'radio_km'],
            'total',
            'data' => [
                '*' => ['id', 'nombre', 'categoria', 'ubicacion', 'latitud', 'longitud', 'distancia_km'],
            ],
        ]);
    }

    /**
     * Propósito: Confirmar que el mismo endpoint acepta POST con cuerpo JSON.
     * Escenario: POST /api/sitios/cercanos con (-13.5167, -71.9781) y radio 5 -> 200 con total 1.
     * Importancia: Garantiza coherencia entre clientes que envían query string y clientes que envían JSON.
     */
    #[Test]
    public function la_api_acepta_tambien_peticiones_post(): void
    {
        Sitio::factory()->enCoordenadas(-13.5167, -71.9781)->create(['nombre' => 'Plaza de Armas Cusco']);
        Sitio::factory()->enCoordenadas(-13.1631, -72.5450)->create(['nombre' => 'Machu Picchu']);

        $response = $this->postJson(route('api.sitios.cercanos'), [
            'latitud' => -13.5167,
            'longitud' => -71.9781,
            'radio_km' => 5,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.nombre', 'Plaza de Armas Cusco');
    }

    /**
     * Propósito: Validar el manejo centralizado de errores de coordenadas en la API.
     * Escenario: latitud -95.0000 y longitud 185 -> HTTP 422 con ambos mensajes exactos.
     * Importancia: Evita respuestas 500 o códigos incorrectos que confundirían a las integraciones.
     */
    #[Test]
    public function la_api_devuelve_422_ante_coordenadas_invalidas(): void
    {
        $response = $this->postJson(route('api.sitios.cercanos'), [
            'latitud' => -95.0000,
            'longitud' => 185,
            'radio_km' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'latitud' => 'La latitud debe estar entre -90 y 90 grados.',
            'longitud' => 'La longitud debe estar entre -180 y 180 grados.',
        ]);
    }

    /**
     * Propósito: Verificar que el radio se valida antes de ejecutar la consulta Haversine.
     * Escenario: radio_km 0 -> HTTP 422 con "El radio debe ser mayor a cero kilómetros."
     * Importancia: Bloquea peticiones que de otro modo devolverían la tabla completa sin filtro útil.
     */
    #[Test]
    public function la_api_devuelve_422_ante_un_radio_invalido(): void
    {
        $response = $this->postJson(route('api.sitios.cercanos'), [
            'latitud' => -13.5167,
            'longitud' => -71.9781,
            'radio_km' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'radio_km' => 'El radio debe ser mayor a cero kilómetros.',
        ]);
    }
}
