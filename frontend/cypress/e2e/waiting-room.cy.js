describe('Waiting Room E2E', () => {
  function setupBaseInterceptions(positionResponse) {
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
      },
    }).as('getSeats')

    cy.intercept('GET', '**/api/sessions/1/queue/position*', {
      statusCode: 200,
      body: positionResponse,
    }).as('getQueuePosition')
  }

  it('shows waiting room when in queue', () => {
    setupBaseInterceptions({ active: false, position: 5 })

    cy.visit('/events/1/seats/1')
    cy.wait('@getEvent')
    cy.wait('@getSeats')
    cy.wait('@getQueuePosition')

    cy.contains('Accés a compra').should('be.visible')
    cy.contains('#5').should('be.visible')
    cy.contains('Preparant el teu accés').should('be.visible')
  })

  it('updates position in real-time', () => {
    setupBaseInterceptions({ active: false, position: 10 })

    cy.visit('/events/1/seats/1')
    cy.wait('@getEvent')
    cy.wait('@getSeats')
    cy.wait('@getQueuePosition')

    cy.contains('#10').should('be.visible')
    cy.contains('20 min').should('be.visible')
  })

  it('redirects when admitted', () => {
    setupBaseInterceptions({ active: true, position: 0 })

    cy.visit('/events/1/seats/1')
    cy.wait('@getEvent')
    cy.wait('@getSeats')
    cy.wait('@getQueuePosition')

    cy.contains('Accés a compra').should('not.exist')
    cy.contains('button', 'Selecciona sitios').should('be.visible')
  })
})