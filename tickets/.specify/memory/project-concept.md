# Project Concept: Lógica y Arquitectura

## 1. El Problema de la Concurrencia
En eventos de alta demanda (ej. Conciertos), miles de usuarios intentan reservar el mismo asiento simultáneamente. El sistema debe garantizar que **un asiento/zona solo pueda ser asignado a una persona** mediante operaciones atómicas, y que el servidor no colapse bajo carga masiva.

## 2. Sistema Híbrido de Venue (Grid + Zonas)

### Cine - Mapa de Asientos (Grid)
Las sesiones de cine usan un grid de asientos donde cada celda puede tener 4 estados:
- **No existe** (0): pasillo o espacio vacío, no se renderiza
- **Libre** (1): asiento disponible, color blanco
- **Reservado**: soft lock temporal en Redis, color negro para otros usuarios
- **Comprado**: persistido en `occupied_seats`, color negro permanente

El usuario selecciona asientos individuales haciendo clic en el grid. Cada asiento tiene el **mismo precio** (definido en la sesión).

### Concierto - Mapa de Zonas
Las sesiones de concierto usan un sistema de zonas (Pista, Grada, VIP) donde:
- Cada zona tiene **capacidad máxima** y **precio propio**
- El usuario selecciona una zona y elige **cantidad de entradas**
- No hay asientos individuales: es entrada por zona
- La disponibilidad se calcula: `capacidad - reservas activas en Redis - compras en DB`

El usuario ve un mapa visual con zonas coloreadas y contadores de disponibilidad.

### Configuración del Venue (JSON)
Se almacena en `sessions.venue_config` y define el tipo de mapa:
```json
// CINE
{"type": "grid", "rows": 10, "cols": 15, "layout": [[1,1,0,1,...], ...]}

// CONCIERTO
{"type": "zones", "zones": [{"id": "pista", "name": "Pista", "capacity": 500, "price": 45.00, "color": "#10B981"}, ...]}
```

## 3. Autenticación y Compras

### Sin obligación de registro
El usuario puede comprar entradas **sin crear cuenta**. Solo necesita proporcionar su email. La compra se registra como guest (`bookings.guest_email`).

### Con cuenta (Laravel Sanctum)
Si el usuario quiere crear cuenta, tiene disponible:
- **Registro con email/password**: con verificación de email obligatoria
- **Login con Google OAuth**: vinculación automática de cuenta
- **Recuperación de contraseña**: por email
- Las compras quedan vinculadas a su `user_id` y puede ver su historial

### Doble modalidad en el flujo de compra
```
Usuario llega al checkout
  ├─ Si está logueado → compra con user_id
  └─ Si no está logueado → pide email → compra como guest
```

## 4. Sistema de Doble Cola (FIFO)

### Flujo General
```
Miles de usuarios acceden al evento
        ↓
[COLA 1: SALA DE ESPERA - Redis FIFO]
  └─ Socket.IO notifica posición en tiempo real
        ↓ (liberación controlada en batches)
[MAPA DEL VENUE - Grid o Zonas]
  └─ Usuario selecciona asiento/zona → soft lock en Redis (TTL)
  └─ Socket.IO broadcast: estado actualizado en tiempo real
        ↓
[COLA 2: COLA DE COMPRA - Redis]
  └─ Procesamiento secuencial de pagos (simulados)
        ↓
[CONFIRMACIÓN + QR]
  └─ Socket.IO: notificación de reserva confirmada
```

### Cola 1: Sala de Espera (Waiting Room)
- **Propósito**: Controlar el flujo de usuarios hacia el mapa del venue.
- **Mecanismo**: Cola FIFO en Redis. Los usuarios se posicionan automáticamente al acceder al evento.
- **Liberación**: Se liberan usuarios en batches controlados para no saturar el backend.
- **Feedback**: Socket.IO notifica posición actual y cuándo es su turno.

### Cola 2: Cola de Compra (Purchase Queue)
- **Propósito**: Procesar pagos de forma secuencial y ordenada.
- **Mecanismo**: Una vez el usuario pulsa "Reservar", su petición entra en la cola de compra.
- **Procesamiento**: Laravel Queue Worker procesa cada pago uno a uno.
- **Pago simulado**: Delay aleatorio (2-5s) con 90% de éxito.
- **Feedback**: Socket.IO notifica "Procesando pago...", "Pago confirmado", "Error en el pago".

## 5. Comunicación en Tiempo Real (Socket.IO)
- **Servicio**: Servidor Node.js independiente con Socket.IO (NO Laravel WebSockets).
- **Responsabilidades**:
  - Notificar posición en la sala de espera.
  - Broadcast de asientos/zonas actualizados en tiempo real.
  - Notificar estado del proceso de compra.
  - Feedback de "gestionando su solicitud" durante cargas.
- **Comunicación con Laravel**: El servidor Node.js escucha eventos de Redis (pub/sub) emitidos por Laravel.

## 6. Lógica de Reserva (Soft Lock)

### Para Cine (asientos individuales)
1. **Selección**: Al hacer clic en un asiento, se crea clave en Redis con TTL de 5-10 min.
2. **Estado**: El asiento aparece como "Ocupado" para otros usuarios (Socket.IO).
3. **Persistencia**: Si paga → pasa a `occupied_seats`. Si expira → se libera.

### Para Concierto (zonas con capacidad)
1. **Selección**: Al elegir zona y cantidad, se incrementa `zone:reserved:{session_id}:{zone_id}` en Redis con TTL.
2. **Estado**: La disponibilidad de la zona se actualiza para todos (Socket.IO).
3. **Persistencia**: Si paga → se guarda en `occupied_zones`. Si expira → se decrementa la reserva.

## 7. Imágenes
Las imágenes de eventos se almacenan en **storage local** del servidor Laravel (`storage/app/public/events/`).

## 8. Pilares Técnicos
- **Integridad de Datos**: Transacciones SQL con `SELECT FOR UPDATE` para asientos de cine.
- **Escalabilidad**: Dockerización de servicios independientes.
- **Testing de Carga**: Simulación de peticiones concurrentes con Pest.
- **Desacoplamiento**: Laravel API REST + Node.js Socket.IO + Nuxt frontend.

## 9. Arquitectura de Servicios

| Servicio | Tecnología | Responsabilidad |
|---|---|---|
| Frontend | Nuxt 3 + Tailwind | UI, consume API + escucha Socket.IO |
| API Backend | Laravel 11 (PHP 8.3) + Sanctum | Auth, lógica de negocio, colas, pagos, soft lock |
| Realtime Server | Node.js + Socket.IO | Comunicación en tiempo real |
| Base de Datos | PostgreSQL | Persistencia de eventos, sesiones, reservas |
| Cache/Colas | Redis | Sala de espera, soft locks, pub/sub |
| Infraestructura | Docker + Docker Compose | Orquestación de todos los servicios |
