# Task: Servidor Socket.IO (Node.js)

**ID**: T006
**Phase**: 2 - Realtime
**Priority**: Alta
**Status**: Pendiente

## Descripción
Crear el servidor Node.js independiente con Socket.IO que gestiona toda la comunicación en tiempo real. Escucha eventos de Redis pub/sub publicados por Laravel y los reenvía a los clientes conectados.

## Objetivos
- Servidor Socket.IO corriendo en puerto 3001
- Rooms por `session_id` para agrupar usuarios del mismo evento
- Listener de Redis pub/sub que captura eventos de Laravel
- Eventos Socket.IO al cliente:
  - `queue:position` - posición en la sala de espera
  - `queue:admitted` - el usuario puede acceder al seat map
  - `seat:locked` - un asiento ha sido bloqueado
  - `seat:released` - un asiento ha sido liberado
  - `booking:processing` - el pago está siendo procesado
  - `booking:confirmed` - la reserva está confirmada
  - `booking:failed` - el pago ha fallado
- Feedback: "Gestionando su solicitud..." durante procesos largos
- Dockerfile para containerización

## Archivos a crear/modificar
- `realtime/src/server.js` - Servidor Socket.IO principal
- `realtime/src/redisSubscriber.js` - Listener Redis pub/sub
- `realtime/src/queueManager.js` - Lógica de gestión de cola en Socket.IO
- `realtime/src/rooms.js` - Gestión de rooms por session_id
- `realtime/package.json` - Dependencias (socket.io, ioredis)
- `realtime/Dockerfile`

## Canales Redis a escuchar
- `seat:locked`, `seat:released`
- `queue:updated`
- `booking:processing`, `booking:confirmed`, `booking:failed`

## Dependencias
- T001 (Docker + Redis)

## Criterios de verificación
- El servidor acepta conexiones Socket.IO en puerto 3001
- Los clientes se unen al room correcto por `session_id`
- Los eventos de Redis se reenvían correctamente a los clientes
- La desconexión de un cliente limpia su suscripción
