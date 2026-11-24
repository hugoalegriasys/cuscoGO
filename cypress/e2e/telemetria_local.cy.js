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
     * Propósito: Comprobar que el panel se carga y arranca sin datos consultados.
     * Escenario: GET /sitios/telemetria -> formulario, selector, modo, botón y estado vacío.
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
     * Propósito: Verificar la visualización del aforo en vivo y su barra visual.
     * Escenario: Machu Picchu + modo demo "vivo" -> 85%, badge "En vivo (IoT)" y aria-valuenow 85.
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
     * Propósito: Validar el estado degradado cuando el sensor pierde señal.
     * Escenario: Qorikancha + modo demo "fallback" -> 45% histórico, badge de respaldo y aviso stale.
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
     * Propósito: Confirmar el error controlado cuando no existe histórico de respaldo.
     * Escenario: Museo de Arte Religioso + modo "fallback" -> alerta HTTP 503 con SENSOR_NO_DISPONIBLE.
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
