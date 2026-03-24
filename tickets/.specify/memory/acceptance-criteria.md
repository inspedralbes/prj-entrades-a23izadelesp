# Acceptance Criteria

**Created**: 2026-03-23
**Feature**: Core Booking MVP

## UI/UX Quality
- CHK001 Todas las tarjetas tienen bordes de 2px #000000
- CHK002 Los botones tienen la sombra rígida de 4px
- CHK003 El seat map muestra estados: libre (blanco), ocupado (negro), seleccionado (verde #10B981)
- CHK004 El sticky footer muestra el total y el botón "Reservar Ahora" siempre visible

## Sala de Espera
- CHK005 El usuario recibe su posición en la cola al acceder al evento
- CHK006 La posición se actualiza en tiempo real via Socket.IO
- CHK007 El usuario recibe notificación cuando es su turno de acceder al seat map
- CHK008 La liberación de usuarios es FIFO estricta

## Soft Lock de Asientos
- CHK009 Al seleccionar un asiento, se crea un bloqueo temporal en Redis con TTL
- CHK010 Los asientos bloqueados aparecen como ocupados para el resto de usuarios (broadcast Socket.IO)
- CHK011 Si el TTL expira sin pago, el asiento se libera automáticamente
- CHK012 La API devuelve error 422 si se intenta reservar un asiento ya ocupado

## Cola de Compra
- CHK013 Al pulsar "Reservar", la petición entra en la cola de compra
- CHK014 Los pagos se procesan secuencialmente (uno a uno)
- CHK015 El usuario recibe feedback de estado del pago via Socket.IO

## Confirmación
- CHK016 Se genera un código QR al confirmar la reserva
- CHK017 El QR contiene la información de la reserva (evento, sesión, asientos)
- CHK018 La página de confirmación muestra resumen + QR en estilo Neo-Brutalista

## Técnico
- CHK019 Los tests de Pest pasan al 100% en local
- CHK020 Tests de concurrencia verifican que no hay duplicados en occupied_seats
- CHK021 La comunicación Laravel → Redis pub/sub → Node Socket.IO → Cliente funciona correctamente
