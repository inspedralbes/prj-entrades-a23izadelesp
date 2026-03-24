# Tech Stack: BentoTickets

## Frontend
- **Framework**: Nuxt 3 (Vue.js)
- **Styling**: Tailwind CSS (Neo-Brutalism components)
- **Testing**: Vitest + Cypress (E2E)
- **State Management**: Pinia
- **Realtime Client**: socket.io-client

## Backend - API (Laravel)
- **Framework**: Laravel 11 (PHP 8.3)
- **API**: RESTful API con Resources de Laravel
- **Auth**: Laravel Sanctum (tokens SPA) + Socialite (Google OAuth)
- **Testing**: Pest (Testing Framework)
- **Colas**: Laravel Queues (Redis driver) para procesamiento de pagos y emails
- **Cache/Session**: Redis (soft locks, sala de espera FIFO, pub/sub)
- **Mail**: Laravel Mail (verificación de email, recuperación de contraseña, confirmación de compra)
- **Storage**: Local (imágenes de eventos en storage/public)

## Backend - Realtime (Node.js)
- **Runtime**: Node.js
- **Librería**: Socket.IO (server)
- **Responsabilidad**: Servidor independiente de comunicación en tiempo real
- **Comunicación**: Escucha eventos Redis pub/sub de Laravel y los reenvía al frontend via Socket.IO

## Infraestructura & DB
- **Database**: PostgreSQL (relacional)
- **Cache/Queue**: Redis (sala de espera FIFO, soft locks, pub/sub entre Laravel y Node)
- **Containerization**: Docker + Docker Compose
- **Servicios Docker**: Nginx, Laravel App, Node Socket.IO, PostgreSQL, Redis, Mailpit
- **CI/CD**: GitHub Actions (tests + build + deploy)
