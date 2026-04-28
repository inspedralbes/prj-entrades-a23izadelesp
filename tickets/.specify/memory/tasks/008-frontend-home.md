# Task: Frontend - Home Page y Listado de Eventos

**ID**: T007
**Phase**: 3 - Frontend
**Priority**: Media
**Status**: Pendiente

## Descripción
Crear la página principal con el listado de eventos en formato Bento Grid con estilo Neo-Brutalista. Cada tarjeta de evento muestra imagen, título y tag (Cine/Concierto).

## Objetivos
- Home page con Bento Grid de eventos
- Tarjetas con bordes 2px #000000, sin border-radius, sombras 4px
- Filtros por tipo: Todos / Cine / Conciertos
- Carga de eventos desde la API Laravel (`GET /api/events`)
- Links a la página de detalle del evento

## Archivos a crear/modificar
- `frontend/pages/index.vue`
- `frontend/components/EventCard.vue`
- `frontend/components/TopBar.vue`
- `frontend/composables/useApi.js`
- `frontend/stores/events.js` (Pinia)
- `frontend/assets/css/tailwind.config.js` (colores Neo-Brutalism)

## Estilo Neo-Brutalism (recordatorio)
- Bordes: `2px solid #000000`
- Radios: `border-radius: 0px`
- Sombras: `box-shadow: 4px 4px 0px 0px #000000`
- Fondo: `#F3F4F6`
- Acento primario: `#10B981`
- Acento secundario: `#F59E0B`
- Tipografía: Plus Jakarta Sans

## Dependencias
- T003 (API REST eventos)

## Criterios de verificación
- La home page carga y muestra los eventos desde la API
- Las tarjetas tienen el estilo Neo-Brutalista correcto
- Los filtros funcionan correctamente
- Click en una tarjeta navega a `/events/{id}`
