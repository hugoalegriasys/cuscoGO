<?php

/**
 * Capa de pruebas unitarias de CUS-03 enfocada en sitios patrimoniales.
 * Valida verificarSitioAbierto(), evaluarEstadoOperatividad(), el DTO resultante y los mutators del modelo,
 * usando modelos en memoria sin HTTP, base de datos ni navegador.
 * Detecta regresiones en la conversión de estados ABIERTO / CERRADO / INACTIVO antes de llegar al controlador.
 */

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\EstadoOperatividadDTO;
use App\Models\SitioPatrimonial;
use App\Services\DisponibilidadService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias de los métodos de sitio patrimonial del
 * servicio de disponibilidad (CUS-03: Evaluación de Estado de
 * Operatividad). Se usan modelos en memoria, sin base de datos.
 */
class EstadoOperatividadSitioServiceTest extends TestCase
{
    private DisponibilidadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DisponibilidadService;
    }

    private function crearSitio(array $atributos = []): SitioPatrimonial
    {
        $porOmision = [
            'nombre' => 'Qorikancha',
            'direccion' => 'Calle Santo Domingo, Cusco',
            'descripcion' => 'Templo del Sol',
            'hora_apertura' => '09:00',
            'hora_cierre' => '17:00',
            'activo' => true,
        ];

        return new SitioPatrimonial(array_merge($porOmision, $atributos));
    }

    /* =====================================================================
     * verificarSitioAbierto()
     * ===================================================================== */

    /**
     * Given: Un sitio Qorikancha activo en memoria con horario 09:00-17:00.
     * When: Se verifica si está abierto consultando a las 10:00.
     * Then: verificarSitioAbierto() devuelve true (ABIERTO).
     * Importancia: Previene falsos CERRADO que dejarían al turista sin información válida.
     */
    #[Test]
    public function sitio_esta_abierto_dentro_del_horario_de_atencion(): void
    {
        // Given: Un sitio Qorikancha con horario 09:00-17:00 y activo.
        $sitio = $this->crearSitio();

        // When: Se consulta si el sitio está abierto a las 10:00.
        // Then: El servicio debe responder que sí está abierto.
        $this->assertTrue($this->service->verificarSitioAbierto($sitio, '10:00'));
    }

    /**
     * Given: Un sitio Qorikancha activo en memoria con horario 09:00-17:00.
     * When: Se verifica si está abierto consultando a las 18:00, tras el cierre.
     * Then: verificarSitioAbierto() devuelve false (CERRADO).
     * Importancia: Evita mostrar ABIERTO cuando el sitio ya cerró.
     */
    #[Test]
    public function sitio_no_esta_abierto_fuera_del_horario_de_atencion(): void
    {
        // Given: Un sitio Qorikancha con horario 09:00-17:00 y activo.
        $sitio = $this->crearSitio();

        // When: Se consulta si el sitio está abierto a las 18:00.
        // Then: El servicio debe responder que ya está cerrado.
        $this->assertFalse($this->service->verificarSitioAbierto($sitio, '18:00'));
    }

    /**
     * Given: Un sitio Qorikancha con horario 09:00-17:00 pero activo=false.
     * When: Se verifica si está abierto consultando a las 10:00, dentro del horario.
     * Then: verificarSitioAbierto() devuelve false porque el estado inactivo prevalece.
     * Importancia: Impide que un sitio temporalmente cerrado aparezca como ABIERTO por su horario.
     */
    #[Test]
    public function sitio_inactivo_nunca_se_reporta_como_abierto(): void
    {
        // Given: Un sitio con horario 09:00-17:00 pero activo=false.
        $sitio = $this->crearSitio(['activo' => false]);

        // When: Se consulta si el sitio está abierto a las 10:00.
        // Then: El servicio debe responder false aunque la hora esté dentro del horario.
        $this->assertFalse($this->service->verificarSitioAbierto($sitio, '10:00'));
    }

    /**
     * Given: Un sitio activo con horario 00:00-23:59 y sin hora de consulta (horaActual=null).
     * When: Se verifica si está abierto sin enviar la hora, usando Carbon::now() por defecto.
     * Then: verificarSitioAbierto() devuelve true porque la jornada cubre cualquier hora.
     * Importancia: Evita que el widget AJAX falle cuando el usuario deja el campo vacío.
     */
    #[Test]
    public function sitio_esta_abierto_cuando_se_consulta_con_la_hora_actual_del_sistema(): void
    {
        // Given: Un sitio activo sin hora de consulta y con jornada completa.
        // Horario 00:00 - 23:59 cubre cualquier hora actual del sistema.
        $sitio = $this->crearSitio([
            'hora_apertura' => '00:00',
            'hora_cierre' => '23:59',
        ]);

        // When: Se verifica el estado sin pasar horaActual, usando Carbon::now().
        // Then: El servicio debe responder que está abierto.
        $this->assertTrue($this->service->verificarSitioAbierto($sitio));
    }

    /* =====================================================================
     * evaluarEstadoOperatividad()
     * ===================================================================== */

    /**
     * Given: Un sitio Sacsayhuamán activo en memoria con horario 09:00-17:00.
     * When: Se evalúa su estado de operatividad consultando a las 10:00.
     * Then: Se obtiene un DTO con estado 'abierto', operativo true, nombre 'Sacsayhuamán', horaActual '10:00', apertura '09:00', cierre '17:00' y mensaje con "abierto".
     * Importancia: Previene respuestas JSON incompletas o con el nombre/horario equivocados.
     */
    #[Test]
    public function evaluacion_devuelve_dto_abierto_con_los_datos_correctos(): void
    {
        // Given: Un sitio Sacsayhuamán con horario 09:00-17:00 y activo.
        $sitio = $this->crearSitio(['nombre' => 'Sacsayhuamán']);

        // When: Se evalúa su operatividad a las 10:00.
        $estado = $this->service->evaluarEstadoOperatividad($sitio, '10:00');

        // Then: El DTO debe ser ABIERTO y contener los datos del sitio y de la consulta.
        $this->assertInstanceOf(EstadoOperatividadDTO::class, $estado);
        $this->assertSame('abierto', $estado->estado);
        $this->assertTrue($estado->operativo);
        $this->assertSame('Sacsayhuamán', $estado->nombreSitio);
        $this->assertSame('10:00', $estado->horaActual);
        $this->assertSame('09:00', $estado->horaApertura);
        $this->assertSame('17:00', $estado->horaCierre);
        $this->assertStringContainsStringIgnoringCase('abierto', $estado->mensaje);
    }

    /**
     * Given: Un sitio Qorikancha activo en memoria con horario 09:00-17:00.
     * When: Se evalúa su estado de operatividad consultando a las 18:00, tras el cierre.
     * Then: El DTO tiene estado 'cerrado', operativo false, horaActual '18:00' y mensaje con "cerrado".
     * Importancia: Evita que la tarjeta muestre datos de otro sitio o una hora distinta a la consultada.
     */
    #[Test]
    public function evaluacion_devuelve_dto_cerrado_con_los_datos_correctos(): void
    {
        // Given: Un sitio Qorikancha con horario 09:00-17:00 y activo.
        $sitio = $this->crearSitio();

        // When: Se evalúa su operatividad a las 18:00.
        $estado = $this->service->evaluarEstadoOperatividad($sitio, '18:00');

        // Then: El DTO debe ser CERRADO y conservar la hora consultada.
        $this->assertSame('cerrado', $estado->estado);
        $this->assertFalse($estado->operativo);
        $this->assertSame('18:00', $estado->horaActual);
        $this->assertStringContainsStringIgnoringCase('cerrado', $estado->mensaje);
    }

    /**
     * Given: Un sitio Qorikancha con horario 09:00-17:00 pero activo=false.
     * When: Se evalúa su estado de operatividad consultando a las 10:00.
     * Then: El DTO tiene estado 'inactivo', operativo false y el mensaje exacto "El sitio patrimonial se encuentra temporalmente inactivo.".
     * Importancia: Asegura que el mensaje al turista sea claro y no parezca un error técnico.
     */
    #[Test]
    public function evaluacion_devuelve_dto_inactivo_cuando_el_sitio_esta_desactivado(): void
    {
        // Given: Un sitio con horario 09:00-17:00 pero activo=false.
        $sitio = $this->crearSitio(['activo' => false]);

        // When: Se evalúa su operatividad a las 10:00.
        $estado = $this->service->evaluarEstadoOperatividad($sitio, '10:00');

        // Then: El DTO debe ser INACTIVO y explicar la situación al turista.
        $this->assertSame('inactivo', $estado->estado);
        $this->assertFalse($estado->operativo);
        $this->assertSame('El sitio patrimonial se encuentra temporalmente inactivo.', $estado->mensaje);
    }

    /**
     * Given: Un sitio Qorikancha activo con horario 09:00-17:00, evaluado como ABIERTO a las 10:00.
     * When: Se convierte el DTO resultante en un arreglo con toArray().
     * Then: El arreglo contiene sitio_id, nombre_sitio, estado, operativo, hora_actual, hora_apertura, hora_cierre y mensaje, con operativo en true.
     * Importancia: Previene errores en la respuesta JSON por claves faltantes o renombradas.
     */
    #[Test]
    public function evaluacion_convierte_el_dto_a_un_arreglo_asociativo_completo(): void
    {
        // Given: Un sitio Qorikancha con horario 09:00-17:00 y activo.
        $sitio = $this->crearSitio();

        // When: Se evalúa a las 10:00 y se convierte el DTO con toArray().
        $arreglo = $this->service->evaluarEstadoOperatividad($sitio, '10:00')->toArray();

        // Then: El arreglo debe exponer todas las claves que consume la vista/AJAX.
        $this->assertArrayHasKey('sitio_id', $arreglo);
        $this->assertArrayHasKey('nombre_sitio', $arreglo);
        $this->assertArrayHasKey('estado', $arreglo);
        $this->assertArrayHasKey('operativo', $arreglo);
        $this->assertArrayHasKey('hora_actual', $arreglo);
        $this->assertArrayHasKey('hora_apertura', $arreglo);
        $this->assertArrayHasKey('hora_cierre', $arreglo);
        $this->assertArrayHasKey('mensaje', $arreglo);
        $this->assertTrue($arreglo['operativo']);
    }

    /* =====================================================================
     * Casos de borde y manejo defensivo de errores
     * ===================================================================== */

    /**
     * Given: Un sitio Qorikancha válido con horario 09:00-17:00 y una hora de consulta '25:00'.
     * When: Se evalúa su estado con esa hora fuera de rango.
     * Then: Se lanza InvalidArgumentException con el texto 'hora de consulta'.
     * Importancia: Garantiza que el endpoint AJAX pueda mostrar un error entendible al usuario.
     */
    #[Test]
    public function evaluacion_lanza_excepcion_si_la_hora_de_consulta_es_invalida(): void
    {
        // Given: Un sitio Qorikancha con horario 09:00-17:00 y activo.
        $sitio = $this->crearSitio();

        // Then: Se espera InvalidArgumentException con el texto 'hora de consulta'.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('hora de consulta');

        // When: Se evalúa el estado del sitio con la hora '25:00'.
        $this->service->evaluarEstadoOperatividad($sitio, '25:00');
    }

    /**
     * Given: Un sitio Qorikancha válido con horario 09:00-17:00 y una hora de consulta '-10:00'.
     * When: Se evalúa su estado con esa hora de formato incorrecto.
     * Then: Se lanza InvalidArgumentException sin procesar la hora como válida.
     * Importancia: Evita que un signo o formato extraño se procese como una hora válida.
     */
    #[Test]
    public function evaluacion_lanza_excepcion_si_la_hora_de_consulta_tiene_formato_incorrecto(): void
    {
        // Given: Un sitio Qorikancha con horario 09:00-17:00 y activo.
        $sitio = $this->crearSitio();

        // Then: Se espera InvalidArgumentException por el formato de la hora.
        $this->expectException(InvalidArgumentException::class);

        // When: Se evalúa el estado del sitio con la hora '-10:00'.
        $this->service->evaluarEstadoOperatividad($sitio, '-10:00');
    }

    /**
     * Given: Un sitio sin horario registrado, con hora_apertura y hora_cierre en null vía setRawAttributes.
     * When: Se evalúa su estado de operatividad consultando a las 10:00.
     * Then: Se lanza InvalidArgumentException con el texto 'no tiene un horario de atención'.
     * Importancia: Previene un error fatal de tipos al intentar comparar horas inexistentes.
     */
    #[Test]
    public function evaluacion_lanza_excepcion_si_el_sitio_no_tiene_horario_registrado(): void
    {
        // Given: Un sitio 'Sitio sin horario' con apertura y cierre en null, cargados sin mutators.
        $sitio = $this->crearSitio();
        $sitio->setRawAttributes([
            'nombre' => 'Sitio sin horario',
            'direccion' => 'Cusco',
            'hora_apertura' => null,
            'hora_cierre' => null,
            'activo' => true,
        ]);

        // Then: Se espera InvalidArgumentException con el texto 'no tiene un horario de atención'.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no tiene un horario de atención');

        // When: Se evalúa el estado del sitio con la hora '10:00'.
        $this->service->evaluarEstadoOperatividad($sitio, '10:00');
    }

    /**
     * Given: Un sitio con apertura '18:00' y cierre '09:00' cargados vía setRawAttributes, burlando los mutators.
     * When: Se evalúa su estado de operatividad consultando a las 10:00.
     * Then: Se lanza InvalidArgumentException con el texto 'horario de atención'.
     * Importancia: Evita responder ABIERTO con un horario imposible que debería haberse rechazado.
     */
    #[Test]
    public function evaluacion_lanza_excepcion_si_el_horario_registrado_es_incoherente(): void
    {
        // Given: Un sitio con horario invertido en base de datos que eludió la validación.
        // Simula datos corruptos en base de datos que eludieron la validación:
        // se usa setRawAttributes para omitir los mutators del modelo.
        $sitio = $this->crearSitio();
        $sitio->setRawAttributes([
            'nombre' => 'Sitio corrupto',
            'direccion' => 'Cusco',
            'hora_apertura' => '18:00',
            'hora_cierre' => '09:00',
            'activo' => true,
        ]);

        // Then: Se espera InvalidArgumentException con el texto 'horario de atención'.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('horario de atención');

        // When: Se evalúa el estado del sitio con la hora '10:00'.
        $this->service->evaluarEstadoOperatividad($sitio, '10:00');
    }

    /**
     * Given: Un sitio Qorikancha válido con horario 09:00-17:00 en memoria.
     * When: Se asigna '25:00' a hora_apertura, disparando el mutator del modelo.
     * Then: Se lanza InvalidArgumentException con el texto 'hora de apertura'.
     * Importancia: Impide que se persistan horarios inválidos que luego romperían la evaluación.
     */
    #[Test]
    public function el_modelo_rechaza_horas_con_formato_invalido_al_persistir(): void
    {
        // Then: Se espera InvalidArgumentException con el texto 'hora de apertura'.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('hora de apertura');

        // Given: Un sitio Qorikancha con horario válido 09:00-17:00.
        $sitio = $this->crearSitio();

        // When: Se asigna '25:00' a hora_apertura antes de persistir.
        $sitio->hora_apertura = '25:00';
    }

    /**
     * Given: Un sitio Machu Picchu con horas leídas de MySQL como '06:00:00' y '17:00:00' vía setRawAttributes.
     * When: Se leen hora_apertura, hora_cierre y horarioAtencion() del modelo.
     * Then: Se obtienen '06:00', '17:00' y el texto de horario '06:00 - 17:00', sin segundos.
     * Importancia: Evita que los segundos provoquen comparaciones erróneas o textos raros en la vista.
     */
    #[Test]
    public function el_modelo_normaliza_horas_con_segundos_al_leer_de_base_de_datos(): void
    {
        // Given: Un sitio Machu Picchu con '06:00:00' y '17:00:00' cargados sin mutators.
        $sitio = $this->crearSitio();
        $sitio->setRawAttributes([
            'nombre' => 'Machu Picchu',
            'direccion' => 'Urubamba',
            'hora_apertura' => '06:00:00',
            'hora_cierre' => '17:00:00',
            'activo' => true,
        ]);

        // When: Se consultan las horas normalizadas y el texto del horario.
        // Then: Deben ser '06:00', '17:00' y '06:00 - 17:00'.
        $this->assertSame('06:00', $sitio->hora_apertura);
        $this->assertSame('17:00', $sitio->hora_cierre);
        $this->assertSame('06:00 - 17:00', $sitio->horarioAtencion());
    }
}
