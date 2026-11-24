<?php

/**
 * Capa de pruebas Feature (HTTP) de CUS-04.
 * Verifica el flujo completo en Laravel: rutas web y API, FormRequest, controlador,
 * sesión, respuesta JSON y renderizado Blade, sin abrir un navegador real.
 * Complementa a las pruebas unitarias comprobando que la regla de negocio llegue al usuario.
 */

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransporteFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Given: la aplicación está lista para mostrar el formulario, sin cálculo previo en sesión.
     * When: se solicita GET /transporte/calcular.
     * Then: responde HTTP 200 con los 3 campos, las 3 modalidades y las rutas de referencia.
     * Importancia: Detecta rutas mal nombradas, vistas que no compilan o assets Vite ausentes antes de que llegue a QA.
     */
    #[Test]
    public function muestra_la_vista_interactiva_de_calculo_de_transporte(): void
    {
        // Given: no hay ningún resultado de transporte guardado en sesión.
        // When: se envía GET /transporte/calcular.
        $response = $this->get(route('transporte.calcular'));

        // Then: la vista carga con los campos, modalidades y rutas de referencia.
        $response->assertOk();
        $response->assertSee('CUS-04 · Cálculo del Costo del Transporte Turístico');
        $response->assertSee('Calcular costo total');
        $response->assertSee('Sin cálculo todavía');
        $response->assertSee('name="tipo_servicio"', false);
        $response->assertSee('name="pasajeros"', false);
        $response->assertSee('name="precio_unitario"', false);
        $response->assertSee('Bus Colectivo Cusco – Ollantaytambo');
        $response->assertSee('Tren Turístico Ollantaytambo – Machu Picchu');
        $response->assertSee('Transporte Privado Valle Sagrado');
        $response->assertSee('value="BUS_COLECTIVO"', false);
        $response->assertSee('value="TREN"', false);
        $response->assertSee('value="PRIVADO"', false);
    }

    /**
     * Given: el formulario web está disponible y no hay un cálculo guardado en sesión.
     * When: se envía POST /transporte/calcular/evaluar con BUS_COLECTIVO, 2 pasajeros y precio 35.
     * Then: redirige a la vista con resultado_transporte y costo_total 70.0 con modalidad por pasajero.
     * Importancia: Evita que el controlador y la vista usen claves distintas y dejen la tarjeta de resultado vacía.
     */
    #[Test]
    public function calcula_el_costo_de_bus_colectivo_y_redirige_con_el_resultado(): void
    {
        // Given: no hay resultado de transporte previo en la sesión.
        // When: se envía el formulario con BUS_COLECTIVO, 2 pasajeros y precio 35.
        $response = $this->post(route('transporte.calcular.evaluar'), [
            'tipo_servicio' => 'BUS_COLECTIVO',
            'pasajeros' => 2,
            'precio_unitario' => 35,
        ]);

        // Then: hay redirect a la vista y el resultado en sesión contiene el costo 70.0.
        $response->assertRedirect(route('transporte.calcular'));
        $response->assertSessionHas('resultado_transporte', static function (array $resultado): bool {
            return $resultado['tipo_servicio'] === 'BUS_COLECTIVO'
                && $resultado['tipo_etiqueta'] === 'Bus Colectivo'
                && $resultado['pasajeros'] === 2
                && (float) $resultado['precio_unitario'] === 35.0
                && (float) $resultado['costo_total'] === 70.0
                && $resultado['modalidad_cobro'] === 'COBRO POR PASAJERO'
                && $resultado['mensaje'] === 'El costo total del servicio Bus Colectivo es de S/ 70.00.';
        });
    }

    /**
     * Given: el formulario web está listo para recibir una cotización de tren.
     * When: se envía TREN, 1 pasajero y precio 220 siguiendo redirects.
     * Then: responde HTTP 200 con "S/ 220.00", el badge 'COBRO POR PASAJERO' y la etiqueta 'Tren Turístico'.
     * Importancia: Detecta pérdida del flash de sesión o nombres de variables Blade desalineados.
     */
    #[Test]
    public function calcula_el_costo_de_tren_y_muestra_el_desglose_en_la_vista(): void
    {
        // Given: no hay cálculo previo; se quiere visualizar el desglose del tren.
        // When: se envía el formulario con TREN, 1 pasajero y precio 220 siguiendo redirects.
        $response = $this->followingRedirects()->post(route('transporte.calcular.evaluar'), [
            'tipo_servicio' => 'TREN',
            'pasajeros' => 1,
            'precio_unitario' => 220,
        ]);

        // Then: la vista final muestra el total, la modalidad y la etiqueta del tren.
        $response->assertOk();
        $response->assertSee('S/ 220.00');
        $response->assertSee('COBRO POR PASAJERO');
        $response->assertSee('Tren Turístico');
    }

    /**
     * Given: el formulario web está disponible y no hay un resultado guardado en sesión.
     * When: se envía PRIVADO, 3 pasajeros y precio 150 mediante POST /transporte/calcular/evaluar.
     * Then: resultado_transporte contiene costo_total 150.0, 'TARIFA FIJA POR VEHÍCULO' y 3 pasajeros.
     * Importancia: Previene sobrecobros en la interfaz si se aplica por error la regla de cobro por pasajero.
     */
    #[Test]
    public function calcula_el_costo_de_servicio_privado_como_tarifa_fija(): void
    {
        // Given: no hay cálculo previo en la sesión.
        // When: se envía el formulario con PRIVADO, 3 pasajeros y precio 150.
        $response = $this->post(route('transporte.calcular.evaluar'), [
            'tipo_servicio' => 'PRIVADO',
            'pasajeros' => 3,
            'precio_unitario' => 150,
        ]);

        // Then: el resultado en sesión respeta la tarifa fija de 150.0 para 3 pasajeros.
        $response->assertRedirect(route('transporte.calcular'));
        $response->assertSessionHas('resultado_transporte', static function (array $resultado): bool {
            return (float) $resultado['costo_total'] === 150.0
                && $resultado['modalidad_cobro'] === 'TARIFA FIJA POR VEHÍCULO'
                && $resultado['pasajeros'] === 3;
        });
    }

    /**
     * Given: el formulario de cotización está disponible sin resultados previos.
     * When: se envía BUS_COLECTIVO con 0 pasajeros y precio 35 desde la vista.
     * Then: redirige de vuelta con error en "pasajeros": 'La cantidad de pasajeros debe ser mayor a cero'.
     * Importancia: Asegura que la UI muestre la guía de corrección en español y no calcule un costo en cero.
     */
    #[Test]
    public function rechaza_cero_pasajeros_en_la_web(): void
    {
        // Given: el visitante está en el formulario sin cálculos anteriores.
        // When: se envía el formulario con 0 pasajeros.
        $response = $this->from(route('transporte.calcular'))
            ->post(route('transporte.calcular.evaluar'), [
                'tipo_servicio' => 'BUS_COLECTIVO',
                'pasajeros' => 0,
                'precio_unitario' => 35,
            ]);

        // Then: vuelve al formulario con el error exacto en 'pasajeros'.
        $response->assertRedirect(route('transporte.calcular'));
        $response->assertSessionHasErrors([
            'pasajeros' => 'La cantidad de pasajeros debe ser mayor a cero',
        ]);
    }

    /**
     * Given: el formulario de cotización está disponible sin resultados previos.
     * When: se envía TREN con -2 pasajeros y precio 220 desde la vista.
     * Then: hay error en "pasajeros" con 'La cantidad de pasajeros debe ser mayor a cero'.
     * Importancia: Evita que un valor negativo llegue a la vista como un total negativo.
     */
    #[Test]
    public function rechaza_pasajeros_negativos_en_la_web(): void
    {
        // Given: el visitante parte del formulario sin cálculos anteriores.
        // When: se envía el formulario con -2 pasajeros.
        $response = $this->from(route('transporte.calcular'))
            ->post(route('transporte.calcular.evaluar'), [
                'tipo_servicio' => 'TREN',
                'pasajeros' => -2,
                'precio_unitario' => 220,
            ]);

        // Then: la validación rechaza 'pasajeros' con el mismo mensaje exacto.
        $response->assertSessionHasErrors([
            'pasajeros' => 'La cantidad de pasajeros debe ser mayor a cero',
        ]);
    }

    /**
     * Given: el formulario web solo admite BUS_COLECTIVO, TREN y PRIVADO.
     * When: se envía tipo_servicio "HELICOPTERO" con 1 pasajero y precio 500.
     * Then: hay error en "tipo_servicio" con 'Tipo de transporte no reconocido'.
     * Importancia: Bloquea valores manipulados desde el navegador que de otro modo cotizarían como colectivo.
     */
    #[Test]
    public function rechaza_un_tipo_de_transporte_no_reconocido_en_la_web(): void
    {
        // Given: el visitante está en el formulario con modalidades permitidas conocidas.
        // When: se envía el valor manipulado HELICOPTERO.
        $response = $this->from(route('transporte.calcular'))
            ->post(route('transporte.calcular.evaluar'), [
                'tipo_servicio' => 'HELICOPTERO',
                'pasajeros' => 1,
                'precio_unitario' => 500,
            ]);

        // Then: la lista blanca rechaza 'tipo_servicio' con el mensaje acordado.
        $response->assertSessionHasErrors([
            'tipo_servicio' => 'Tipo de transporte no reconocido',
        ]);
    }

    /**
     * Given: el formulario web está listo para cotizar un servicio BUS_COLECTIVO.
     * When: se envía precio_unitario -10 con 2 pasajeros.
     * Then: hay error en "precio_unitario" con 'El precio unitario debe ser un monto válido mayor o igual a cero'.
     * Importancia: Evita que un precio inválido se propague y muestre un total negativo al turista.
     */
    #[Test]
    public function rechaza_un_precio_unitario_negativo_en_la_web(): void
    {
        // Given: el visitante parte del formulario sin cálculos anteriores.
        // When: se envía el formulario con precio_unitario -10.
        $response = $this->from(route('transporte.calcular'))
            ->post(route('transporte.calcular.evaluar'), [
                'tipo_servicio' => 'BUS_COLECTIVO',
                'pasajeros' => 2,
                'precio_unitario' => -10,
            ]);

        // Then: la validación rechaza 'precio_unitario' con el mensaje acordado.
        $response->assertSessionHasErrors([
            'precio_unitario' => 'El precio unitario debe ser un monto válido mayor o igual a cero',
        ]);
    }

    /**
     * Given: la API de CUS-04 está disponible para calcular cotizaciones.
     * When: se envía POST JSON con BUS_COLECTIVO, 2 pasajeros y precio 35.
     * Then: responde HTTP 200 con status "ok" y 5 claves en data, incluido costo_total 70.0.
     * Importancia: Detecta cambios accidentales en nombres de claves que romperían a los clientes de la API.
     */
    #[Test]
    public function la_api_calcula_y_devuelve_la_estructura_json_esperada(): void
    {
        // Given: no se requiere estado previo; la API está lista para recibir la cotización.
        // When: se envía el payload JSON con BUS_COLECTIVO, 2 pasajeros y precio 35.
        $response = $this->postJson(route('api.transporte.calcular'), [
            'tipo_servicio' => 'BUS_COLECTIVO',
            'pasajeros' => 2,
            'precio_unitario' => 35,
        ]);

        // Then: el JSON de éxito respeta status, claves de data y costo 70.0.
        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('data.tipo_servicio', 'BUS_COLECTIVO');
        $response->assertJsonPath('data.pasajeros', 2);
        $response->assertJsonPath('data.costo_total', 70.0);
        $response->assertJsonPath('data.modalidad_cobro', 'COBRO POR PASAJERO');
        $response->assertJsonStructure([
            'status',
            'data' => [
                'tipo_servicio',
                'pasajeros',
                'precio_unitario',
                'costo_total',
                'modalidad_cobro',
            ],
        ]);
    }

    /**
     * Given: la API de CUS-04 está disponible para validar cotizaciones.
     * When: se envía POST JSON con HELICOPTERO, 0 pasajeros y precio -10.
     * Then: responde HTTP 422 con los tres mensajes exactos de tipo_servicio, pasajeros y precio_unitario.
     * Importancia: Evita respuestas 500 o códigos incorrectos que confundirían a las integraciones.
     */
    #[Test]
    public function la_api_devuelve_422_ante_datos_invalidos(): void
    {
        // Given: no se requiere estado previo; la API validará el payload recibido.
        // When: se envía el payload JSON con tres datos inválidos a la vez.
        $response = $this->postJson(route('api.transporte.calcular'), [
            'tipo_servicio' => 'HELICOPTERO',
            'pasajeros' => 0,
            'precio_unitario' => -10,
        ]);

        // Then: la validación centralizada devuelve 422 y los mensajes acordados.
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'tipo_servicio' => 'Tipo de transporte no reconocido',
            'pasajeros' => 'La cantidad de pasajeros debe ser mayor a cero',
            'precio_unitario' => 'El precio unitario debe ser un monto válido mayor o igual a cero',
        ]);
    }

    /**
     * Given: la API de CUS-04 aplica las mismas reglas de negocio que la vista web.
     * When: se envía POST JSON con PRIVADO, 5 pasajeros y precio 150.
     * Then: responde HTTP 200 con status "ok", costo_total 150.0 y 'TARIFA FIJA POR VEHÍCULO'.
     * Importancia: Garantiza coherencia entre canales; evita que un consumidor de la API cobre distinto que la web.
     */
    #[Test]
    public function la_api_devuelve_200_con_tarifa_fija_para_privado(): void
    {
        // Given: no se requiere estado previo; la API está lista para cotizar.
        // When: se envía el payload JSON con PRIVADO, 5 pasajeros y precio 150.
        $response = $this->postJson(route('api.transporte.calcular'), [
            'tipo_servicio' => 'PRIVADO',
            'pasajeros' => 5,
            'precio_unitario' => 150,
        ]);

        // Then: la respuesta confirma la tarifa fija de 150.0 sin importar los 5 pasajeros.
        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('data.costo_total', 150.0);
        $response->assertJsonPath('data.modalidad_cobro', 'TARIFA FIJA POR VEHÍCULO');
    }
}
