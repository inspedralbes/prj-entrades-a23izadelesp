# Arquitectura del sistema

Volver al [índice](./README.md).

## Vista de alto nivel

```text
Navegador (Nuxt SPA)
  ├─ HTTPS / -> Nginx (estático frontend)
  ├─ HTTPS /api -> Nginx -> PHP-FPM (Laravel)
  └─ WSS /socket.io -> Nginx -> Node Socket.IO

Laravel <-> PostgreSQL (estado persistente)
Laravel <-> Redis (locks, cola, pub/sub, estado efímero)
Node Socket.IO <-> Redis pub/sub (eventos de negocio en vivo)
```

## Servicios en producción (compose)

- `web`: Nginx + frontend generado (`npm run generate`).
- `laravel`: API (PHP-FPM).
- `laravel-worker`: procesamiento de jobs de cola (`queue:work`).
- `node-socket`: gateway de tiempo real.
- `postgres`: almacenamiento transaccional.
- `redis`: locks, cola, estado temporal y pub/sub.

Más detalle en [08-infra-deploy.md](./08-infra-deploy.md).

## Capas lógicas

1. **Presentación**: Nuxt + componentes de mapa/cola.
2. **API**: controladores REST Laravel.
3. **Dominio**: servicios (`SeatLockService`, `QueueService`, `PaymentService`, etc.).
4. **Integración tiempo real**: Redis pub/sub y Socket.IO.
5. **Persistencia**: PostgreSQL para entidades finales.

## Patrón de eventos

- Laravel publica eventos de negocio en Redis (`seat:locked`, `queue:updated`, `booking:*`).
- `node-socket` suscribe esos canales y reemite a room `session:<id>` o socket destino.
- Frontend recibe evento y sincroniza UI sin polling agresivo.

## Separación de responsabilidades

- **Laravel** decide reglas y valida operaciones.
- **Redis** mantiene estado temporal y coordinación concurrente.
- **Node realtime** no aplica negocio de inventario final; distribuye estado.
- **PostgreSQL** guarda estado fuente de verdad duradero (bookings/tickets/ocupación).

## Riesgos técnicos conocidos

- Divergencia temporal Redis/DB en fallos abruptos.
- Operación de cola en dos sitios (Laravel `QueueService` y `node-socket/queueManager`).
- Necesidad de mantener naming/TTL coherentes entre servicios.

Estos puntos se detallan en [05-queues-realtime.md](./05-queues-realtime.md).
