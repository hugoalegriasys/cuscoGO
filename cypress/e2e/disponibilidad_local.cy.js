/**
 * Pruebas End-to-End de CUS-03 con Cypress.
 * Ejecutan el flujo real en el navegador contra http://127.0.0.1:8000: cargan la vista,
 * escriben hora actual/horario y comprueban los tres estados de la tarjeta (ABIERTO, CERRADO y error).
 * Detectan fallos que PHPUnit no ve: JavaScript, CSRF, redirects y estilos de la tarjeta.
 */

const PAGINA_DISPONIBILIDAD = '/sitios/disponibilidad';

const SELECTOR_HORA_ACTUAL = '[data-cy="hora-actual"]';
const SELECTOR_APERTURA = '[data-cy="apertura"]';
const SELECTOR_CIERRE = '[data-cy="cierre"]';
const SELECTOR_BTN_EVALUAR = '[data-cy="btn-evaluar"]';
const SELECTOR_RESULTADO = '[data-cy="resultado-container"]';
const SELECTOR_BADGE = '[data-cy="badge-resultado"]';
const SELECTOR_MENSAJE = '[data-cy="mensaje-resultado"]';

const abrirFormulario = () => {
    cy.visit(PAGINA_DISPONIBILIDAD);

    cy.get('[data-cy="form-disponibilidad"]').should('be.visible');
    cy.get(SELECTOR_HORA_ACTUAL).should('be.visible');
    cy.get(SELECTOR_APERTURA).should('be.visible');
    cy.get(SELECTOR_CIERRE).should('be.visible');
    cy.get(SELECTOR_BTN_EVALUAR).should('be.visible');
};

const escribirHora = (selector, valor) => {
    cy.get(selector)
        .should('be.visible')
        .clear()
        .type(valor)
        .then(($input) => {
            if ($input.val() !== valor) {
                cy.wrap($input).invoke('val', valor).trigger('input').trigger('change');
            }
        });

    cy.get(selector).should('have.value', valor);
};

const evaluarOperatividad = (horaActual, apertura, cierre) => {
    escribirHora(SELECTOR_HORA_ACTUAL, horaActual);
    escribirHora(SELECTOR_APERTURA, apertura);
    escribirHora(SELECTOR_CIERRE, cierre);

    cy.get(SELECTOR_BTN_EVALUAR).should('be.visible').click();
};

const verificarResultadoDesplegado = () => {
    cy.get(SELECTOR_RESULTADO).should('be.visible');
    cy.get(SELECTOR_BADGE).should('be.visible');
    cy.get(SELECTOR_MENSAJE).should('be.visible');
};

describe('CUS-03: Evaluación de Estado de Operatividad', () => {
    /**
     * Given: El navegador abre el formulario de disponibilidad en /sitios/disponibilidad con la hora actual, la apertura y el cierre visibles.
     * When: El usuario escribe la hora 10:00, el horario 09:00-17:00 y pulsa Evaluar.
     * Then: La tarjeta derecha muestra el badge y el mensaje "ABIERTO".
     * Importancia: Comprueba que el formulario envía, el servidor responde y la tarjeta se actualiza de verdad.
     */
    it('CA-01: muestra ABIERTO cuando la hora está dentro del horario', () => {
        abrirFormulario();

        evaluarOperatividad('10:00', '09:00', '17:00');

        verificarResultadoDesplegado();
        cy.get(SELECTOR_BADGE).should('contain.text', 'ABIERTO');
        cy.get(SELECTOR_MENSAJE).should('contain.text', 'ABIERTO');
    });

    /**
     * Given: El formulario de disponibilidad está cargado en el navegador, listo para comparar la hora con el horario.
     * When: El usuario escribe la hora 18:00, el horario 09:00-17:00 y pulsa Evaluar.
     * Then: La tarjeta derecha muestra el badge y el mensaje "CERRADO" porque el sitio ya cerró.
     * Importancia: Previene que el turista vea ABIERTO cuando el sitio ya cerró.
     */
    it('CA-02: muestra CERRADO cuando la hora está fuera del horario', () => {
        abrirFormulario();

        evaluarOperatividad('18:00', '09:00', '17:00');

        verificarResultadoDesplegado();
        cy.get(SELECTOR_BADGE).should('contain.text', 'CERRADO');
        cy.get(SELECTOR_MENSAJE).should('contain.text', 'CERRADO');
    });

    /**
     * Given: El formulario de disponibilidad está abierto para probar la frontera exacta de apertura en el navegador.
     * When: El usuario escribe la hora 09:00 con apertura 09:00 y cierre 17:00, y pulsa Evaluar.
     * Then: La tarjeta derecha muestra el badge y el mensaje "ABIERTO" en la hora exacta de apertura.
     * Importancia: Detecta comparaciones estrictas que excluyan la hora de apertura.
     */
    it('CA-03a: muestra ABIERTO en la hora exacta de apertura', () => {
        abrirFormulario();

        evaluarOperatividad('09:00', '09:00', '17:00');

        verificarResultadoDesplegado();
        cy.get(SELECTOR_BADGE).should('contain.text', 'ABIERTO');
        cy.get(SELECTOR_MENSAJE).should('contain.text', 'ABIERTO');
    });

    /**
     * Given: El formulario de disponibilidad está abierto para probar la frontera exacta de cierre en el navegador.
     * When: El usuario escribe la hora 17:00 con apertura 09:00 y cierre 17:00, y pulsa Evaluar.
     * Then: La tarjeta derecha muestra el badge y el mensaje "ABIERTO" en la hora exacta de cierre (límite inclusivo).
     * Importancia: Evita que la hora exacta de cierre se muestre como CERRADO por error de comparación.
     */
    it('CA-03b: muestra ABIERTO en la hora exacta de cierre', () => {
        abrirFormulario();

        evaluarOperatividad('17:00', '09:00', '17:00');

        verificarResultadoDesplegado();
        cy.get(SELECTOR_BADGE).should('contain.text', 'ABIERTO');
        cy.get(SELECTOR_MENSAJE).should('contain.text', 'ABIERTO');
    });

    /**
     * Given: El formulario de disponibilidad está cargado y la tarjeta derecha espera el resultado de la evaluación.
     * When: El usuario escribe la hora 10:00 con el horario 18:00-09:00 y pulsa Evaluar.
     * Then: La tarjeta derecha muestra el badge "ERROR DE VALIDACIÓN" y el mensaje "La hora de cierre debe ser posterior a la hora de apertura."
     * Importancia: Evita que el formulario parezca no hacer nada o que el mensaje quede solo en la consola.
     */
    it('CA-04: muestra ERROR DE VALIDACIÓN ante un horario incoherente', () => {
        abrirFormulario();

        evaluarOperatividad('10:00', '18:00', '09:00');

        verificarResultadoDesplegado();
        cy.get(SELECTOR_BADGE).should('contain.text', 'ERROR DE VALIDACIÓN');
        cy.get(SELECTOR_MENSAJE).should(
            'contain.text',
            'La hora de cierre debe ser posterior a la hora de apertura.',
        );
    });
});
