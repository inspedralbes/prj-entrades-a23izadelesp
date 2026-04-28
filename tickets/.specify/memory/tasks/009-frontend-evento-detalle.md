# Task: Frontend - Detalle de Evento y Selector de Sesión

**ID**: T008
**Phase**: 3 - Frontend
**Priority**: Media
**Status**: Pendiente

## Descripción
Crear la página de detalle del evento con imagen hero, información en Bento boxes, y selector de sesión (días/horarios). Al seleccionar una sesión, el usuario accede al seat map o a la sala de espera.

## Objetivos
- Página `/events/{id}` con detalle del evento
- Imagen hero ocupando el 40% superior con borde negro inferior
- Bento boxes con duración, género, calificación
- Selector horizontal de sesiones (cuadrados blancos, ámbar al seleccionar)
- Al seleccionar sesión: intenta acceder al seat map o entra en la sala de espera
- Conexión Socket.IO inicial para escuchar eventos de la sesión

## Archivos a crear/modificar
- `frontend/pages/events/[id].vue`
- `frontend/components/SessionSelector.vue`
- `frontend/composables/useSocket.js`
- `frontend/stores/session.js` (Pinia)

## Dependencias
- T003 (API REST)
- T006 (Socket.IO server)
- T007 (Home page)

## Criterios de verificación
- La página carga el detalle del evento desde la API
- El selector de sesiones muestra las sesiones disponibles
- Al seleccionar una sesión, se establece conexión Socket.IO
- Si el usuario está en la cola, se muestra el componente WaitingRoom
- Si el usuario es activo, se navega al seat map
