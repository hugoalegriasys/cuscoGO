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
     * Propósito: Confirmar que la página web de cotización existe y muestra todos sus controles.
     * Escenario: GET /transporte/calcular -> HTTP 200 con los 3 campos, las 3 modalidades y las rutas de referencia.
     * Importancia: Detecta rutas mal nombradas, vistas que no compilan o assets Vite ausentes antes de que llegue a QA.
     */
    #[Test]
    public function muestra_la_vista_interactiva_de_calculo_de_transporte(): void
    {
        $response = $this->get(route('transporte.calcular'));

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
     * Propósito: Validar el flujo web completo POST y el contrato del resultado guardado en sesión.
     * Escenario: BUS_COLECTIVO, 2 pasajeros y precio 35 -> redirect a la vista con resultado_transporte y costo 70.0.
     * Importancia: Evita que el controlador y la vista usen claves distintas y dejen la tarjeta de resultado vacía.
     */
    #[Test]
    public function calcula_el_costo_de_bus_colectivo_y_redirige_con_el_resultado(): void
    {
        $response = $this->post(route('transporte.calcular.evaluar'), [
            'tipo_servicio' => 'BUS_COLECTIVO',
            'pasajeros' => 2,
            'precio_unitario' => 35,
        ]);

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
     * Propósito: Comprobar que el resultado de la sesión se convierte en información visible para el usuario.
     * Escenario: TREN, 1 pasajero y precio 220 siguiendo redirects -> HTTP 200 con "S/ 220.00", badge y etiqueta.
     * Importancia: Detecta pérdida del flash de sesión o nombres de variables Blade desalineados.
     */
    #[Test]
    public function calcula_el_costo_de_tren_y_muestra_el_desglose_en_la_vista(): void
    {
        $response = $this->followingRedirects()->post(route('transporte.calcular.evaluar'), [
            'tipo_servicio' => 'TREN',
            'pasajeros' => 1,
            'precio_unitario' => 220,
        ]);

        $response->assertOk();
        $response->assertSee('S/ 220.00');
        $response->assertSee('COBRO POR PASAJERO');
        $response->assertSee('Tren Turístico');
    }

    /**
     * Propósito: Verificar que la tarifa fija por vehículo también se respeta en el flujo HTTP web.
     * Escenario: PRIVADO, 3 pasajeros y precio 150 -> resultado_transporte con costo 150.0 y modalidad fija.
     * Importancia: Previene sobrecobros en la interfaz si se aplica por error la regla de cobro por pasajero.
     */
    #[Test]
    public function calcula_el_costo_de_servicio_privado_como_tarifa_fija(): void
    {
        $response = $this->post(route('transporte.calcular.evaluar'), [
            'tipo_servicio' => 'PRIVADO',
            'pasajeros' => 3,
            'precio_unitario' => 150,
        ]);

        $response->assertRedirect(route('transporte.calcular'));
        $response->assertSessionHas('resultado_transporte', static function (array $resultado): bool {
            return (float) $resultado['costo_total'] === 150.0
                && $resultado['modalidad_cobro'] === 'TARIFA FIJA POR VEHÍCULO'
                && $resultado['pasajeros'] === 3;
        });
    }

    /**
     * Propósito: Comprobar que el FormRequest rechaza el valor mínimo inválido de pasajeros en la web.
     * Escenario: BUS_COLECTIVO con 0 pasajeros -> redirect con error en "pasajeros" y mensaje exacto.
     * Importancia: Asegura que la UI muestre la guía de corrección en español y no calcule un costo en cero.
     */
    #[Test]
    public function rechaza_cero_pasajeros_en_la_web(): void
    {
        $response = $this->from(route('transporte.calcular'))
            ->post(route('transporte.calcular.evaluar'), [
                'tipo_servicio' => 'BUS_COLECTIVO',
                'pasajeros' => 0,
                'precio_unitario' => 35,
            ]);

        $response->assertRedirect(route('transporte.calcular'));
        $response->assertSessionHasErrors([
            'pasajeros' => 'La cantidad de pasajeros debe ser mayor a cero',
        ]);
    }

    /**
     * Propósito: Extender la validación web de pasajeros al caso negativo.
     * Escenario: TREN con -2 pasajeros -> error en "pasajeros" con el mismo mensaje exacto.
     * Importancia: Evita que un valor negativo llegue a la vista como un total negativo.
     */
    #[Test]
    public function rechaza_pasajeros_negativos_en_la_web(): void
    {
        $response = $this->from(route('transporte.calcular'))
            ->post(route('transporte.calcular.evaluar'), [
                'tipo_servicio' => 'TREN',
                'pasajeros' => -2,
                'precio_unitario' => 220,
            ]);

        $response->assertSessionHasErrors([
            'pasajeros' => 'La cantidad de pasajeros debe ser mayor a cero',
        ]);
    }

    /**
     * Propósito: Verificar que la lista blanca de modalidades se aplique antes de llegar al servicio.
     * Escenario: tipo_servicio "HELICOPTERO" -> error en "tipo_servicio" con "Tipo de transporte no reconocido".
     * Importancia: Bloquea valores manipulados desde el navegador que de otro modo cotizarían como colectivo.
     */
    #[Test]
    public function rechaza_un_tipo_de_transporte_no_reconocido_en_la_web(): void
    {
        $response = $this->from(route('transporte.calcular'))
            ->post(route('transporte.calcular.evaluar'), [
                'tipo_servicio' => 'HELICOPTERO',
                'pasajeros' => 1,
                'precio_unitario' => 500,
            ]);

        $response->assertSessionHasErrors([
            'tipo_servicio' => 'Tipo de transporte no reconocido',
        ]);
    }

    /**
     * Propósito: Confirmar que la web rechaza tarifas negativas con el mensaje acordado para el formulario.
     * Escenario: precio_unitario -10 -> error en "precio_unitario" con "El precio unitario debe ser un monto válido...".
     * Importancia: Evita que un precio inválido se propague y muestre un total negativo al turista.
     */
    #[Test]
    public function rechaza_un_precio_unitario_negativo_en_la_web(): void
    {
        $response = $this->from(route('transporte.calcular'))
            ->post(route('transporte.calcular.evaluar'), [
                'tipo_servicio' => 'BUS_COLECTIVO',
                'pasajeros' => 2,
                'precio_unitario' => -10,
            ]);

        $response->assertSessionHasErrors([
            'precio_unitario' => 'El precio unitario debe ser un monto válido mayor o igual a cero',
        ]);
    }

    /**
     * Propósito: Fijar el contrato JSON de éxito de la API REST de CUS-04.
     * Escenario: POST JSON con BUS_COLECTIVO, 2 pasajeros y 35 -> HTTP 200, status "ok" y 5 claves en data.
     * Importancia: Detecta cambios accidentales en nombres de claves que romperían a los clientes de la API.
     */
    #[Test]
    public function la_api_calcula_y_devuelve_la_estructura_json_esperada(): void
    {
        $response = $this->postJson(route('api.transporte.calcular'), [
            'tipo_servicio' => 'BUS_COLECTIVO',
            'pasajeros' => 2,
            'precio_unitario' => 35,
        ]);

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
     * Propósito: Validar el manejo centralizado de errores de validación en la API.
     * Escenario: HELICOPTERO, 0 pasajeros y precio -10 -> HTTP 422 con los tres mensajes exactos.
     * Importancia: Evita respuestas 500 o códigos incorrectos que confundirían a las integraciones.
     */
    #[Test]
    public function la_api_devuelve_422_ante_datos_invalidos(): void
    {
        $response = $this->postJson(route('api.transporte.calcular'), [
            'tipo_servicio' => 'HELICOPTERO',
            'pasajeros' => 0,
            'precio_unitario' => -10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'tipo_servicio' => 'Tipo de transporte no reconocido',
            'pasajeros' => 'La cantidad de pasajeros debe ser mayor a cero',
            'precio_unitario' => 'El precio unitario debe ser un monto válido mayor o igual a cero',
        ]);
    }

    /**
     * Propósito: Asegurar que la API aplica la misma regla de tarifa fija que la vista web.
     * Escenario: PRIVADO con 5 pasajeros y precio 150 -> HTTP 200, costo 150.0 y modalidad fija.
     * Importancia: Garantiza coherencia entre canales; evita que un consumidor de la API cobre distinto que la web.
     */
    #[Test]
    public function la_api_devuelve_200_con_tarifa_fija_para_privado(): void
    {
        $response = $this->postJson(route('api.transporte.calcular'), [
            'tipo_servicio' => 'PRIVADO',
            'pasajeros' => 5,
            'precio_unitario' => 150,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('data.costo_total', 150.0);
        $response->assertJsonPath('data.modalidad_cobro', 'TARIFA FIJA POR VEHÍCULO');
    }
}
