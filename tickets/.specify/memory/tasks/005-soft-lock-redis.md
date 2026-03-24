# Task: Soft Lock de Asientos y Zonas en Redis

**ID**: T005
**Phase**: 1 - Backend
**Priority**: Alta
**Status**: Pendiente

## Descripción
Implementar el servicio de bloqueo temporal usando Redis. Soporta dos modos: asientos individuales (cine - grid) y zonas con capacidad (concierto). El soft lock tiene TTL configurable y se libera automáticamente al expirar.

## Objetivos
- `SeatLockService` para asientos individuales (cine):
  - `lockSeat()`, `releaseSeat()`, `isSeatLocked()`, `getSeatLocks()`
- `ZoneLockService` para zonas (concierto):
  - `lockZone()`, `releaseZone()`, `getZoneAvailability()`, `getZoneReservedCount()`
- Bloqueo con TTL configurable (5-10 minutos)
- Endpoints para bloquear/liberar
- Publicar eventos Redis pub/sub al bloquear/liberar
- Tests Pest de concurrencia

## Endpoints
| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/sessions/{id}/seats/lock` | Bloquear asiento (cine) |
| DELETE | `/api/sessions/{id}/seats/unlock` | Liberar asiento (cine) |
| POST | `/api/sessions/{id}/zones/lock` | Reservar zona (concierto) |
| DELETE | `/api/sessions/{id}/zones/unlock` | Liberar zona (concierto) |

## Archivos a crear/modificar
- `backend/app/Services/SeatLockService.php` - soft lock para asientos (grid)
- `backend/app/Services/ZoneLockService.php` - soft lock para zonas
- `backend/app/Http/Controllers/Api/SeatController.php`
- `backend/app/Events/SeatLocked.php`
- `backend/app/Events/SeatReleased.php`
- `backend/app/Events/ZoneLocked.php`
- `backend/app/Events/ZoneReleased.php`
- `backend/routes/api.php`
- `backend/tests/Feature/Services/SeatLockTest.php`
- `backend/tests/Feature/Services/ZoneLockTest.php`

## Estructura Redis

### Asientos (Cine)
```
seat:lock:{session_id}:{row}:{col} → identifier (TTL: 5-10 min)
seat:locks:{session_id} → Hash {row:col → identifier}
```

### Zonas (Concierto)
```
zone:lock:{session_id}:{zone_id}:{lock_id} → JSON {identifier, quantity} (TTL: 5-10 min)
zone:reserved:{session_id}:{zone_id} → Integer (cantidad total reservada)
```

## Dependencias
- T002 (Modelos)
- T001 (Redis en Docker)

## Criterios de verificación
- **Cine**: Al bloquear un asiento, la clave aparece en Redis con TTL
- **Cine**: Al intentar bloquear un asiento ya ocupado, devuelve error 422
- **Concierto**: Al reservar zona, se incrementa el contador de reservas
- **Concierto**: No se puede reservar más entradas que la capacidad de la zona
- Al expirar el TTL, el asiento/zona se libera automáticamente
- Los eventos pub/sub se publican correctamente
- Tests Pest de concurrencia pasan (no hay race conditions)
