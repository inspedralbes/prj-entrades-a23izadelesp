# Task: Perfil de Usuario y Historial de Compras

**ID**: T011
**Phase**: 4 - Frontend + Backend
**Priority**: Media
**Status**: Pendiente

## Descripción
Crear la página de perfil del usuario donde puede ver su historial de compras y acceder a sus entradas. Solo accesible para usuarios autenticados (Sanctum).

## Objetivos
- Endpoint: `GET /api/profile/tickets` - lista de reservas del usuario autenticado
- Endpoint: `GET /api/profile/tickets/{id}` - detalle de una reserva con QR
- Frontend: página `/profile/tickets` con listado de compras
- Frontend: página `/profile/tickets/{id}` con detalle y QR descargable
- Diseño Neo-Brutalista con tarjetas por cada reserva

## Endpoints
| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| GET | `/api/profile/tickets` | Historial de compras del usuario | Requerida |
| GET | `/api/profile/tickets/{id}` | Detalle de una compra + QR | Requerida |

## Archivos a crear/modificar
- `backend/app/Http/Controllers/Api/ProfileController.php`
- `backend/app/Http/Resources/TicketResource.php`
- `backend/routes/api.php`
- `frontend/pages/profile/tickets.vue` - listado de entradas
- `frontend/pages/profile/tickets/[id].vue` - detalle de entrada
- `frontend/components/TicketCard.vue` - tarjeta de entrada en el listado
- `frontend/stores/profile.js` (Pinia)

## Flujo
```
Usuario logueado → /profile/tickets
  ├─ GET /api/profile/tickets (con Sanctum token)
  ├─ Muestra lista de bookings con estado
  └─ Click en una → /profile/tickets/{id}
      ├─ GET /api/profile/tickets/{id}
      ├─ Muestra detalle: evento, sesión, asientos/zonas, total
      └─ Muestra QR (reutiliza endpoint de QR existente)
```

## Dependencias
- T003 (Auth Sanctum)
- T013 (Cola de compra - las reservas deben existir)
- T014 (Generación de QR)

## Criterios de verificación
- Solo usuarios autenticados pueden acceder a `/profile/tickets`
- Se muestran todas las reservas del usuario con su estado
- El detalle de cada reserva muestra evento, sesión, asientos/zonas, total
- El QR se puede ver/descargar desde el perfil
- Un usuario sin compras ve un mensaje informativo
- Las compras guest no aparecen en ningún perfil
- El diseño sigue el estilo Neo-Brutalista
