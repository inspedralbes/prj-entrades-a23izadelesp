# Environment Variables

## PostgreSQL
- `DB_CONNECTION`: Driver de conexión (pgsql).
- `DB_HOST`: Host de la base de datos (default: postgres).
- `DB_PORT`: Puerto de PostgreSQL (default: 5432).
- `DB_DATABASE`: Nombre de la base de datos.
- `DB_USERNAME`: Usuario de la base de datos.
- `DB_PASSWORD`: Password de la base de datos.

## Redis
- `REDIS_HOST`: Host de Redis (default: redis).
- `REDIS_PORT`: Puerto de Redis (default: 6379).
- `REDIS_PASSWORD`: Password de Redis (opcional).
- `REDIS_URL`: URL completa de Redis (usado por Node.js Socket.IO).

## Laravel (Backend API)
- `APP_NAME`: Nombre de la aplicación.
- `APP_ENV`: Entorno (local, production).
- `APP_KEY`: Clave de encriptación de Laravel.
- `APP_DEBUG`: Debug mode (true/false).
- `APP_URL`: URL base de la API (default: http://localhost:8000).
- `QUEUE_CONNECTION`: Driver de colas (default: redis).
- `SOFT_LOCK_TTL`: TTL en segundos para soft locks de asientos (default: 300 = 5 min).
- `QUEUE_BATCH_SIZE`: Número de usuarios a liberar por batch en la sala de espera (default: 5).
- `QUEUE_BATCH_INTERVAL`: Intervalo en segundos entre batches (default: 10).
- `FRONTEND_URL`: URL del frontend para CORS (default: http://localhost:3000).

## Autenticación (Sanctum + Google OAuth)
- `SANCTUM_STATEFUL_DOMAINS`: Dominios permitidos para SPA auth (localhost:3000).
- `SESSION_DOMAIN`: Dominio de la cookie de sesión (.localhost).
- `GOOGLE_CLIENT_ID`: Client ID de Google OAuth.
- `GOOGLE_CLIENT_SECRET`: Client Secret de Google OAuth.
- `GOOGLE_REDIRECT_URI`: URL de callback (http://localhost:8000/auth/google/callback).

## Mail (Verificación de email, recuperación de contraseña, confirmación de compra)
- `MAIL_MAILER`: Driver de mail (smtp para Mailpit en dev).
- `MAIL_HOST`: Host SMTP (default: mailpit en Docker).
- `MAIL_PORT`: Puerto SMTP (default: 1025).
- `MAIL_USERNAME`: Usuario SMTP (vacío con Mailpit).
- `MAIL_PASSWORD`: Password SMTP (vacío con Mailpit).
- `MAIL_FROM_ADDRESS`: Email remitente (no-reply@bentotickets.com).
- `MAIL_FROM_NAME`: Nombre remitente (BentoTickets).

### Mailpit (solo desarrollo)
- Web UI: `http://localhost:8025` — para ver emails capturados
- SMTP: puerto `1025` — donde Laravel envía los emails

## Nuxt 3 (Frontend)
- `NUXT_PUBLIC_API_URL`: URL base de la API de Laravel (default: http://localhost:8000).
- `NUXT_PUBLIC_SOCKET_URL`: URL del servidor Socket.IO (default: http://localhost:3001).

## Node.js (Socket.IO Realtime Server)
- `SOCKET_PORT`: Puerto del servidor Socket.IO (default: 3001).
- `REDIS_URL`: URL de conexión a Redis para pub/sub (formato: redis://redis:6379).

## Puertos de Servicios
| Servicio | Puerto | Variable |
|----------|--------|----------|
| Frontend (Nuxt) | 3000 | - |
| API (Laravel) | 8000 | - |
| Socket.IO (Node) | 3001 | SOCKET_PORT |
| PostgreSQL | 5432 | DB_PORT |
| Redis | 6379 | REDIS_PORT |
| Mailpit SMTP | 1025 | MAIL_PORT |
| Mailpit Web UI | 8025 | - |
