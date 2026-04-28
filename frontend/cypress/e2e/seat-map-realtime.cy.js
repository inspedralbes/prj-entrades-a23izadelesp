describe('Seat Map Real-time Updates', () => {
  beforeEach(() => {
    cy.intercept('GET', '**/api/events/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          title: 'Test Event',
          description: 'Test',
          image: 'https://via.placeholder.com/400x225',
          type: 'movie',
          duration: 120,
          venue: 'Test Venue',
          genre: 'Action',
          rating: 'PG-13',
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
            { status: 'occupied' },
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

  it('shows available seats', () => {
    cy.contains('span', 'A')
      .parent()
      .within(() => {
        cy.contains('button', '1').should('be.visible')
        cy.contains('button', '2').should('be.visible').and('be.disabled')
        cy.contains('button', '3').should('be.visible')
      })
  })

  it('updates seat to occupied in real-time', () => {
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

  it('updates seat to available when released', () => {
    cy.intercept('POST', '**/api/sessions/1/seats/lock', {
      statusCode: 200,
      body: { success: true }
    }).as('lockSeat')

    cy.intercept('DELETE', '**/api/sessions/1/seats/unlock', {
      statusCode: 200,
      body: { success: true }
    }).as('unlockSeat')

    cy.contains('span', 'A').parent().within(() => {
      cy.contains('button', '3').click()
    })
    cy.wait('@lockSeat')
    cy.contains('button', 'Comprar').should('not.be.disabled')

    cy.contains('span', 'A').parent().within(() => {
      cy.contains('button', '3').click()
    })
    cy.wait('@unlockSeat')
    cy.contains('button', 'Selecciona sitios').should('be.disabled')
  })

  it('shows seat selection', () => {
    cy.intercept('POST', '**/api/sessions/1/seats/lock', {
      statusCode: 200,
      body: { success: true }
    }).as('lockSeat')

    cy.contains('span', 'A').parent().within(() => {
      cy.contains('button', '1').click()
    })
    cy.wait('@lockSeat')

    cy.contains('Lliure').should('be.visible')
    cy.contains('Ocupat').should('be.visible')
    cy.contains('Seleccionat').should('be.visible')
  })
})