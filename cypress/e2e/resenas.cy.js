/**
 * CP-CUSCOGO-001: Caso de prueba: Registro de reseñas en CuscoGO
 * 
 * Criterio de aceptación:
 * El usuario puede registrar una reseña de un sitio turístico mediante una calificación
 * de 1 a 5 estrellas y un comentario. El sistema debe validar los campos obligatorios,
 * rechazar comentarios vacíos o compuestos por espacios y almacenar correctamente la reseña.
 * 
 * Datos:
 * - Sitio turístico: Machu Picchu.
 * - Calificación: 5 estrellas.
 * - Comentario: «Maravillosa experiencia turística. El santuario es impresionante y el guiado fue excelente».
 */

describe('CP-CUSCOGO-001: Caso de prueba: Registro de reseñas en CuscoGO', () => {

  beforeEach(() => {
    cy.viewport(1280, 800)
  })

  it('Escenario 1: Registro exitoso de reseña cumpliendo el paso a paso', () => {
    // Acción: Ingresar a CuscoGO
    cy.visit('/')

    // 1. Seleccionar un sitio turístico (Machu Picchu)
    cy.contains('[data-cy="site-card"]', 'Machu Picchu')
      .should('be.visible')
      .as('sitioTuristico')

    // 2. Acceder a su sección de reseñas
    cy.get('@sitioTuristico')
      .find('[data-cy="enter-site"]')
      .click()

    cy.url().should('include', '/sitios/machu-picchu')
    cy.get('[data-cy="site-title"]').should('contain', 'Machu Picchu')
    cy.get('[data-cy="reviews-section"]').should('be.visible')

    // 3. Seleccionar una calificación (5 estrellas)
    cy.get('[data-cy="rating-select"]').select('5')

    // 4. Escribir un comentario
    const comentario = 'Maravillosa experiencia turística. El santuario es impresionante y el guiado fue excelente.'
    cy.get('[data-cy="comment-input"]').type(comentario)

    // 5. Presionar «Publicar reseña»
    cy.get('[data-cy="submit-button"]').click()

    // Resultado esperado:
    // El sistema registra correctamente la reseña cuando los datos son válidos,
    // muestra un mensaje de confirmación y presenta el comentario en el listado.
    cy.get('[data-cy="success-message"]')
      .should('be.visible')
      .and('contain', 'éxito')

    cy.get('[data-cy="reviews-list"]').within(() => {
      cy.contains(comentario).should('be.visible')
      cy.get('[data-cy="review-rating"]').should('contain', '5')
    })
  })

  it('Escenario 2: Intento de registro sin calificación (validación obligatoria)', () => {
    // Acción: Ingresar a CuscoGO
    cy.visit('/')

    // 1. Seleccionar un sitio turístico (Machu Picchu)
    cy.contains('[data-cy="site-card"]', 'Machu Picchu')
      .as('sitioTuristico')

    // 2. Acceder a su sección de reseñas
    cy.get('@sitioTuristico')
      .find('[data-cy="enter-site"]')
      .click()

    // 3. Omitir calificación (no seleccionar nada)

    // 4. Escribir un comentario
    cy.get('[data-cy="comment-input"]').type('Excelente tour, pero omití seleccionar calificación.')

    // 5. Presionar «Publicar reseña»
    cy.get('[data-cy="submit-button"]').click()

    // Resultado esperado:
    // Muestra los mensajes de validación correspondientes
    cy.get('[data-cy="error-message"]')
      .should('be.visible')
      .and('contain', 'calificación')
  })

  it('Escenario 3: Intento de registro sin comentario (validación obligatoria)', () => {
    // Acción: Ingresar a CuscoGO
    cy.visit('/')

    // 1. Seleccionar un sitio turístico (Machu Picchu)
    cy.contains('[data-cy="site-card"]', 'Machu Picchu')
      .as('sitioTuristico')

    // 2. Acceder a su sección de reseñas
    cy.get('@sitioTuristico')
      .find('[data-cy="enter-site"]')
      .click()

    // 3. Seleccionar una calificación (5 estrellas)
    cy.get('[data-cy="rating-select"]').select('5')

    // 4. Omitir comentario (dejar vacío)

    // 5. Presionar «Publicar reseña»
    cy.get('[data-cy="submit-button"]').click()

    // Resultado esperado:
    // Muestra los mensajes de validación correspondientes
    cy.get('[data-cy="error-message"]')
      .should('be.visible')
      .and('contain', 'comentario')
  })

  it('Escenario 4: Intento de registro con espacios en blanco (rechazo de comentarios vacíos)', () => {
    // Acción: Ingresar a CuscoGO
    cy.visit('/')

    // 1. Seleccionar un sitio turístico (Machu Picchu)
    cy.contains('[data-cy="site-card"]', 'Machu Picchu')
      .as('sitioTuristico')

    // 2. Acceder a su sección de reseñas
    cy.get('@sitioTuristico')
      .find('[data-cy="enter-site"]')
      .click()

    // 3. Seleccionar una calificación (5 estrellas)
    cy.get('[data-cy="rating-select"]').select('5')

    // 4. Escribir un comentario con solo espacios en blanco
    cy.get('[data-cy="comment-input"]').type('     ')

    // 5. Presionar «Publicar reseña»
    cy.get('[data-cy="submit-button"]').click()

    // Resultado esperado:
    // Rechaza comentarios compuestos únicamente por espacios y muestra el mensaje correspondiente
    cy.get('[data-cy="error-message"]')
      .should('be.visible')
      .and('contain', 'espacios')
  })

  it('Escenario 5: Recargar la página para comprobar la persistencia del registro y promedio', () => {
    // Acción: Ingresar a CuscoGO
    cy.visit('/')

    // 1. Seleccionar un sitio turístico (Machu Picchu)
    cy.contains('[data-cy="site-card"]', 'Machu Picchu')
      .as('sitioTuristico')

    // 2. Acceder a su sección de reseñas
    cy.get('@sitioTuristico')
      .find('[data-cy="enter-site"]')
      .click()

    // 3. Seleccionar una calificación (5 estrellas)
    cy.get('[data-cy="rating-select"]').select('5')

    // 4. Escribir un comentario con identificador único
    const comentarioPersistente = `Maravillosa experiencia turística - Persistencia ${Date.now()}`
    cy.get('[data-cy="comment-input"]').type(comentarioPersistente)

    // 5. Presionar «Publicar reseña»
    cy.get('[data-cy="submit-button"]').click()

    // Verificar publicación inicial
    cy.get('[data-cy="reviews-list"]').should('contain', comentarioPersistente)

    // Finalmente: Recargar la página
    cy.reload()

    // Resultado esperado:
    // Después de recargar la página, la reseña permanece visible y se muestra el promedio de calificaciones
    cy.get('[data-cy="reviews-list"]')
      .should('contain', comentarioPersistente)

    cy.get('[data-cy="average-rating"]')
      .should('be.visible')
  })

})
