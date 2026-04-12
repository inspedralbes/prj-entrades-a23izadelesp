# Cues i temps real (nucli del projecte)

Volver al [índex](./README.md).

## Objectiu

Garantir compra justa i consistència sota càrrega alta:

- Ordenar usuaris amb FIFO.
- Evitar que dos usuaris comprin el mateix inventari.
- Informar en viu de posició, admissió i canvis d'inventari.

## Arquitectura de la cua

Hi ha dos mecanismes que conviuen:

1. **Laravel `QueueService`**
   - API de join/position/admit.
   - Manté estructura a Redis i publica `queue:updated`.
2. **Node `queueManager` (realtime)**
   - Bucle d'admissió periòdic (`ADMISSION_INTERVAL`, `ADMISSION_BATCH_SIZE`).
   - Emet `queue:admitted` i actualitza posicions/restants.

## Estructures Redis principals

- `queue:{session}` (list): cua FIFO.
- `queue:{session}:active` (set): admesos.
- `queue:position:{session}:{identifier}` (string): posició cachejada.
- `active_sessions` (set): sessions amb activitat realtime.

## Canals pub/sub

`node-socket` subscriu canals amb i sense prefix:

- `queue:updated`
- `seat:locked`, `seat:released`
- `zone:locked`, `zone:released`
- `zone-seat:locked`, `zone-seat:released`
- `booking:processing`, `booking:confirmed`, `booking:failed`

I redistribueix via Socket.IO:

- Room: `session:{session_id}` per esdeveniments de sessió.
- Socket específic (`socket_id`) per estat de booking individual.

## Protocol Socket.IO

### Entrada (client -> server)

- `join:session` (`sessionId`): uneix socket a room de sessió.
- `register:queue` (`{session_id, identifier}`): registra identitat per cua.

### Sortida (server -> client)

- `queue:position`
- `queue:admitted`
- `queue:remaining`
- `seat:locked` / `seat:released`
- `zone:locked` / `zone:released`
- `zone-seat:locked` / `zone-seat:released`
- `booking:processing` / `booking:confirmed` / `booking:failed`

## Flux complet de compra (resumit)

1. Usuari entra a sessió i s'uneix a cua.
2. Quan és admès, passa a flux de compra.
3. Bloqueja inventari (seat/zone/zone-seat) amb TTL.
4. Crea `booking` (`pending`) via API.
5. Job `ProcessPayment` processa pagament.
6. Si èxit: ocupa inventari en DB i confirma booking/tickets.
7. Si error: allibera locks i marca `failed`.
8. Frontend rep estat en viu i actualitza UI.

## Locking: garanties i límits

### Garanties

- Lock associat a `identifier` (propietat de lock).
- Rebuig de compra si lock no existeix o no és del comprador.
- TTL evita bloqueig infinit.

### Límits

- Sistema eventual: Redis i DB poden desfasar-se momentàniament.
- Si cau un procés en mal moment, cal observabilitat/logs i recuperació.

## Realtime subscriber (Node)

`redisSubscriber` normalitza canal (`queuely-database-...` -> canal base) i publica cap a:

- room de sessió (`session:{id}`), o
- socket concret (`socket_id`) per booking events.

## Paràmetres operatius

- `ADMISSION_BATCH_SIZE` (default 10)
- `ADMISSION_INTERVAL` (default 5000ms)
- `SOCKET_PORT` (default 3001)
- `REDIS_URL`

## Detecció de problemes habituals

### Usuari veu posició incorrecta

- Revisar `queue:{session}` i `queue:position:*` a Redis.
- Revisar publicació `queue:updated` en logs.

### 2 usuaris amb mateix seat

- Revisar claus `seat:lock:*` i ocupació final `occupied_seats`.
- Verificar que booking només avança amb locks propis.

### Estat booking no arriba al client

- Confirmar `socket_id` guardat a Redis (`booking_socket:{id}`).
- Confirmar que `redisSubscriber` rep i emet `booking:*`.

## Relació amb altres docs

- API de cua i booking: [03-backend-api.md](./03-backend-api.md)
- Implementació de serveis: [04-services-domain.md](./04-services-domain.md)
- Deploy/operació: [08-infra-deploy.md](./08-infra-deploy.md)
