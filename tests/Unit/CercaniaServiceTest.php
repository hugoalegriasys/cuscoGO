<?php

/**
 * Capa de pruebas unitarias de CUS-05.
 * Valida la lógica pura de CercaniaService: rangos de coordenadas, Haversine,
 * excepciones de negocio y el orden de los sitios devueltos por la consulta SQL.
 * No intervienen HTTP ni navegador; es la primera red de seguridad ante cambios
 * en la fórmula de distancia o en el filtro por radio.
 */

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\CercaniaServiceInterface;
use App\Models\Sitio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CercaniaServiceTest extends TestCase
{
    use RefreshDatabase;

    private CercaniaServiceInterface $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = app(CercaniaServiceInterface::class);
    }

    /**
     * Propósito: Verificar la distancia esférica real entre Cusco y Machu Picchu con Haversine.
     * Escenario: (-13.5167, -71.9781) -> (-13.1631, -72.5450) -> aproximadamente 73 km (72.86 km).
     * Importancia: Previene errores de radio, unidades incorrectas o fórmulas plano cartesiano.
     */
    public function test_calcula_distancia_entre_cusco_y_machu_picchu(): void
    {
        // Given: Las coordenadas reales de la Plaza de Armas y de Machu Picchu.
        $latitudCusco = -13.5167;
        $longitudCusco = -71.9781;
        $latitudMachu = -13.1631;
        $longitudMachu = -72.5450;

        // When: Se calcula la distancia con el servicio.
        $distancia = $this->servicio->calcularDistanciaKm(
            $latitudCusco,
            $longitudCusco,
            $latitudMachu,
            $longitudMachu,
        );

        // Then: Debe estar en el entorno de 73 km con tolerancia de 1 km.
        $this->assertEqualsWithDelta(73.0, $distancia, 1.0);
    }

    /**
     * Propósito: Confirmar que un punto comparado consigo mismo produce distancia cero.
     * Escenario: (-13.5167, -71.9781) -> (-13.5167, -71.9781) -> 0.00 km.
     * Importancia: Evita distancias residuales por redondeo que sacarían al origen de su propia búsqueda.
     */
    public function test_calcula_distancia_cero_en_el_mismo_punto(): void
    {
        $distancia = $this->servicio->calcularDistanciaKm(-13.5167, -71.9781, -13.5167, -71.9781);

        $this->assertSame(0.0, $distancia);
    }

    /**
     * Propósito: Proteger el cálculo contra coordenadas fuera de los rangos geográficos.
     * Escenario: Latitud -95.0000 -> InvalidArgumentException con "Las coordenadas proporcionadas no son válidas."
     * Importancia: Impide resultados absurdos (distancias negativas o gigantes) por datos corruptos.
     */
    public function test_lanza_excepcion_al_calcular_distancia_con_latitud_invalida(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Las coordenadas proporcionadas no son válidas.');

        $this->servicio->calcularDistanciaKm(-95.0000, -71.9781, -13.1631, -72.5450);
    }

    /**
     * Propósito: Cubrir también los valores especiales NAN e INF que PHP puede producir.
     * Escenario: NAN como destino -> InvalidArgumentException, sin llegar a Haversine.
     * Importancia: Evita que un valor no finito se propague como distancia NAN en la API.
     */
    public function test_lanza_excepcion_al_calcular_distancia_con_valor_no_finito(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->calcularDistanciaKm(-13.5167, -71.9781, NAN, -72.5450);
    }

    /**
     * Propósito: Fijar el contrato de validación de coordenadas, incluyendo los límites exactos.
     * Escenario: (-13.5167, -71.9781) y (-90, 180) -> true; (-95.0000, -71.9781) y (0, 185) -> false.
     * Importancia: Asegura que la frontera del rango se acepte y que un simple signo erróneo se rechace.
     */
    public function test_valida_coordenadas_en_rangos_y_limites(): void
    {
        // Given / When: Se validan casos representativos dentro y fuera de rango.
        $casos = [
            [-13.5167, -71.9781, true],
            [-90.0, 180.0, true],
            [-95.0000, -71.9781, false],
            [0.0, 185.0, false],
        ];

        // Then: Cada pareja coincide con su resultado esperado.
        foreach ($casos as [$latitud, $longitud, $esperado]) {
            $this->assertSame($esperado, $this->servicio->validarCoordenadas($latitud, $longitud));
        }
    }

    /**
     * Propósito: Verificar que la búsqueda SQL filtra por radio y ordena por cercanía real.
     * Escenario: Origen Plaza de Armas y radio 5 km -> Plaza (0), Qorikancha (0.50) y Sacsayhuamán (0.86); Machu Picchu (72.86) y Ollantaytambo (42.15) quedan fuera.
     * Importancia: Detecta consultas sin ORDER BY, filtros por distancia mal aplicados o trigonometría rota en SQLite.
     */
    public function test_busca_sitios_cercanos_dentro_de_cinco_km_y_los_ordena(): void
    {
        // Given: Sitios reales de Cusco con sus coordenadas exactas.
        Sitio::factory()->enCoordenadas(-13.5167, -71.9781)->create(['nombre' => 'Plaza de Armas Cusco']);
        Sitio::factory()->enCoordenadas(-13.5200, -71.9750)->create(['nombre' => 'Qorikancha']);
        Sitio::factory()->enCoordenadas(-13.5098, -71.9817)->create(['nombre' => 'Sacsayhuaman']);
        Sitio::factory()->enCoordenadas(-13.2584, -72.2633)->create(['nombre' => 'Ollantaytambo']);
        Sitio::factory()->enCoordenadas(-13.1631, -72.5450)->create(['nombre' => 'Machu Picchu']);

        // When: Se buscan sitios en un radio de 5 km desde la Plaza de Armas.
        $sitios = $this->servicio->buscarSitiosCercanos(-13.5167, -71.9781, 5);

        // Then: Solo están los tres sitios del centro, ordenados de menor a mayor distancia.
        $this->assertCount(3, $sitios);
        $this->assertSame(
            ['Plaza de Armas Cusco', 'Qorikancha', 'Sacsayhuaman'],
            $sitios->pluck('nombre')->all(),
        );

        $distancias = $sitios->pluck('distancia_km')->map(static fn ($distancia): float => (float) $distancia)->all();
        $this->assertSame([0.0, 0.5, 0.86], $distancias);
        $this->assertFalse($sitios->contains('nombre', 'Machu Picchu'));
    }

    /**
     * Propósito: Evitar que un radio inválido altere el filtro de la consulta.
     * Escenario: Radio 0 -> InvalidArgumentException con "El radio de búsqueda debe ser mayor a cero kilómetros."
     * Importancia: Previene búsquedas que devolverían toda la tabla o ningún resultado sin avisar.
     */
    public function test_lanza_excepcion_si_el_radio_no_es_mayor_a_cero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El radio de búsqueda debe ser mayor a cero kilómetros.');

        $this->servicio->buscarSitiosCercanos(-13.5167, -71.9781, 0);
    }
}
