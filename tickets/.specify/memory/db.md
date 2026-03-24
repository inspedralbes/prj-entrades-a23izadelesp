# Database Schema

## PostgreSQL - Tablas SQL

### users
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigIncrements | PK |
| name | string | |
| email | string | unique |
| email_verified_at | timestamp | nullable, verificación de email |
| password | string | nullable (null si login solo Google) |
| google_id | string | nullable, ID de Google OAuth |
| avatar | string | nullable, URL del avatar |
| remember_token | string | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### personal_access_tokens (Sanctum)
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigIncrements | PK |
| tokenable_type | string | Polymorphic |
| tokenable_id | unsignedBigInteger | Polymorphic |
| name | string | |
| token | string | unique, hashed |
| abilities | text | nullable |
| last_used_at | timestamp | nullable |
| expires_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### password_reset_tokens
| Campo | Tipo | Notas |
|-------|------|-------|
| email | string | PK |
| token | string | |
| created_at | timestamp | nullable |

### events
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigIncrements | PK |
| title | string | |
| description | text | nullable |
| type | enum | 'movie', 'concert' |
| image | string | nullable, path local storage |
| created_at | timestamp | |
| updated_at | timestamp | |

### sessions
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigIncrements | PK |
| event_id | foreignId | FK → events.id |
| date | date | |
| time | time | |
| price | decimal(8,2) | nullable, precio fijo (cine) |
| venue_config | json | Configuración del venue (ver abajo) |
| created_at | timestamp | |
| updated_at | timestamp | |

#### venue_config - Formato JSON

**Cine (tipo: grid):**
```json
{
  "type": "grid",
  "rows": 10,
  "cols": 15,
  "layout": [
    [1,1,1,0,0,1,1,1,1,1,1,0,0,1,1],
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
  ]
}
```
- `1` = asiento existe
- `0` = no existe asiento (pasillo, espacio)

**Concierto (tipo: zones):**
```json
{
  "type": "zones",
  "zones": [
    {"id": "pista", "name": "Pista", "capacity": 500, "price": 45.00, "color": "#10B981"},
    {"id": "grada_izq", "name": "Grada Izquierda", "capacity": 200, "price": 60.00, "color": "#F59E0B"},
    {"id": "grada_der", "name": "Grada Derecha", "capacity": 200, "price": 60.00, "color": "#F59E0B"},
    {"id": "vip", "name": "VIP", "capacity": 50, "price": 120.00, "color": "#EF4444"}
  ]
}
```

### bookings
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigIncrements | PK |
| user_id | foreignId | nullable, FK → users.id (null si guest) |
| guest_email | string | nullable, email del guest |
| session_id | foreignId | FK → sessions.id |
| status | enum | 'pending', 'confirmed', 'cancelled' |
| total | decimal(8,2) | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Constraint**: `user_id` o `guest_email` debe existir (no ambos null).

### occupied_seats (solo para cine - grid)
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigIncrements | PK |
| booking_id | foreignId | FK → bookings.id |
| session_id | foreignId | FK → sessions.id (index) |
| row | integer | |
| col | integer | |
| created_at | timestamp | |

**Índice único**: `(session_id, row, col)` - impide duplicados en la misma sesión.

### occupied_zones (solo para conciertos - zones)
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigIncrements | PK |
| booking_id | foreignId | FK → bookings.id |
| session_id | foreignId | FK → sessions.id (index) |
| zone_id | string | ID de la zona (ej. "pista", "vip") |
| quantity | integer | Número de entradas reservadas |
| created_at | timestamp | |

## Relaciones SQL
```
users ──1:N──▶ bookings
events ──1:N──▶ sessions
sessions ──1:N──▶ bookings
sessions ──1:N──▶ occupied_seats
sessions ──1:N──▶ occupied_zones
bookings ──1:N──▶ occupied_seats
bookings ──1:N──▶ occupied_zones
```

## Redis - Estructura de Claves

### Sala de Espera (FIFO)
| Clave | Tipo | TTL | Contenido |
|-------|------|-----|-----------|
| `queue:{session_id}` | List | Sin TTL | Lista ordenada de identificadores (user_id o session_hash para guests) |
| `queue:{session_id}:active` | Set | Sin TTL | Identificadores con acceso al mapa |
| `queue:position:{session_id}:{identifier}` | String | Mientras esté en cola | Posición numérica |

### Soft Lock - Asientos (Cine)
| Clave | Tipo | TTL | Contenido |
|-------|------|-----|-----------|
| `seat:lock:{session_id}:{row}:{col}` | String | 5-10 min | identifier que tiene el bloqueo |
| `seat:locks:{session_id}` | Hash | Sin TTL | Mapa de todos los locks activos |

### Soft Lock - Zonas (Concierto)
| Clave | Tipo | TTL | Contenido |
|-------|------|-----|-----------|
| `zone:lock:{session_id}:{zone_id}:{lock_id}` | String | 5-10 min | JSON con {identifier, quantity} |
| `zone:reserved:{session_id}:{zone_id}` | Integer | Sin TTL | Cantidad total reservada en la zona |

### Cola de Compra
| Clave | Tipo | TTL | Contenido |
|-------|------|-----|-----------|
| `purchase:queue` | List | Sin TTL | Lista de booking_ids pendientes de pago |

### Pub/Sub (Laravel → Node Socket.IO)
| Canal | Evento | Payload |
|-------|--------|---------|
| `seat:locked` | Asiento bloqueado | {session_id, row, col, identifier} |
| `seat:released` | Asiento liberado (TTL expirado) | {session_id, row, col} |
| `zone:locked` | Zona reservada | {session_id, zone_id, quantity, availability} |
| `zone:released` | Zona liberada | {session_id, zone_id, availability} |
| `queue:updated` | Posición de cola actualizada | {session_id, identifier, position} |
| `booking:confirmed` | Reserva confirmada | {booking_id, identifier, qr_data} |
| `purchase:processing` | Pago en proceso | {booking_id, identifier} |
| `purchase:failed` | Pago fallido | {booking_id, identifier, reason} |
