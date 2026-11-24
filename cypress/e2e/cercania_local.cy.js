/**
 * Pruebas End-to-End de CUS-05 con Cypress.
 * Ejecutan el flujo real en el navegador contra http://127.0.0.1:8000: cargan la vista,
 * usan los presets y el selector de radio, envían el formulario y comprueban
 * el orden de las tarjetas, el estado vacío y la alerta de validación.
 * Detectan fallos que PHPUnit no ve: JavaScript de los botones, CSRF, redirects y estilos.
 */

const PAGINA_CERCANIA = '/sitios/cercanos';

const SELECTOR_FORMULARIO = '[data-cy="form-cercania"]';
const SELECTOR_LATITUD = '[data-cy="latitud"]';
const SELECTOR_LONGITUD = '[data-cy="longitud"]';
const SELECTOR_RADIO = '[data-cy="radio-km"]';
const SELECTOR_BTN_GPS = '[data-cy="btn-gps"]';
const SELECTOR_PRESET_CUSCO = '[data-cy="btn-preset-cusco"]';
const SELECTOR_PRESET_OLLANTAYTAMBO = '[data-cy="btn-preset-ollantaytambo"]';
const SELECTOR_BTN_BUSCAR = '[data-cy="btn-buscar-cercanos"]';
const SELECTOR_RESULTADOS = '[data-cy="resultados-cercania"]';
const SELECTOR_ITEM = '[data-cy="item-sitio-cercano"]';
const SELECTOR_BADGE = '[data-cy="badge-distancia"]';
const SELECTOR_ESTADO_VACIO = '[data-cy="estado-vacio"]';
const SELECTOR_ALERTA_ERROR = '[data-cy="alerta-error"]';

const abrirFormulario = () => {
    cy.visit(PAGINA_CERCANIA);

    cy.get(SELECTOR_FORMULARIO).should('be.visible');
    cy.get(SELECTOR_LATITUD).should('be.visible');
    cy.get(SELECTOR_LONGITUD).should('be.visible');
    cy.get(SELECTOR_RADIO).should('be.visible');
    cy.get(SELECTOR_BTN_GPS).should('be.visible');
    cy.get(SELECTOR_PRESET_CUSCO).should('be.visible');
    cy.get(SELECTOR_PRESET_OLLANTAYTAMBO).should('be.visible');
    cy.get(SELECTOR_BTN_BUSCAR).should('be.visible');
};

const buscar = (latitud, longitud, radio) => {
    cy.get(SELECTOR_LATITUD).clear().type(String(latitud));
    cy.get(SELECTOR_LONGITUD).clear().type(String(longitud));
    cy.get(SELECTOR_RADIO).select(String(radio));
    cy.get(SELECTOR_BTN_BUSCAR).click();
};

const distanciasMostradas = () => cy.get(SELECTOR_ITEM).then($items => {
    const distancias = [...$items].map(item => parseFloat(Cypress.$(item).find(SELECTOR_BADGE).text()));

    expect(distancias.length).to.be.greaterThan(0);
    expect(distancias).to.deep.equal([...distancias].sort((a, b) => a - b));

    return distancias;
});

describe('CUS-05: Búsqueda de Sitios por Cercanía (Geolocalización / GPS)', () => {
    /**
     * Propósito: Recorrer en el navegador la búsqueda desde la Plaza de Armas con radio de 5 km.
     * Escenario: Preset "Plaza de Armas Cusco" (-13.5167, -71.9781) y radio 5 -> tarjetas ordenadas con badges 0.00, 0.50 y 0.86 km.
     * Importancia: Comprueba que el JavaScript del preset, el envío del formulario y el orden por distancia funcionan de verdad.
     */
    it('CA-01: lista sitios cercanos ordenados desde la Plaza de Armas', () => {
        abrirFormulario();

        cy.get(SELECTOR_PRESET_CUSCO).click();
        cy.get(SELECTOR_LATITUD).should('have.value', '-13.5167');
        cy.get(SELECTOR_LONGITUD).should('have.value', '-71.9781');

        cy.get(SELECTOR_RADIO).select('5');
        cy.get(SELECTOR_BTN_BUSCAR).click();

        cy.get(SELECTOR_RESULTADOS).should('be.visible');
        cy.get(SELECTOR_ITEM).should('have.length.at.least', 2);
        cy.get(SELECTOR_BADGE).first().should('contain.text', 'km');

        distanciasMostradas();
    });

    /**
     * Propósito: Verificar que al ampliar el radio aparecen destinos lejanos al final de la lista.
     * Escenario: Radio 100 km desde Cusco -> Machu Picchu (72.86 km) incluido como último sitio.
     * Importancia: Detecta un filtro de radio demasiado estrecho o un ORDER BY invertido en la consulta SQL.
     */
    it('CA-02: amplía el radio a 100 km e incluye Machu Picchu al final', () => {
        abrirFormulario();

        cy.get(SELECTOR_PRESET_CUSCO).click();
        buscar('-13.5167', '-71.9781', 100);

        cy.get(SELECTOR_RESULTADOS).should('be.visible');
        cy.get(SELECTOR_ITEM).last().should('contain.text', 'Machu Picchu');
        cy.get(SELECTOR_ITEM).last().find(SELECTOR_BADGE).should('contain.text', '72.86 km');

        distanciasMostradas();
    });

    /**
     * Propósito: Confirmar el mensaje de estado vacío cuando ningún sitio cae dentro del radio.
     * Escenario: Punto alejado (0, 0) con radio 0.01 km -> "Sin resultados en este radio" y cero tarjetas.
     * Importancia: Evita que una lista vacía parezca un error de carga o que se muestren sitios de otra región.
     */
    it('CA-03: muestra estado vacío con un radio de 0.01 km en un punto alejado', () => {
        abrirFormulario();

        // El selector rápido solo ofrece 1/5/10/50/100 km; agregamos 0.01 km
        // solo en el DOM de la prueba para verificar el caso límite del servidor.
        cy.get(SELECTOR_RADIO).then($select => {
            $select.append('<option value="0.01">0.01 km</option>');
        });

        buscar(0, 0, 0.01);

        cy.get(SELECTOR_RESULTADOS).should('be.visible');
        cy.get(SELECTOR_ESTADO_VACIO).should('be.visible');
        cy.get(SELECTOR_ESTADO_VACIO).should('contain.text', 'Sin resultados en este radio');
        cy.get(SELECTOR_ITEM).should('not.exist');
    });

    /**
     * Propósito: Comprobar que una latitud imposible se comunica visualmente al usuario.
     * Escenario: Latitud -95.0000 -> alerta terracota con "La latitud debe estar entre -90 y 90 grados."
     * Importancia: Evita que el formulario parezca no hacer nada o que el mensaje quede solo en la consola.
     */
    it('CA-04: muestra alerta de validación con latitud -95.0000', () => {
        abrirFormulario();

        buscar(-95.0000, -71.9781, 5);

        cy.get(SELECTOR_ALERTA_ERROR).should('be.visible');
        cy.get(SELECTOR_ALERTA_ERROR).should('contain.text', 'La latitud debe estar entre -90 y 90 grados.');
        cy.get(SELECTOR_RESULTADOS).should('not.exist');
    });
});
