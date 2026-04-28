# Task: Frontend - Componente Sala de Espera

**ID**: T009
**Phase**: 3 - Frontend
**Priority**: Media
**Status**: Pendiente

## Descripción
Crear el componente visual de la sala de espera que muestra al usuario su posición en la cola, tiempo estimado, y feedback en tiempo real via Socket.IO.

## Objetivos
- Componente `WaitingRoom.vue` con diseño Neo-Brutalista
- Muestra posición actual en la cola (actualizada en tiempo real)
- Mensaje de feedback: "Gestionando su solicitud..."
- Barra de progreso o indicador visual de espera
- Notificación cuando el usuario es admitido al seat map
- Animación suave al cambiar de posición

## Archivos a crear/modificar
- `frontend/components/WaitingRoom.vue`
- `frontend/composables/useQueue.js`

## Eventos Socket.IO a escuchar
- `queue:position` - actualiza la posición mostrada
- `queue:admitted` - transición al seat map

## Dependencias
- T005 (QueueService backend)
- T006 (Socket.IO server)

## Criterios de verificación
- La posición se actualiza en tiempo real sin recargar la página
- Al ser admitido, el usuario es redirigido al seat map automáticamente
- El diseño sigue el estilo Neo-Brutalista
- El feedback visual es claro y no confunde al usuario
