/**
 * Pruebas End-to-End de CUS-06 con Cypress.
 *
 * Ejecutan el flujo real en el navegador contra http://127.0.0.1:8000 sobre
 * la base sembrada. Http::fake() solo existe en PHPUnit, por eso el panel
 * ofrece modos demo deterministas: "vivo" devuelve 85 y "fallback" simula
 * la caída del sensor para mostrar la lectura histórica o el HTTP 503.
 * Usamos sitios distintos en cada caso para que la caché de 15 segundos no
 * convierta una comprobación de stale en una respuesta "Caché (15s)".
 */

const PAGINA_TELEMETRIA = '/sitios/telemetria';

const SELECTOR_FORMULARIO = '[data-cy="form-telemetria"]';
const SELECTOR_SITIO = '[data-cy="selector-sitio"]';
const SELECTOR_MODO = '[data-cy="modo"]';
const SELECTOR_BTN_CONSULTAR = '[data-cy="btn-consultar"]';
const SELECTOR_RESULTADO = '[data-cy="resultado-telemetria"]';
const SELECTOR_OCUPACION = '[data-cy="ocupacion"]';
const SELECTOR_BARRA = '[data-cy="barra-aforo"]';
const SELECTOR_BADGE_ORIGEN = '[data-cy="badge-origen"]';
const SELECTOR_BADGE_STALE = '[data-cy="badge-stale"]';
const SELECTOR_ALERTA_503 = '[data-cy="alerta-503"]';
const SELECTOR_ESTADO_VACIO = '[data-cy="estado-vacio"]';

const abrirPanel = () => {
    cy.visit(PAGINA_TELEMETRIA);

    cy.get(SELECTOR_FORMULARIO).should('be.visible');
    cy.get(SELECTOR_SITIO).should('be.visible');
    cy.get(SELECTOR_MODO).should('be.visible');
    cy.get(SELECTOR_BTN_CONSULTAR).should('be.visible');
};

const consultar = (nombreSitio, modo) => {
    // El <select> cuelga de la base sembrada; elegir por texto parcial evita
    // depender de ids y tolera que la opción añada el código del sensor.
    cy.get(SELECTOR_SITIO).then($selector => {
        const opcion = [...$selector[0].options].find(option => option.textContent.includes(nombreSitio));

        expect(opcion, `Debe existir la opción ${nombreSitio}`).to.exist;
        cy.wrap($selector).select(opcion.value);
    });

    cy.get(SELECTOR_MODO).select(modo);
    cy.get(SELECTOR_BTN_CONSULTAR).click();
};

describe('CUS-06: Telemetría IoT y Resiliencia de Aforo', () => {
    /**
     * Given: El navegador abre GET /sitios/telemetria sobre la base sembrada y muestra el formulario, el selector de sitio, el modo y el botón Consultar.
     * When: El usuario deja el panel sin consultar ningún sitio y observa su estado inicial.
     * Then: El modo queda en "vivo" con las opciones "En vivo" y "Fallback", se ve el estado vacío "Sin consulta todavía" y no aparecen resultado ni alerta HTTP 503.
     * Importancia: Detecta que la vista no compila o que faltan los data-cy del resto de casos.
     */
    it('CA-01: carga el panel de telemetría con su estado inicial', () => {
        abrirPanel();

        cy.get(SELECTOR_MODO).should('have.value', 'vivo');
        cy.get(SELECTOR_MODO).should('contain.text', 'En vivo');
        cy.get(SELECTOR_MODO).should('contain.text', 'Fallback');
        cy.get(SELECTOR_ESTADO_VACIO).should('be.visible');
        cy.get(SELECTOR_ESTADO_VACIO).should('contain.text', 'Sin consulta todavía');
        cy.get(SELECTOR_RESULTADO).should('not.exist');
        cy.get(SELECTOR_ALERTA_503).should('not.exist');
    });

    /**
     * Given: El panel de telemetría está abierto y el selector de sitio ofrece Machu Picchu.
     * When: El usuario elige Machu Picchu, selecciona el modo demo "vivo" y pulsa Consultar.
     * Then: El resultado muestra 85%, el badge "En vivo (IoT)" y la barra con aria-valuenow 85, sin aviso stale ni alerta 503.
     * Importancia: Comprueba que el valor del DTO llega al texto y al progressbar sin divergencias.
     */
    it('CA-02: consulta el aforo en vivo y muestra el badge En vivo (IoT)', () => {
        abrirPanel();

        consultar('Machu Picchu', 'vivo');

        cy.get(SELECTOR_RESULTADO).should('be.visible');
        cy.get(SELECTOR_OCUPACION).should('contain.text', '85%');
        cy.get(SELECTOR_BARRA).should('have.attr', 'aria-valuenow', '85');
        cy.get(SELECTOR_BADGE_ORIGEN).should('contain.text', 'En vivo (IoT)');
        cy.get(SELECTOR_BADGE_STALE).should('not.exist');
        cy.get(SELECTOR_ALERTA_503).should('not.exist');
    });

    /**
     * Given: El panel de telemetría está abierto y el selector de sitio ofrece Qorikancha.
     * When: El usuario elige Qorikancha, selecciona el modo demo "fallback" y pulsa Consultar.
     * Then: El resultado muestra el 45% histórico, el badge "Fallback" con "Respaldo histórico" y el aviso "Sensor sin señal · Mostrando última lectura registrada", sin alerta 503.
     * Importancia: Asegura que el usuario entiende que el dato es real pero no reciente.
     */
    it('CA-03: muestra respaldo histórico con stale = true al caer el sensor', () => {
        abrirPanel();

        consultar('Qorikancha', 'fallback');

        cy.get(SELECTOR_RESULTADO).should('be.visible');
        cy.get(SELECTOR_OCUPACION).should('contain.text', '45%');
        cy.get(SELECTOR_BADGE_ORIGEN).should('contain.text', 'Fallback');
        cy.get(SELECTOR_BADGE_ORIGEN).should('contain.text', 'Respaldo histórico');
        cy.get(SELECTOR_BADGE_STALE).should('be.visible');
        cy.get(SELECTOR_BADGE_STALE).should('contain.text', 'Sensor sin señal · Mostrando última lectura registrada');
        cy.get(SELECTOR_ALERTA_503).should('not.exist');
    });

    /**
     * Given: El panel de telemetría está abierto y el selector de sitio ofrece el Museo de Arte Religioso.
     * When: El usuario elige el Museo de Arte Religioso, selecciona el modo "fallback" y pulsa Consultar.
     * Then: Aparece la alerta HTTP 503 con SENSOR_NO_DISPONIBLE y no se muestra ningún resultado.
     * Importancia: Evita que una ausencia de datos parezca un fallo 500 del servidor.
     */
    it('CA-04: muestra alerta HTTP 503 cuando no hay lecturas previas', () => {
        abrirPanel();

        consultar('Museo de Arte Religioso', 'fallback');

        cy.get(SELECTOR_ALERTA_503).should('be.visible');
        cy.get(SELECTOR_ALERTA_503).should('contain.text', 'HTTP 503');
        cy.get(SELECTOR_ALERTA_503).should('contain.text', 'SENSOR_NO_DISPONIBLE');
        cy.get(SELECTOR_RESULTADO).should('not.exist');
    });
});
