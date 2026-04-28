# Frontend i flux d'usuari

Volver al [índex](./README.md).

## Stack frontend

- Nuxt 3
- Pinia
- Tailwind
- Socket.IO client
- Build de producció estàtica (`npm run generate`)

## Composables clau

- `useApi.ts`
  - Wrapper de `fetch` per `GET/POST/PUT/DELETE`.
  - Usa `runtimeConfig.public.apiBase`.
  - Inclou `Authorization: Bearer` si hi ha token.
- `useSocket.ts`
  - Connexió Socket.IO i API de `emit/on/off`.
- `useQueue.ts`
  - Lògica de posició/admissió de cua.
  - Genera `identifier` de user o guest.

## Estat d'autenticació i identificació

- Usuari registrat: `identifier = user_<token_id>`.
- Guest: `identifier = guest_<timestamp>_<random>` (persistit a `localStorage`).

Aquesta `identifier` és crítica per a:

- Propietat de locks.
- Posició de cua.
- Validació de booking.

## Flux principal de reserva

1. L'usuari entra a event/sessió.
2. Frontend consulta estat de cua (`/queue/position`).
3. Connexió socket a `session:{id}`.
4. Rep `queue:admitted` i passa a compra.
5. Bloqueja inventari (seat/zone/zone-seat).
6. Envia `POST /bookings`.
7. Espera feedback de `booking:*` en viu.
8. Mostra confirmació/fracàs.

## Pàgines rellevants

- Home/event listing.
- Detall d'event i sessions.
- Checkout (`pages/events/[id]/checkout/[sessionId].vue`).
- Mapa de seients/zones.
- Confirmació de booking.
- Login/register i perfil de tickets.

## Gestió d'errors

`useApi` retorna `null` en error i exposa `error.value`.

Recomanació de manteniment:

- Mostrar fallback d'UI quan endpoint cau temporalment.
- Evitar hard-fail global de pantalla per errors puntuals.

## Socket lifecycle

- `init()` a entrada de flux.
- `cleanup()` en sortir de pantalla/flux.
- En `disconnect`, backend realtime pot retirar l'usuari de cua activa segons context.

## Config pública runtime

- `apiBase` (`API_BASE_URL`, per defecte `/api`)
- `socketUrl` (`SOCKET_URL`)

## Notes de generació estàtica

Producció serveix HTML/JS estàtic via Nginx.
Per evitar prerenders fràgils depenent de backend runtime, s'ha fixat el mode client-side al `nuxt.config.ts`.

Veure també:

- [03-backend-api.md](./03-backend-api.md)
- [05-queues-realtime.md](./05-queues-realtime.md)
