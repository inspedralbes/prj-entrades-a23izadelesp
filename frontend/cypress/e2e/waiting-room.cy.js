describe('Waiting Room E2E', () => {
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
          genre: 'Action',
          rating: 'PG-13',
          venue: 'Test Venue',
          sessions: [{ id: 1, date: '2026-05-01', time: '18:00:00' }]
        }
      }
    })
    cy.intercept('GET', '/api/sessions/1', {
      statusCode: 200,
      body: { data: { id: 1, event_id: 1 } }
    })
  })

  it('shows waiting room when in queue', () => {
    cy.intercept('POST', '/api/sessions/1/queue/join', {
      statusCode: 200,
      body: { data: { position: 5 } }
    }).as('joinQueue')

    cy.wait('@joinQueue')

    cy.contains('Sala d\'Espera').should('be.visible')
    cy.contains('#5').should('be.visible')
    cy.contains('Gestionando').should('be.visible')
  })

  it('updates position in real-time', () => {
    cy.intercept('POST', '/api/sessions/1/queue/join', {
      statusCode: 200,
      body: { data: { position: 10 } }
    }).as('joinQueue')

    cy.wait('@joinQueue')
    cy.contains('#10').should('be.visible')

    const socketMock = { on: cy.stub(), emit: cy.stub() }
    cy.window().then((win) => {
      Object.assign(win, { socket: socketMock })

      socketMock.on.withArgs('queue:position').callsFake((event, cb) => {
        cb({ position: 3 })
      })
    })

    cy.contains('#3').should('be.visible')
  })

  it('redirects when admitted', () => {
    cy.intercept('POST', '/api/sessions/1/queue/join', {
      statusCode: 200,
      body: { data: { position: 1 } }
    }).as('joinQueue')

    cy.wait('@joinQueue')

    cy.window().then((win) => {
      if (win.socket) {
        win.socket.emit('queue:admitted', { session_id: 1 })
      }
    })

    cy.url().should('include', '/seats/1')
  })
})