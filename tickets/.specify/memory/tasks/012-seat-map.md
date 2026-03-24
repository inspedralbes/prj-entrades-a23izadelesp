# Task: Frontend - Seat Map Interactivo (Grid + Zonas)

**ID**: T012
**Phase**: 4 - Frontend
**Priority**: Alta
**Status**: Pendiente

## Descripción
Crear el componente principal del proyecto: el mapa interactivo del venue. Soporta dos modos según el tipo de evento: grid de asientos (cine) o mapa de zonas (concierto). Comunicación en tiempo real para actualizar el estado.

## Objetivos

### Modo Cine (Grid)
- Componente `SeatMap.vue` - grid de asientos basado en `venue_config.layout`
- Estados visuales:
  - Blanco (`#FFFFFF`): Libre
  - Negro (`#000000`): Ocupado
  - Verde (`#10B981`): Seleccionado por el usuario actual
  - Gris (`#D1D5DB`): No existe (pasillo)
- Al hacer click en asiento libre → petición `POST /api/sessions/{id}/seats/lock`
- Recepción de eventos Socket.IO `seat:locked` y `seat:released`
- Selección múltiple de asientos

### Modo Concierto (Zonas)
- Componente `ZoneMap.vue` - mapa visual con zonas coloreadas
- Cada zona muestra: nombre, precio, disponibilidad (entradas restantes)
- Al hacer click en zona → selector de cantidad (1-10)
- Petición `POST /api/sessions/{id}/zones/lock`
- Recepción de eventos Socket.IO `zone:locked` y `zone:released`
- La disponibilidad se actualiza en tiempo real

### Común a ambos modos
- Sticky footer con:
  - Resumen de selección (asientos o zona + cantidad)
  - Precio total calculado
  - Botón "Reservar Ahora" (estilo ámbar `#F59E0B`)

## Archivos a crear/modificar
- `frontend/pages/session/[id]/seats.vue`
- `frontend/components/SeatMap.vue` - grid para cine
- `frontend/components/SeatCell.vue` - celda individual del grid
- `frontend/components/ZoneMap.vue` - mapa de zonas para concierto
- `frontend/components/ZoneCard.vue` - tarjeta de zona individual
- `frontend/components/QuantitySelector.vue` - selector de cantidad de entradas
- `frontend/components/BookingFooter.vue` - sticky footer
- `frontend/stores/seats.js` (Pinia)
- `frontend/stores/zones.js` (Pinia)

## Dependencias
- T004-api (API REST sesiones/asientos)
- T005 (Soft lock backend - asientos y zonas)
- T008 (Socket.IO server)
- T011 (Sala de espera - el usuario debe estar admitido)

## Criterios de verificación
- **Cine**: El grid se renderiza según `venue_config.layout`
- **Cine**: Click en asiento libre lo bloquea y cambia a verde
- **Cine**: Otro usuario ve el asiento bloqueado en negro en tiempo real
- **Concierto**: Las zonas se muestran con nombre, precio y disponibilidad
- **Concierto**: El selector de cantidad no permite exceder la capacidad
- **Concierto**: La disponibilidad se actualiza en tiempo real para todos
- Si el soft lock expira, la selección se libera para todos
- El sticky footer muestra el resumen y total correctamente
- El botón "Reservar Ahora" solo está habilitado con selección
