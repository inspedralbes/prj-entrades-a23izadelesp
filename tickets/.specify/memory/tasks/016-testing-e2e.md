# Task: Tests E2E con Cypress

**ID**: T014
**Phase**: 6 - Testing
**Priority**: Media
**Status**: Pendiente

## Descripción
Crear tests end-to-end con Cypress que simulan el flujo completo de un usuario: desde la home page hasta la confirmación de la reserva con QR.

## Objetivos
- Test E2E: Flujo completo de reserva (Home → Evento → Sesión → Seat Map → Checkout → QR)
- Test E2E: Sala de espera y admisión al seat map
- Test E2E: Selección de asiento y actualización en tiempo real
- Test E2E: Expiración de soft lock y liberación de asiento

## Archivos a crear/modificar
- `frontend/cypress/e2e/booking-flow.cy.js`
- `frontend/cypress/e2e/waiting-room.cy.js`
- `frontend/cypress/e2e/seat-map-realtime.cy.js`
- `frontend/cypress/e2e/soft-lock-expiry.cy.js`
- `frontend/cypress/support/commands.js`

## Dependencias
- T007 (Home page)
- T008 (Detalle evento)
- T009 (Sala de espera)
- T010 (Seat map)
- T011 (Cola de compra)
- T012 (QR)

## Criterios de verificación
- Todos los tests E2E pasan en local
- El flujo completo funciona sin errores
- Los tests son reproducibles y no dependen de timing exacto
