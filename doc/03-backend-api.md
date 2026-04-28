# Backend y API

Volver al [índice](./README.md).

## Stack backend

- Laravel + PHP-FPM.
- Auth: Sanctum (+ Google OAuth en rutas de auth).
- DB: PostgreSQL.
- Redis para locks/cola/pubsub.

## Endpoints principales

### Salud y catálogo

- `GET /api/ping`
- `GET /api/events`
- `GET /api/events/{event}`
- `GET /api/sessions/{session}`
- `GET /api/sessions/{session}/seats`
- `GET /api/sessions/{session}/zones/{zone}/seats`

### Locks de inventario

- Grid cine:
  - `POST /api/sessions/{session}/seats/lock`
  - `DELETE /api/sessions/{session}/seats/unlock`
- Zonas concierto:
  - `POST /api/sessions/{session}/zones/lock`
  - `DELETE /api/sessions/{session}/zones/unlock`
- Asientos por zona (`seated`):
  - `POST /api/sessions/{session}/zones/{zone}/seats/lock`
  - `DELETE /api/sessions/{session}/zones/{zone}/seats/unlock`

### Cola de espera/compra

- `POST /api/sessions/{session}/queue/join`
- `GET /api/sessions/{session}/queue/position?identifier=...`
- `POST /api/sessions/{session}/queue/admit`

### Booking y tickets

- `POST /api/bookings`
- `GET /api/bookings/{booking}`
- `GET /api/bookings/{booking}/qr`

### Admin de plantillas de recinto

- `GET/POST /api/venue-templates`
- `GET/PUT/DELETE /api/venue-templates/{id}`
- `POST /api/venue-templates/{id}/sessions/{session}/apply`

## Flujo de `POST /api/bookings`

`BookingController@store`:

1. Valida payload (`seats`, `zones`, `zone_seats`, `identifier`, `guest_email`).
2. Carga sesión (`AppSession`) y calcula total.
3. Verifica locks en Redis:
   - Grid: `seat:lock:{session}:{row}:{col}`.
   - General admission: `zone:lock:{session}:{zone}:{lockId}`.
   - Seated zone: `zone-seat:lock:{session}:{zone}:{row}:{col}`.
4. Si hay inconsistencias, responde `422` con detalle de no disponibles.
5. Crea `Booking(status=pending)` y `Ticket(s)`.
6. Persistencia auxiliar de lock IDs para trazabilidad (`booking:*` keys en Redis).
7. Encola proceso de pago (`ProcessPayment`), opcional `dispatchSync`.
8. Responde `201` con `id/status/total`.

## SessionController: cálculo de disponibilidad

- Para `grid`: construye matriz y marca `occupied` desde DB.
- Para `zones`:
  - `general_admission`: disponibilidad = capacidad - ocupados(DB) - reservados(Redis).
  - `seated`: disponibilidad por conteo de asientos en layout menos ocupados y bloqueados.

## Auth/Profile

- Auth pública: register/login/forgot/reset, verify email, Google redirect/callback.
- Auth protegida (`auth:sanctum`): logout/user/resend verification.
- Profile tickets con historial de compra.

## Errores comunes y semántica

- `422`: validación o lock no válido/no propiedad del usuario.
- `201`: booking creado y en proceso.
- `200`: consultas de estado/catálogo.

## Relación con otras piezas

- Reglas finas de dominio: [04-services-domain.md](./04-services-domain.md).
- Propagación en tiempo real: [05-queues-realtime.md](./05-queues-realtime.md).
