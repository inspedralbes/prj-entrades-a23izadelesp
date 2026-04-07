beforeEach(() => {
  cy.visit('/')
  cy.window().then((win) => {
    cy.stub(win, 'fetch').callsFake((url, options) => {
      if (url.includes('/api/events')) {
        return Promise.resolve({
          ok: true,
          json: () => Promise.resolve({
            data: [
              {
                id: 1,
                title: 'Test Event',
                image: 'https://via.placeholder.com/400x225',
                type: 'cine',
                date: '2026-05-01',
                venue: 'Test Venue'
              }
            ]
          })
        })
      }
      return win.fetch(url, options)
    })
  })
})

Cypress.Commands.add('getByTestId', (testId) => {
  return cy.get(`[data-testid="${testId}"]`)
})

Cypress.Commands.add('login', (email = 'test@example.com') => {
  cy.setCookie('token', 'test-token')
  cy.window().then((win) => {
    win.localStorage.setItem('token', 'test-token')
  })
})