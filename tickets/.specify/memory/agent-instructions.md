# BentoTickets Development Guidelines

**Last updated**: 2026-03-24

## Active Technologies
- Laravel 11, Nuxt 3, Tailwind CSS, Pest, Vitest, Cypress, Docker, PostgreSQL, Redis, Socket.IO, Node.js.

## Project Structure
```
prj-entrades-a23izadelesp/
├── backend/                    # Laravel 11 API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Jobs/
│   │   └── Events/
│   ├── database/migrations/
│   ├── routes/api.php
│   └── tests/Feature/
├── realtime/                   # Node.js + Socket.IO
│   ├── src/
│   │   ├── server.js
│   │   ├── redisSubscriber.js
│   │   ├── queueManager.js
│   │   └── rooms.js
│   ├── package.json
│   └── Dockerfile
├── frontend/                   # Nuxt 3
│   ├── pages/
│   ├── components/
│   ├── composables/
│   ├── stores/
│   └── nuxt.config.ts
├── docker-compose.yml
└── tickets/.specify/memory/    # Documentación del proyecto
```

## Naming Conventions
- **Laravel Controllers**: PascalCase singular, sufijo `Controller` (ej. `EventController`, `SeatController`)
- **Laravel Models**: PascalCase singular (ej. `Event`, `OccupiedSeat`)
- **Laravel Services**: PascalCase singular, sufijo `Service` (ej. `SeatLockService`, `QueueService`)
- **Laravel Jobs**: PascalCase, sufijo descriptivo (ej. `ProcessPayment`, `SendConfirmationEmail`)
- **Nuxt Components**: PascalCase (ej. `SeatMap.vue`, `WaitingRoom.vue`)
- **Nuxt Composables**: camelCase con prefijo `use` (ej. `useSocket.js`, `useQueue.js`)
- **Pinia Stores**: camelCase (ej. `seats.js`, `events.js`)
- **Node.js modules**: camelCase (ej. `redisSubscriber.js`, `queueManager.js`)

## Gitflow (Ramas)
- **`main`**: Producción. Solo recibe merges desde `release/*` o `hotfix/*`.
- **`develop`**: Integración. Recibe merges desde `feature/*`.
- **`feature/*`**: Nuevas funcionalidades. Se crean desde `develop`.
- **`hotfix/*`**: Correcciones urgentes en producción. Se crean desde `main`.

### Flujo de trabajo
1. Crear rama `feature/nombre-funcionalidad` desde `develop`
2. Desarrollar con TDD (tests primero)
3. Pull request a `develop`
4. Tras aprobación, merge a `develop`
5. Release: merge `develop` → `main` con tag de versión

## Commands

### Docker
```bash
docker-compose up -d              # Levantar todos los servicios
docker-compose down               # Parar todos los servicios
docker-compose logs -f [service]  # Ver logs de un servicio
```

### Laravel (Backend)
```bash
docker-compose exec backend php artisan migrate --seed
docker-compose exec backend php artisan test          # Pest
docker-compose exec backend ./vendor/bin/pest         # Pest directo
docker-compose exec backend php artisan queue:work    # Worker de colas
```

### Nuxt (Frontend)
```bash
cd frontend && npm install && npm run dev
docker-compose exec frontend npm run test:unit        # Vitest
docker-compose exec frontend npx cypress run          # Cypress E2E
```

### Node.js (Socket.IO)
```bash
cd realtime && npm install && node src/server.js
```

## Code Style
- **Laravel**: PHP 8.3 con tipado fuerte estricto. Usar `declare(strict_types=1)`.
- **Nuxt**: Script Setup y Composition API obligatorios. Nunca Options API.
- **Tailwind**: Clases utilitarias. Colores Neo-Brutalism como variables CSS.
- **Testing**: TDD obligatorio. Tests escritos ANTES de implementar. Pest para backend, Vitest para frontend.
- **Commits**: Formato conventional commits: `feat:`, `fix:`, `test:`, `refactor:`, `docs:`.
