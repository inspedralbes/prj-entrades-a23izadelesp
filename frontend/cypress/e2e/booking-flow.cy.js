describe('Booking Flow E2E', () => {
  it('completes full booking flow', () => {
    cy.intercept('GET', '**/api/events/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          title: 'Concert Test',
          description: 'Concert description',
          image: 'https://via.placeholder.com/400x225',
          type: 'concert',
          duration: 120,
          genre: 'Pop',
          rating: 'A',
          venue: 'Test Arena',
          sessions: [
            { id: 1, event_id: 1, date: '2026-05-01', time: '18:00:00' },
          ],
        },
      },
    }).as('getEvent')

    cy.intercept('GET', '**/api/sessions/1/seats', {
      statusCode: 200,
      body: {
        data: {
          type: 'zones',
          zones: [
            {
              id: 10,
              name: 'Pista Test',
              zone_type: 'general_admission',
              capacity: 100,
              available: 80,
              price: 49,
              color: '#10B981',
              occupied: 20,
            },
          ],
        },
      },
    }).as('getZones')

    cy.intercept('POST', '**/api/sessions/1/zones/lock', {
      statusCode: 200,
      body: {
        locked: true,
        lock_id: 'lock-1',
        available: 79,
      },
    }).as('lockZone')

    cy.intercept('POST', '**/api/bookings', {
      statusCode: 201,
      body: {
        data: {
          id: 999,
          status: 'pending',
          total: 49,
        },
      },
    }).as('createBooking')

    cy.visit('/events/1')
    cy.wait('@getEvent')

    cy.contains('button', '18:00').click()
    cy.contains('button', 'Veure seients').click()
    cy.url().should('include', '/events/1/seats/1')
    cy.wait('@getZones')

    cy.contains('.card-brutal', 'Zona general').within(() => {
      cy.contains('button', '+').click()
    })

    cy.wait('@lockZone')
    cy.contains('button', 'Comprar').should('not.be.disabled').click()
    cy.url().should('include', '/events/1/checkout/1')

    cy.get('#guest-email').type('guest@example.com')
    cy.contains('button', 'Pagar Ahora').click()
    cy.wait('@createBooking')
    cy.url().should('include', '/bookings/999/confirmed')
  })

  it('shows home page with events', () => {
    cy.visit('/')
    cy.contains('QueueLy').should('be.visible')
    cy.contains('🎬 Cine').should('be.visible')
    cy.contains('🎤 Conciertos').should('be.visible')
  })

  it('filters events by type', () => {
    cy.visit('/')
    cy.contains('button', '🎬 Cine').should('be.visible').click()
    cy.contains('button', '🎤 Conciertos').should('be.visible').click()
    cy.contains('button', 'Todos').should('be.visible').click()
  })
})