/**
 * Pruebas End-to-End de CUS-04 con Cypress.
 * Ejecutan el flujo real en el navegador contra http://127.0.0.1:8000: cargan la vista,
 * interactúan con los campos marcados con data-cy y comprueban los tres estados de la tarjeta.
 * Detectan fallos que PHPUnit no ve: JavaScript del selector rápido, CSRF, redirects y estilos.
 */

const PAGINA_TRANSPORTE = '/transporte/calcular';

const SELECTOR_FORMULARIO = '[data-cy="form-transporte"]';
const SELECTOR_RUTA = '[data-cy="ruta-referencia"]';
const SELECTOR_TIPO = '[data-cy="tipo-servicio"]';
const SELECTOR_PASAJEROS = '[data-cy="pasajeros"]';
const SELECTOR_PRECIO = '[data-cy="precio-unitario"]';
const SELECTOR_BTN_CALCULAR = '[data-cy="btn-calcular"]';
const SELECTOR_RESULTADO = '[data-cy="resultado-container"]';
const SELECTOR_BADGE = '[data-cy="badge-resultado"]';
const SELECTOR_COSTO = '[data-cy="costo-total"]';
const SELECTOR_MENSAJE = '[data-cy="mensaje-resultado"]';

const abrirFormulario = () => {
    cy.visit(PAGINA_TRANSPORTE);

    cy.get(SELECTOR_FORMULARIO).should('be.visible');
    cy.get(SELECTOR_RUTA).should('be.visible');
    cy.get(SELECTOR_TIPO).should('be.visible');
    cy.get(SELECTOR_PASAJEROS).should('be.visible');
    cy.get(SELECTOR_PRECIO).should('be.visible');
    cy.get(SELECTOR_BTN_CALCULAR).should('be.visible');
};

const escribirCampo = (selector, valor) => {
    cy.get(selector).should('be.visible').clear().type(String(valor));
    cy.get(selector).should('have.value', String(valor));
};

const calcular = (tipo, pasajeros, precio) => {
    cy.get(SELECTOR_TIPO).select(tipo);
    escribirCampo(SELECTOR_PASAJEROS, pasajeros);
    escribirCampo(SELECTOR_PRECIO, precio);
    cy.get(SELECTOR_BTN_CALCULAR).should('be.visible').click();
};

describe('CUS-04: Cálculo del Costo del Transporte Turístico', () => {
    /**
     * Propósito: Recorrer en el navegador la cotización de bus colectivo por pasajero.
     * Escenario: Elegir BUS_COLECTIVO, 2 pasajeros y S/ 35 -> badge "COBRO POR PASAJERO" y total "S/ 70.00".
     * Importancia: Comprueba que el formulario envía, el servidor responde y la tarjeta se actualiza de verdad.
     */
    it('CA-01: calcula el costo de BUS_COLECTIVO por pasajero', () => {
        abrirFormulario();

        calcular('BUS_COLECTIVO', 2, 35);

        cy.get(SELECTOR_RESULTADO).should('be.visible');
        cy.get(SELECTOR_BADGE).should('contain.text', 'COBRO POR PASAJERO');
        cy.get(SELECTOR_COSTO).should('contain.text', 'S/ 70.00');
        cy.get(SELECTOR_MENSAJE).should('contain.text', 'Bus Colectivo');
    });

    /**
     * Propósito: Verificar visualmente que el servicio privado ignora la cantidad de pasajeros.
     * Escenario: Elegir PRIVADO, 3 pasajeros y S/ 150 -> badge "TARIFA FIJA POR VEHÍCULO" y total "S/ 150.00".
     * Importancia: Previene que el usuario vea un sobrecobro aunque la lógica unitaria sea correcta.
     */
    it('CA-02: calcula PRIVADO como tarifa fija por vehículo', () => {
        abrirFormulario();

        calcular('PRIVADO', 3, 150);

        cy.get(SELECTOR_RESULTADO).should('be.visible');
        cy.get(SELECTOR_BADGE).should('contain.text', 'TARIFA FIJA POR VEHÍCULO');
        cy.get(SELECTOR_COSTO).should('contain.text', 'S/ 150.00');
    });

    /**
     * Propósito: Probar el atajo de ruta turística y su JavaScript de autorrelleno.
     * Escenario: Seleccionar la ruta "tren" -> TREN y 220.00 en los campos; con 2 pasajeros -> "S/ 440.00".
     * Importancia: Detecta scripts roto o atributos data-* desalineados que dejarían al usuario completando todo a mano.
     */
    it('CA-03: autorrellena modalidad y tarifa con el selector rápido de ruta', () => {
        abrirFormulario();

        cy.get(SELECTOR_RUTA).select('tren');
        cy.get(SELECTOR_TIPO).should('have.value', 'TREN');
        cy.get(SELECTOR_PRECIO).should('have.value', '220.00');

        escribirCampo(SELECTOR_PASAJEROS, 2);
        cy.get(SELECTOR_BTN_CALCULAR).should('be.visible').click();

        cy.get(SELECTOR_RESULTADO).should('be.visible');
        cy.get(SELECTOR_COSTO).should('contain.text', 'S/ 440.00');
    });

    /**
     * Propósito: Confirmar que un error de validación se comunica visualmente en la tarjeta derecha.
     * Escenario: BUS_COLECTIVO con 0 pasajeros -> badge "ERROR DE VALIDACIÓN" y mensaje exacto de pasajeros.
     * Importancia: Evita que el formulario parezca no hacer nada o que el mensaje quede solo en la consola.
     */
    it('CA-04: muestra error de validación con pasajeros en cero', () => {
        abrirFormulario();

        calcular('BUS_COLECTIVO', 0, 35);

        cy.get(SELECTOR_RESULTADO).should('be.visible');
        cy.get(SELECTOR_BADGE).should('contain.text', 'ERROR DE VALIDACIÓN');
        cy.get(SELECTOR_MENSAJE).should('contain.text', 'La cantidad de pasajeros debe ser mayor a cero');
    });
});
