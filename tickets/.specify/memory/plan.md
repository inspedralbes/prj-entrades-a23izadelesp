# Implementation Plan: BentoTickets Core Booking Flow

**Branch**: `001-core-booking-flow` | **Date**: 2026-03-24 | **Spec**: [spec.md](spec.md)

## Summary
Plataforma de reserva de entradas de alto rendimiento con sistema de doble cola (sala de espera + cola de compra), comunicación en tiempo real via Socket.IO, soft locking de asientos/zonas en Redis, autenticación Sanctum + Google OAuth, guest checkout, y envío de emails. Stack: Laravel 11 + Nuxt 3 + Node.js Socket.IO + Redis + PostgreSQL + Mailpit.

## Technical Context

**Language/Version**: PHP 8.3, Node.js 20+, Vue 3 (Composition API)
**Primary Dependencies**: Laravel 11, Nuxt 3, Socket.IO, Tailwind CSS, Pest, Vitest, Sanctum, Socialite
**Storage**: PostgreSQL (datos persistentes), Redis (colas, soft locks, pub/sub)
**Testing**: Pest (backend), Vitest (frontend), Cypress (E2E)
**Target Platform**: Docker (Linux containers), browser (Nuxt 3 SSR/SPA)
**Project Type**: Web application (API backend + Realtime server + Frontend)
**Performance Goals**: Soportar picos de miles de usuarios concurrentes en eventos de alta demanda
**Constraints**: Cola FIFO estricta, soft lock con TTL, pagos simulados procesados secuencialmente
**Scale/Scope**: MVP - listado de eventos, auth, seat map híbrido (grid/zonas), doble cola, checkout con QR, email, perfil

## Architecture Overview

```
┌─────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Nuxt 3    │────▶│  Laravel 11 API  │────▶│   PostgreSQL    │
│  (Frontend) │     │  (Business Logic)│     │   (Persistencia)│
└──────┬──────┘     └────────┬─────────┘     └─────────────────┘
       │                     │
       │ Socket.IO           │ Redis pub/sub
       │                     ▼
       │              ┌──────────────┐
       └─────────────▶│  Node.js     │
                      │  Socket.IO   │
                      │  (Realtime)  │
                      └──────┬───────┘
                             │
                      ┌──────▼───────┐
                      │    Redis     │
                      │ (Colas+Lock) │
                      └──────────────┘
```

## Project Structure

```
backend/                          # Laravel 11 API
├── app/
│   ├── Http/Controllers/Api/     # EventController, SessionController, BookingController, AuthController, ProfileController
│   ├── Http/Requests/            # Form Requests (RegisterRequest, LoginRequest)
│   ├── Models/                   # User, Event, Session, Booking, OccupiedSeat, OccupiedZone
│   ├── Services/                 # QueueService, SeatLockService, ZoneLockService, PaymentService, QrService
│   ├── Jobs/                     # ProcessPayment, SendBookingConfirmationEmail
│   ├── Mail/                     # BookingConfirmationMail
│   ├── Events/                   # SeatLocked, ZoneLocked, BookingConfirmed (Redis pub/sub)
│   └── Http/Resources/           # EventResource, SessionResource, SeatResource, ZoneResource, TicketResource
├── database/migrations/
├── routes/api.php
└── tests/Feature/                # Pest tests

realtime/                         # Node.js + Socket.IO
├── src/
│   ├── server.js                 # Socket.IO server
│   ├── redisSubscriber.js        # Escucha eventos de Laravel via Redis pub/sub
│   ├── queueManager.js           # Gestión de sala de espera FIFO
│   └── rooms.js                  # Gestión de rooms por session_id
├── package.json
└── Dockerfile

frontend/                         # Nuxt 3
├── pages/
│   ├── index.vue                 # Home - listado de eventos
│   ├── events/[id].vue           # Detalle del evento
│   ├── session/[id]/seats.vue    # Seat Map (grid o zonas)
│   ├── booking/[id]/confirmed.vue # Confirmación con QR
│   ├── profile/tickets.vue       # Historial de compras (auth)
│   ├── profile/tickets/[id].vue  # Detalle de entrada (auth)
│   ├── auth/login.vue            # Login
│   └── auth/register.vue         # Registro
├── components/
│   ├── SeatMap.vue               # Grid interactivo de asientos (cine)
│   ├── SeatCell.vue              # Celda individual del grid
│   ├── ZoneMap.vue               # Mapa de zonas (concierto)
│   ├── ZoneCard.vue              # Tarjeta de zona individual
│   ├── WaitingRoom.vue           # Interfaz de sala de espera
│   ├── BookingFooter.vue         # Sticky footer con total y botón
│   ├── BookingSummary.vue        # Resumen de la reserva
│   └── TicketCard.vue            # Tarjeta en historial de compras
├── composables/
│   ├── useSocket.js              # Conexión Socket.IO
│   ├── useQueue.js               # Estado de la cola
│   └── useApi.js                 # Peticiones a la API
├── stores/                       # Pinia stores
└── nuxt.config.ts

nginx/
└── default.conf                  # Proxy inverso

docker-compose.yml                # Orquestación: nginx, laravel, node-socket, postgres, redis, mailpit
.github/workflows/
├── ci.yml                        # Tests (Pest, Vitest, lint)
└── cd.yml                        # Build Docker images + deploy
```

