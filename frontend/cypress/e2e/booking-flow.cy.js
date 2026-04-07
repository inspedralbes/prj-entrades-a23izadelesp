describe('Booking Flow E2E', () => {
  beforeEach(() => {
    cy.visit('/')
    cy.intercept('GET', '/api/events', {
      statusCode: 200,
      body: {
        data: [
          {
            id: 1,
            title: 'Test Movie',
            image: 'https://via.placeholder.com/400x225',
            type: 'cine',
            date: '2026-05-01',
            venue: 'Cinema Test'
          }
        ]
      }
    }).as('getEvents')
  })

  it('completes full booking flow', () => {
    cy.wait('@getEvents')
    cy.contains('Test Movie').click()
    cy.url().should('include', '/events/1')

    cy.intercept('GET', '/api/events/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          title: 'Test Movie',
          description: 'Test description',
          image: 'https://via.placeholder.com/400x225',
          type: 'cine',
          duration: 120,
          genre: 'Action',
          rating: 'PG-13',
          venue: 'Cinema Test',
          sessions: [
            { id: 1, event_id: 1, date: '2026-05-01', time: '18:00:00' }
          ]
        }
      }
    }).as('getEvent')

    cy.wait('@getEvent')
    cy.contains('18:00').click()

    cy.intercept('POST', '/api/sessions/1/queue/join', {
      statusCode: 200,
      body: { data: { position: 1 } }
    }).as('joinQueue')

    cy.intercept('GET', '/api/sessions/*/seats', {
      statusCode: 200,
      body: {
        data: [
          { id: 1, row: 'A', number: 1, status: 'available', price: 10 },
          { id: 2, row: 'A', number: 2, status: 'available', price: 10 }
        ]
      }
    }).as('getSeats')

    cy.wait('@getSeats')
    cy.contains('A1').click()

    cy.intercept('POST', '/api/sessions/1/seats/lock', {
      statusCode: 200,
      body: { data: { success: true } }
    }).as('lockSeat')
    cy.wait('@lockSeat')

    cy.contains('Reservar Ara').click()

    cy.intercept('POST', '/api/bookings', {
      statusCode: 201,
      body: {
        data: {
          id: 1,
          status: 'pending',
          total: 10,
          message: 'Reserva en procés'
        }
      }
    }).as('createBooking')

    cy.wait('@createBooking')
    cy.url().should('include', '/booking/1/confirmed')
  })

  it('shows home page with events', () => {
    cy.contains('QueueLy').should('be.visible')
    cy.contains('Test Movie').should('be.visible')
    cy.contains('🎬 Cine').should('be.visible')
  })

  it('filters events by type', () => {
    cy.contains('Todos').click()
    cy.contains('Test Movie').should('be.visible')
    cy.contains('🎬 Cine').click()
    cy.contains('Test Movie').should('be.visible')
  })
})