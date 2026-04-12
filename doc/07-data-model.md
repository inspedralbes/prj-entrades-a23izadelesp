# Model de dades

Volver al [índex](./README.md).

## Entitats principals

- `events`
  - Tipus: `movie` o `concert`.
- `app_sessions`
  - Sessió concreta (data/hora/preu/config recinte).
- `bookings`
  - Reserva creada durant compra.
  - Estat: `pending`, `confirmed`, `cancelled`, `failed`.
- `tickets`
  - Unitats de compra (seat o zona).

## Entitats d'ocupació final

- `occupied_seats` (grid cinema)
- `occupied_zones` (general admission)
- `occupied_zone_seats` (seated zones)

Aquestes taules representen inventari ja confirmat.

## Entitats de configuració de recinte

- `venue_templates`
- `venue_template_zones`
- `zones` (aplicades a `app_sessions`)

## Relacions clau

- `event 1..n app_sessions`
- `app_session 1..n bookings`
- `booking 1..n tickets`
- `app_session 1..n zones`
- `booking 1..n occupied_*` (segons tipus)

## `venue_config` (JSON) a sessió

### Movie

```json
{
  "type": "grid",
  "rows": 12,
  "cols": 16,
  "layout": [[1,1,0,...]]
}
```

### Concert

```json
{
  "type": "zones",
  "zones": [
    {"id":"pista","name":"Pista","capacity":900,"price":49.0,"color":"#10B981"}
  ]
}
```

## Constrains i validacions importants

- `bookings.status` té check constraint a PostgreSQL.
- Migració recent inclou estat `failed`.
- `tickets.status` és string amb cicle `pending -> confirmed/failed`.

## Dades efímeres (Redis)

No són taules però formen part del model operatiu:

- Locks (`seat:*`, `zone:*`, `zone-seat:*`)
- Cua (`queue:*`)
- Metadades de booking/socket (`booking_socket:*`, `booking:zone_lock:*`)

## Seed de demo

`DatabaseSeeder` crea:

- 2 usuaris de prova.
- Esdeveniments movie/concert.
- Sessions i zones de mostra.
- Neteja transaccional prèvia (`occupied_*`, `tickets`, `bookings`).
