Cypress.Commands.add('getByTestId', (testId) => {
  return cy.get(`[data-testid="${testId}"]`)
})

Cypress.Commands.add('login', (email = 'test@example.com') => {
  cy.setCookie('auth-token', 'test-token')
  cy.window().then((win) => {
    win.localStorage.setItem('auth-token', 'test-token')
  })
})