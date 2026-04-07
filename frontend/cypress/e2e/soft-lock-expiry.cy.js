describe('Soft Lock Expiry', () => {
  beforeEach(() => {
    cy.visit('/events/1/seats/1')
    cy.intercept('GET', '/api/events/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          title: 'Test Event',
          description: 'Test',
          image: 'https://via.placeholder.com',
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
          { id: 1, row: 'A', number: 1, status: 'available', price: 10 }
        ]
      }
    })
  })

  it('shows notification when seat lock expires', () => {
    cy.contains('A1').click()

    cy.intercept('POST', '/api/sessions/1/seats/lock', {
      statusCode: 200,
      body: { data: { success: true } }
    }).as('lockSeat')

    cy.wait('@lockSeat')

    cy.window().then((win) => {
      if (win.socket) {
        win.socket.emit('seat:released', {
          session_id: 1,
          seat_id: 1,
          message: 'El seat ha estat alliberat'
        })
      }
    })

    cy.contains('alliberat').should('be.visible')
  })

  it('clears selection when lock expires', () => {
    cy.intercept('POST', '/api/sessions/1/seats/lock', {
      statusCode: 200,
      body: { data: { success: true } }
    }).as('lockSeat')

    cy.contains('A1').click()
    cy.wait('@lockSeat')

    cy.window().then((win) => {
      if (win.socket) {
        win.socket.emit('seat:released', {
          session_id: 1,
          seat_id: 1
        })
      }
    })

    cy.contains('Reservar Ara').should('be.disabled')
  })

  it('allows selecting another seat after expiry', () => {
    cy.intercept('POST', '/api/sessions/1/seats/lock', {
      statusCode: 200,
      body: { data: { success: true } }
    }).as('lockSeat1')

    cy.intercept('POST', '/api/sessions/1/seats/lock', {
      statusCode: 200,
      body: { data: { success: true } }
    }).as('lockSeat2')

    cy.contains('A1').click()
    cy.wait('@lockSeat1')

    cy.window().then((win) => {
      if (win.socket) {
        win.socket.emit('seat:released', { session_id: 1, seat_id: 1 })
      }
    })

    cy.contains('A2').click()
    cy.wait('@lockSeat2')
  })
})