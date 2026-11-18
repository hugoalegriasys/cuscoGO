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
    it('CA-01: muestra ABIERTO cuando la hora está dentro del horario', () => {
        abrirFormulario();

        evaluarOperatividad('10:00', '09:00', '17:00');

        verificarResultadoDesplegado();
        cy.get(SELECTOR_BADGE).should('contain.text', 'ABIERTO');
        cy.get(SELECTOR_MENSAJE).should('contain.text', 'ABIERTO');
    });

    it('CA-02: muestra CERRADO cuando la hora está fuera del horario', () => {
        abrirFormulario();

        evaluarOperatividad('18:00', '09:00', '17:00');

        verificarResultadoDesplegado();
        cy.get(SELECTOR_BADGE).should('contain.text', 'CERRADO');
        cy.get(SELECTOR_MENSAJE).should('contain.text', 'CERRADO');
    });

    it('CA-03a: muestra ABIERTO en la hora exacta de apertura', () => {
        abrirFormulario();

        evaluarOperatividad('09:00', '09:00', '17:00');

        verificarResultadoDesplegado();
        cy.get(SELECTOR_BADGE).should('contain.text', 'ABIERTO');
        cy.get(SELECTOR_MENSAJE).should('contain.text', 'ABIERTO');
    });

    it('CA-03b: muestra ABIERTO en la hora exacta de cierre', () => {
        abrirFormulario();

        evaluarOperatividad('17:00', '09:00', '17:00');

        verificarResultadoDesplegado();
        cy.get(SELECTOR_BADGE).should('contain.text', 'ABIERTO');
        cy.get(SELECTOR_MENSAJE).should('contain.text', 'ABIERTO');
    });

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
