# QueueLy

Plataforma de reserva de entradas de alto rendimiento para cine y conciertos.

## Descripción

QueueLy es un sistema de reservas que maneja eventos de alta demanda mediante:
- Sistema de doble cola FIFO (sala de espera + cola de compra)
- Comunicación en tiempo real con Socket.IO
- Soft locking de asientos/zonas en Redis
- Autenticación con Sanctum + Google OAuth
- Guest checkout (compra sin registro)

## Stack

- **Backend**: Laravel 11 (PHP 8.3) + PostgreSQL + Redis
- **Frontend**: Nuxt 3 + Tailwind CSS (Neo-Brutalism)
- **Realtime**: Node.js + Socket.IO
- **Infraestructura**: Docker + Docker Compose

## Servicios

| Servicio | Puerto |
|----------|--------|
| Frontend (Nuxt) | 3000 |
| API (Laravel) | 8080 |
| Socket.IO | 3001 |
| PostgreSQL | 5432 |
| Redis | 6379 |
| Mailpit | 8025 |

## Levantar el proyecto

```bash
docker compose up -d
cd backend && composer install && php artisan migrate --seed
cd ../realtime && npm install
cd ../frontend && npm install && npm run dev
```

## Estado

En desarrollo activo - Fase de implementación del MVP.
