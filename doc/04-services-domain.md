# Servicios de dominio (locks, pagos, QR)

Volver al [índice](./README.md).

## Servicios y responsabilidades

- `SeatLockService`: lock/unlock de asientos de cine (grid).
- `ZoneLockService`: lock/unlock de cupos por zona (`general_admission`).
- `ZoneSeatLockService`: lock/unlock de asientos en zonas `seated`.
- `QueueService`: cola FIFO de admisión.
- `PaymentService`: confirma o falla booking; ocupa inventario final.
- `QrService`: generación de QR para tickets/booking.

## 1) SeatLockService

### Keys Redis

- `seat:lock:{session}:{row}:{col}` -> owner (`identifier`) con TTL.
- `seat:locks:{session}` -> hash de locks de sesión.

### Comportamiento

- Permite relock por el mismo `identifier` (renueva TTL).
- Rechaza lock de tercero si ya existe lock activo.
- Publica eventos `seat:locked` y `seat:released`.
- Valida existencia de asiento contra `venue_config` tipo `grid`.

## 2) ZoneLockService (general admission)

### Objetivo

Reservar **cantidad** en una zona sin asiento concreto.

### Keys Redis

- `zone:lock:{session}:{zone}:{lockId}` -> `{identifier, quantity}` con TTL.
- `zone:reserved:{session}:{zone}` -> contador agregado de reservas temporales.

### Disponibilidad efectiva

$$available = capacity - occupied_{DB} - reserved_{Redis}$$

### Eventos

- `zone:locked`
- `zone:released`

## 3) ZoneSeatLockService (seated)

### Keys Redis

- `zone-seat:lock:{session}:{zone}:{row}:{col}` -> JSON lock con TTL.
- `zone-seat:locks:{session}:{zone}` -> hash row:col -> owner.

### Reglas

- Solo aplica si `zone_type=seated`.
- Rechaza si asiento no existe en `seat_layout`.
- Rechaza si ya está ocupado en DB (`occupied_zone_seats`).
- Publica `zone-seat:locked` / `zone-seat:released`.

## 4) QueueService

### Estructuras Redis

- `queue:{session}` -> lista FIFO.
- `queue:{session}:active` -> set de admitidos.
- `queue:position:{session}:{identifier}` -> cache de posición.

### Flujo

- `join`: evita duplicados y evita reinsertar usuario ya activo.
- `releaseBatch`: extrae hasta `batchSize`, mueve a `active`, recalcula posiciones.
- Publica `queue:updated` en cambios.

### Nota importante

`lpop` vacío puede devolver `null` o `false` según cliente Redis/PHP. El servicio contempla ambos para no generar admisiones fantasma.

## 5) PaymentService

### `process(Booking)`

1. Simula pago (`sleep` + probabilidad de éxito).
2. Si éxito -> `confirmBooking`.
3. Si fallo -> `failBooking`.

### Confirmación

- Crea ocupación final en DB:
  - `occupied_seats`
  - `occupied_zones`
  - `occupied_zone_seats`
- Actualiza tickets a `confirmed`.
- Libera locks de Redis asociados.
- Actualiza booking a `confirmed`.
- Publica `booking:confirmed`.

### Fallo

- Libera locks temporales.
- Actualiza tickets a `failed`.
- Actualiza booking a `failed`.
- Publica `booking:failed`.

## 6) ProcessPayment Job

- Comprueba que booking existe y está en `pending`.
- Publica estado `purchase:processing`.
- Delega en `PaymentService`.

## Consistencia y límites

- Redis = estado temporal de decisión.
- DB = estado final de compra/ocupación.
- Un booking solo se procesa una vez por guard clause de estado.

Para la propagación de eventos en vivo, ver [05-queues-realtime.md](./05-queues-realtime.md).
