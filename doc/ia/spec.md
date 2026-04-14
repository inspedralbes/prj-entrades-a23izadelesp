# OpenSpec — Mejora de experiencia realtime en compra de entradas

## Contexto
En sesiones de alta demanda, algunos usuarios reportaban desincronización inicial entre pestañas (locks visuales no coherentes al primer acceso).

## Objetivo
Garantizar identidad única por pestaña para usuarios guest y consistencia de eventos realtime en cola y selección de asientos.

## Requisitos funcionales
1. Cada pestaña guest debe tener un `identifier` independiente.
2. El registro en cola por socket debe producirse al unirse explícitamente a la cola.
3. Los asientos bloqueados deben mostrarse correctamente desde carga inicial y eventos realtime.
4. Al cerrar pestaña, se deben liberar locks temporales del usuario.

## Criterios de aceptación
- Dos pestañas incógnitas distintas no comparten `identifier`.
- El primer acceso ya refleja locks/updates sin necesidad de recargar.
- El flujo de cola mantiene orden FIFO.
- Build frontend y tests de cola/locks pasan en Docker.
