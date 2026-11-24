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
     * Given: La aplicación no tiene datos previos y la vista de búsqueda por cercanía está disponible en GET /sitios/cercanos.
     * When: Se solicita esa página con una petición GET.
     * Then: Se responde HTTP 200 con el formulario, coordenadas, radio, botón GPS y los dos presets (Plaza de Armas Cusco y Ollantaytambo).
     * Importancia: Detecta rutas mal nombradas, vistas que no compilan o data-cy ausente para Cypress.
     */
    #[Test]
    public function muestra_la_vista_interactiva_de_busqueda_por_cercania(): void
    {
        // Given: La aplicación está lista y no hay sitios previos para la búsqueda.
        // When: Se solicita GET /sitios/cercanos.
        $response = $this->get(route('sitios.cercanos'));

        // Then: La vista responde 200 y muestra formulario, coordenadas, radio, GPS y dos presets.
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
     * Given: Existen tres sitios: Plaza de Armas Cusco (-13.5167, -71.9781), Qorikancha (-13.5200, -71.9750) y Machu Picchu (-13.1631, -72.5450).
     * When: Se envía un POST a la web con latitud -13.5167, longitud -71.9781 y radio_km 5.
     * Then: Se redirige a la vista y la sesión guarda resultado_cercania con total 2, origen -13.5167/5.0 y distancias ascendentes de 0.0 y 0.5 km.
     * Importancia: Evita que controlador y vista usen claves distintas y dejen la tarjeta de resultados vacía.
     */
    #[Test]
    public function busca_sitios_cercanos_en_la_web_y_guarda_el_resultado_en_sesion(): void
    {
        // Given: Se crean tres sitios en coordenadas concretas: Plaza de Armas Cusco, Qorikancha y Machu Picchu.
        Sitio::factory()->enCoordenadas(-13.5167, -71.9781)->create(['nombre' => 'Plaza de Armas Cusco']);
        Sitio::factory()->enCoordenadas(-13.5200, -71.9750)->create(['nombre' => 'Qorikancha']);
        Sitio::factory()->enCoordenadas(-13.1631, -72.5450)->create(['nombre' => 'Machu Picchu']);

        // When: Se envía el POST web desde Plaza de Armas con radio 5 km.
        $response = $this->post(route('sitios.cercanos.buscar'), [
            'latitud' => -13.5167,
            'longitud' => -71.9781,
            'radio_km' => 5,
        ]);

        // Then: Se redirige y la sesión conserva el resultado con total 2 y distancias 0.0 y 0.5 km.
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
     * Given: Hay cuatro sitios; Qorikancha y Sacsayhuamán están cerca de Plaza de Armas Cusco y Machu Picchu queda lejos.
     * When: Se envía el POST web con latitud -13.5167, longitud -71.9781 y radio_km 5 siguiendo los redirects.
     * Then: Se responde HTTP 200 con tarjetas ordenadas, badges de distancia (0.50 km) y los nombres cercanos, sin Machu Picchu.
     * Importancia: Detecta pérdida del flash de sesión o variables Blade desalineadas en la vista.
     */
    #[Test]
    public function muestra_las_tarjetas_de_resultados_ordenadas_en_la_vista(): void
    {
        // Given: Se crean los sitios cercanos y el sitio lejano Machu Picchu.
        Sitio::factory()->enCoordenadas(-13.5167, -71.9781)->create(['nombre' => 'Plaza de Armas Cusco']);
        Sitio::factory()->enCoordenadas(-13.5200, -71.9750)->create(['nombre' => 'Qorikancha']);
        Sitio::factory()->enCoordenadas(-13.5098, -71.9817)->create(['nombre' => 'Sacsayhuaman']);
        Sitio::factory()->enCoordenadas(-13.1631, -72.5450)->create(['nombre' => 'Machu Picchu']);

        // When: Se busca con origen -13.5167, -71.9781 y radio 5 km siguiendo redirects.
        $response = $this->followingRedirects()->post(route('sitios.cercanos.buscar'), [
            'latitud' => -13.5167,
            'longitud' => -71.9781,
            'radio_km' => 5,
        ]);

        // Then: La vista muestra las tarjetas y badges, incluido 0.50 km, y oculta Machu Picchu.
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
     * Given: Solo existe Plaza de Armas Cusco en (-13.5167, -71.9781), lejos del punto (0, 0).
     * When: Se envía el POST web con latitud 0, longitud 0 y radio_km 0.01 siguiendo los redirects.
     * Then: Se responde HTTP 200 y se muestra el estado vacío "Sin resultados en este radio".
     * Importancia: Evita que el usuario interprete una lista vacía como un fallo del sistema.
     */
    #[Test]
    public function muestra_estado_vacio_cuando_no_hay_sitios_dentro_del_radio(): void
    {
        // Given: Se crea un único sitio que queda fuera del radio consultado.
        Sitio::factory()->enCoordenadas(-13.5167, -71.9781)->create(['nombre' => 'Plaza de Armas Cusco']);

        // When: Se busca desde (0, 0) con un radio muy pequeño de 0.01 km.
        $response = $this->followingRedirects()->post(route('sitios.cercanos.buscar'), [
            'latitud' => 0,
            'longitud' => 0,
            'radio_km' => 0.01,
        ]);

        // Then: La vista responde 200 y explica que no hay resultados en ese radio.
        $response->assertOk();
        $response->assertSee('data-cy="estado-vacio"', false);
        $response->assertSee('Sin resultados en este radio');
    }

    /**
     * Given: La ruta web de búsqueda está disponible y no se necesitan sitios previos.
     * When: Se envía un POST desde /sitios/cercanos con latitud -95.0000, longitud -71.9781 y radio_km 5.
     * Then: Se redirige a /sitios/cercanos y la sesión registra en latitud el mensaje "La latitud debe estar entre -90 y 90 grados."
     * Importancia: Asegura que la alerta terracota explique el error al usuario en español.
     */
    #[Test]
    public function rechaza_latitud_fuera_de_rango_en_la_web(): void
    {
        // Given: No hay sitios que preparar porque la validación ocurre antes de consultar la BD.
        // When: Se envía una latitud inválida -95.0000 desde el formulario web.
        $response = $this->from(route('sitios.cercanos'))
            ->post(route('sitios.cercanos.buscar'), [
                'latitud' => -95.0000,
                'longitud' => -71.9781,
                'radio_km' => 5,
            ]);

        // Then: Se redirige y la sesión conserva el error exacto en el campo latitud.
        $response->assertRedirect(route('sitios.cercanos'));
        $response->assertSessionHasErrors([
            'latitud' => 'La latitud debe estar entre -90 y 90 grados.',
        ]);
    }

    /**
     * Given: Hay tres sitios: Plaza de Armas Cusco, Qorikancha y Machu Picchu.
     * When: Se hace GET /api/sitios/cercanos con latitud -13.5167, longitud -71.9781 y radio_km 100.
     * Then: Se responde 200 con status "ok", origen.radio_km 100.0, total 3 y datos ordenados: Plaza de Armas Cusco, Qorikancha y Machu Picchu (72.86 km).
     * Importancia: Detecta cambios accidentales en claves JSON o pérdida del orden por cercanía en integraciones.
     */
    #[Test]
    public function la_api_devuelve_200_con_sitios_ordenados_por_distancia(): void
    {
        // Given: Se crean los tres sitios de prueba con sus coordenadas reales.
        Sitio::factory()->enCoordenadas(-13.5167, -71.9781)->create(['nombre' => 'Plaza de Armas Cusco']);
        Sitio::factory()->enCoordenadas(-13.5200, -71.9750)->create(['nombre' => 'Qorikancha']);
        Sitio::factory()->enCoordenadas(-13.1631, -72.5450)->create(['nombre' => 'Machu Picchu']);

        // When: Se consulta la API por GET con origen Plaza de Armas y radio 100 km.
        $response = $this->getJson(route('api.sitios.cercanos', [
            'latitud' => -13.5167,
            'longitud' => -71.9781,
            'radio_km' => 100,
        ]));

        // Then: El JSON conserva status ok, total 3 y el orden por distancia hasta Machu Picchu.
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
     * Given: Existen Plaza de Armas Cusco y Machu Picchu con sus coordenadas.
     * When: Se envía POST /api/sitios/cercanos con cuerpo JSON (-13.5167, -71.9781) y radio_km 5.
     * Then: Se responde 200 con status "ok", total 1 y data.0.nombre "Plaza de Armas Cusco".
     * Importancia: Garantiza coherencia entre clientes que envían query string y clientes que envían JSON.
     */
    #[Test]
    public function la_api_acepta_tambien_peticiones_post(): void
    {
        // Given: Se crean un sitio cercano y otro lejano para comprobar el filtro del POST.
        Sitio::factory()->enCoordenadas(-13.5167, -71.9781)->create(['nombre' => 'Plaza de Armas Cusco']);
        Sitio::factory()->enCoordenadas(-13.1631, -72.5450)->create(['nombre' => 'Machu Picchu']);

        // When: Se envía el cuerpo JSON con origen -13.5167, -71.9781 y radio 5 km.
        $response = $this->postJson(route('api.sitios.cercanos'), [
            'latitud' => -13.5167,
            'longitud' => -71.9781,
            'radio_km' => 5,
        ]);

        // Then: La API responde 200 y devuelve solo Plaza de Armas Cusco.
        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.nombre', 'Plaza de Armas Cusco');
    }

    /**
     * Given: El endpoint de la API está disponible y no requiere datos previos.
     * When: Se envía un POST JSON a /api/sitios/cercanos con latitud -95.0000, longitud 185 y radio_km 5.
     * Then: Se responde HTTP 422 con "La latitud debe estar entre -90 y 90 grados." y "La longitud debe estar entre -180 y 180 grados."
     * Importancia: Evita respuestas 500 o códigos incorrectos que confundirían a las integraciones.
     */
    #[Test]
    public function la_api_devuelve_422_ante_coordenadas_invalidas(): void
    {
        // Given: No hay registros que preparar porque la validación se ejecuta primero.
        // When: Se envían coordenadas fuera de rango: latitud -95.0000 y longitud 185.
        $response = $this->postJson(route('api.sitios.cercanos'), [
            'latitud' => -95.0000,
            'longitud' => 185,
            'radio_km' => 5,
        ]);

        // Then: Se responde 422 con los mensajes exactos en latitud y longitud.
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'latitud' => 'La latitud debe estar entre -90 y 90 grados.',
            'longitud' => 'La longitud debe estar entre -180 y 180 grados.',
        ]);
    }

    /**
     * Given: El endpoint de la API está disponible y no requiere sitios previos.
     * When: Se envía un POST JSON con latitud -13.5167, longitud -71.9781 y radio_km 0.
     * Then: Se responde HTTP 422 con el error "El radio debe ser mayor a cero kilómetros." en radio_km.
     * Importancia: Bloquea peticiones que de otro modo devolverían la tabla completa sin filtro útil.
     */
    #[Test]
    public function la_api_devuelve_422_ante_un_radio_invalido(): void
    {
        // Given: No se necesitan sitios porque el radio se valida antes de consultar Haversine.
        // When: Se envía radio_km 0 junto con una coordenada válida.
        $response = $this->postJson(route('api.sitios.cercanos'), [
            'latitud' => -13.5167,
            'longitud' => -71.9781,
            'radio_km' => 0,
        ]);

        // Then: Se responde 422 con el mensaje exacto del campo radio_km.
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'radio_km' => 'El radio debe ser mayor a cero kilómetros.',
        ]);
    }
}
