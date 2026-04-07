describe('Seat Map Real-time Updates', () => {
  beforeEach(() => {
    cy.visit('/events/1/seats/1')
    cy.intercept('GET', '/api/events/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          title: 'Test Event',
          description: 'Test',
          image: 'https://via.placeholder.com/400x225',
          type: 'cine',
          duration: 120,
          venue: 'Test Venue',
          sessions: [{ id: 1, date: '2026-05-01', time: '18:00:00' }]
        }
      }
    })
    cy.intercept('GET', '/api/sessions/1/seats', {
      statusCode: 200,
      body: {
        data: [
          { id: 1, row: 'A', number: 1, status: 'available', price: 10 },
          { id: 2, row: 'A', number: 2, status: 'available', price: 10 },
          { id: 3, row: 'A', number: 3, status: 'available', price: 10 }
        ]
      }
    })
  })

  it('shows available seats', () => {
    cy.contains('A1').should('be.visible')
    cy.contains('A2').should('be.visible')
    cy.contains('A3').should('be.visible')
  })

  it('updates seat to occupied in real-time', () => {
    cy.window().then((win) => {
      if (win.socket) {
        win.socket.emit('seat:locked', {
          session_id: 1,
          seat_id: 1,
          status: 'occupied'
        })
      }
    })

    cy.get('button').contains('A1').should('have.class', 'bg-black')
  })

  it('updates seat to available when released', () => {
    cy.window().then((win) => {
      if (win.socket) {
        win.socket.emit('seat:released', {
          session_id: 1,
          seat_id: 3,
          status: 'available'
        })
      }
    })

    cy.contains('A3').should('not.have.class', 'bg-black')
  })

  it('shows seat selection', () => {
    cy.intercept('POST', '/api/sessions/1/seats/lock', {
      statusCode: 200,
      body: { data: { success: true } }
    }).as('lockSeat')

    cy.contains('A1').click()
    cy.wait('@lockSeat')

    cy.contains('Lliure').should('be.visible')
    cy.contains('Ocupat').should('be.visible')
    cy.contains('Seleccionat').should('be.visible')
  })
})