## Implementation Phases

Las tareas individuales están en `/tickets/.specify/memory/tasks/`.

### Phase 0: Infraestructura Base
Docker Compose con servicios: nginx, laravel, node-socket, postgres, redis, mailpit. Laravel 11 con conexión a PostgreSQL y Redis. Nuxt 3 con proxy a API Laravel. Servidor Node.js básico con Socket.IO. Verificación de comunicación: Laravel → Redis pub/sub → Node Socket.IO → Cliente. CI/CD con GitHub Actions.

### Phase 1: Backend - Auth, Modelos y API
Autenticación: Sanctum + Google OAuth + verificación de email + recuperación de contraseña. Migraciones: `users` (con auth), `personal_access_tokens`, `password_reset_tokens`, `events`, `sessions` (con venue_config JSON), `bookings` (con guest_email), `occupied_seats` (cine), `occupied_zones` (concierto). Models Eloquent con relaciones. Controllers API REST. SeatLockService (asientos individuales) y ZoneLockService (zonas con capacidad). QueueService (sala de espera FIFO). Seeder con eventos de cine y concierto. Tests Pest.

### Phase 2: Realtime Server (Node.js + Socket.IO)
Servidor Socket.IO con rooms por `session_id`. Listener de Redis pub/sub. Eventos: `queue:position`, `seat:locked`, `seat:released`, `zone:locked`, `zone:released`, `booking:confirmed`. Gestión de sala de espera FIFO con batches. Feedback al usuario.

### Phase 3: Frontend - Auth, Descubrimiento y Eventos
Páginas de login y registro. Home page con Bento Grid de eventos (Neo-Brutalism). Página de detalle de evento con selector de sesión. Conexión Socket.IO desde Nuxt. Componente WaitingRoom con feedback de posición.

### Phase 4: Frontend - Seat Map (Grid + Zonas)
SeatMap.vue para cine (grid de asientos con estados: libre, ocupado, no existe, seleccionado). ZoneMap.vue para conciertos (zonas coloreadas con disponibilidad y selector de cantidad). Recepción de eventos Socket.IO para actualización en tiempo real. Sticky footer con total y botón "Reservar Ahora". Perfil de usuario con historial de compras.

### Phase 5: Flujo de Compra y Confirmación
Cola de compra: petición entra en cola Redis. ProcessPayment job (pago simulado: delay 2-5s, 90% éxito). Soporte guest checkout (email sin cuenta) y usuario logueado. Envío de email async con QR y resumen (Mailpit en dev). Generación de QR. Página de confirmación con resumen estilo recibo.

### Phase 6: Testing y Optimización
Tests Pest de concurrencia (asientos y zonas). Tests de carga. Verificar soft lock con TTL. Tests Vitest para componentes. Tests E2E con Cypress (flujo completo de reserva).

## Constitution Check
- TDD: Cada fase debe tener tests escritos ANTES de implementar
- Neo-Brutalism: UI con bordes 2px #000, sombras 4px, sin border-radius
- Gitflow: Todo en `feature/*`, integrar en `develop`
- API-First: Laravel solo JSON, Nuxt consume la API, desacoplados
