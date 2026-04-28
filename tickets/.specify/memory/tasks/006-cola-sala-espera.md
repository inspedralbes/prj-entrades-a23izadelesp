# Task: Cola de Sala de Espera (FIFO en Redis)

**ID**: T005
**Phase**: 1 - Backend
**Priority**: Alta
**Status**: Pendiente

## Descripción
Implementar el servicio de cola FIFO para la sala de espera. Los usuarios se posicionan en la cola al acceder a un evento de alta demanda y se liberan en batches controlados para no saturar el servidor.

## Objetivos
- `QueueService` con métodos: `join()`, `getPosition()`, `releaseBatch()`, `isActive()`
- Cola FIFO por `session_id` en Redis
- Liberación en batches configurables (ej. cada 10 segundos se liberan 5 usuarios)
- Publicar evento Redis pub/sub `queue:updated` al cambiar posiciones
- Endpoint: `POST /api/sessions/{id}/queue/join` - entrar en la cola
- Endpoint: `GET /api/sessions/{id}/queue/position` - consultar posición
- Endpoint: `POST /api/sessions/{id}/queue/admit` - admitir batch (admin/cron)

## Archivos a crear/modificar
- `backend/app/Services/QueueService.php`
- `backend/app/Http/Controllers/Api/QueueController.php`
- `backend/app/Events/QueueUpdated.php`
- `backend/routes/api.php`
- `backend/tests/Feature/Services/QueueTest.php`

## Estructura Redis
```
queue:{session_id} → List [user_id_1, user_id_2, ...] (FIFO)
queue:{session_id}:active → Set {user_id_a, user_id_b, ...} (usuarios con acceso)
queue:position:{session_id}:{user_id} → posición numérica
```

## Dependencias
- T001 (Redis en Docker)

## Criterios de verificación
- Los usuarios entran en la cola en orden FIFO
- `getPosition()` devuelve la posición correcta
- `releaseBatch()` mueve N usuarios de la cola al set de activos
- El evento `queue:updated` se publica al cambiar posiciones
- Un usuario activo no puede entrar de nuevo en la cola de la misma sesión
