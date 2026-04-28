describe('Soft Lock Expiry', () => {
  beforeEach(() => {
    cy.intercept('GET', '**/api/events/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          title: 'Test Event',
          description: 'Test',
          image: 'https://via.placeholder.com',
          type: 'movie',
          duration: 120,
          genre: 'Action',
          rating: 'PG-13',
          venue: 'Test Venue',
          sessions: [{ id: 1, event_id: 1, date: '2026-05-01', time: '18:00:00' }]
        }
      }
    }).as('getEvent')

    cy.intercept('GET', '**/api/sessions/1/seats', {
      statusCode: 200,
      body: {
        data: {
          type: 'grid',
          grid: [[
            { status: 'available' },
            { status: 'available' }
          ]]
        }
      }
    }).as('getSeats')

    cy.intercept('GET', '**/api/sessions/1/queue/position*', {
      statusCode: 200,
      body: {
        active: true,
        position: 0,
      },
    }).as('getQueuePosition')

    cy.visit('/events/1/seats/1')
    cy.wait('@getEvent')
    cy.wait('@getSeats')
    cy.wait('@getQueuePosition')
  })

  it('shows notification when seat lock expires', () => {
    cy.intercept('POST', '**/api/sessions/1/seats/lock', {
      statusCode: 200,
      body: { success: true }
    }).as('lockSeat')

    cy.contains('span', 'A').parent().within(() => {
      cy.contains('button', '1').click()
    })
    cy.wait('@lockSeat')
    cy.contains('button', 'Comprar').should('not.be.disabled')
  })

  it('clears selection when lock expires', () => {
    cy.intercept('POST', '**/api/sessions/1/seats/lock', {
      statusCode: 200,
      body: { success: true }
    }).as('lockSeat')

    cy.intercept('DELETE', '**/api/sessions/1/seats/unlock', {
      statusCode: 200,
      body: { success: true }
    }).as('unlockSeat')

    cy.contains('span', 'A').parent().within(() => {
      cy.contains('button', '1').click()
    })
    cy.wait('@lockSeat')
    cy.contains('button', 'Comprar').should('not.be.disabled')

    cy.contains('span', 'A').parent().within(() => {
      cy.contains('button', '1').click()
    })
    cy.wait('@unlockSeat')

    cy.contains('button', 'Selecciona sitios').should('be.disabled')
  })

  it('allows selecting another seat after expiry', () => {
    cy.intercept('POST', '**/api/sessions/1/seats/lock', {
      statusCode: 200,
      body: { success: true }
    }).as('lockSeat1')

    cy.intercept('DELETE', '**/api/sessions/1/seats/unlock', {
      statusCode: 200,
      body: { success: true }
    }).as('unlockSeat')

    cy.contains('span', 'A').parent().within(() => {
      cy.contains('button', '1').click()
    })
    cy.wait('@lockSeat1')

    cy.contains('span', 'A').parent().within(() => {
      cy.contains('button', '1').click()
    })
    cy.wait('@unlockSeat')

    cy.contains('span', 'A').parent().within(() => {
      cy.contains('button', '2').click()
    })
    cy.wait('@lockSeat1')
    cy.contains('button', 'Comprar').should('not.be.disabled')
  })
})