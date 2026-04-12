# QueueLy

Plataforma de venta y reserva de entradas para cine y conciertos, preparada para escenarios de alta concurrencia.

## Resumen ejecutivo

QueueLy implementa un flujo de compra robusto basado en:

- Cola FIFO de admisión para controlar picos de tráfico.
- Soft-locks en Redis para evitar sobreventa.
- Eventos en tiempo real con Socket.IO para feedback instantáneo.
- Procesamiento asíncrono de compra y confirmación de tickets.

## Stack

- **Backend**: Laravel + PostgreSQL + Redis
- **Frontend**: Nuxt 3 + Pinia + Tailwind
- **Realtime**: Node.js + Socket.IO
- **Infraestructura**: Docker Compose + Nginx + Let's Encrypt
- **CI/CD**: GitHub Actions

## Arranque rápido (desarrollo)

```bash
docker compose up -d

cd backend
composer install
php artisan migrate --seed

cd ../realtime
npm install

cd ../frontend
npm install
npm run dev
```

## Producción (resumen)

```bash
cd /opt/queuely
git pull --ff-only origin main
docker compose -p queuely -f docker-compose.prod.yml --env-file .env up -d --build --remove-orphans
docker compose -p queuely -f docker-compose.prod.yml --env-file .env exec -T laravel php artisan migrate --force
```

## Documentación completa

La documentación técnica exhaustiva está en `doc/`:

- **Índice principal**: [`doc/README.md`](./doc/README.md)
- **Parte más importante (colas + realtime)**: [`doc/05-queues-realtime.md`](./doc/05-queues-realtime.md)
- **Backend y API**: [`doc/03-backend-api.md`](./doc/03-backend-api.md)
- **Infra y deploy**: [`doc/08-infra-deploy.md`](./doc/08-infra-deploy.md)
- **CI/CD**: [`doc/09-ci-cd.md`](./doc/09-ci-cd.md)

## Estado del proyecto

MVP funcional con despliegue productivo, flujo de compra, cola y sincronización en tiempo real.
