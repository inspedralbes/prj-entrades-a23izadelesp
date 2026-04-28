# Runbook

## Levantar el proyecto completo

1. `docker-compose up -d` — Levanta todos los servicios (nginx, laravel, node-socket, postgres, redis)

2. `cd backend && composer install && php artisan migrate --seed` — Instala dependencias, ejecuta migraciones y seeder

3. `cd realtime && npm install && node src/server.js` — Instala dependencias y arranca el servidor Socket.IO

4. `cd frontend && npm install && npm run dev` — Instala dependencias y arranca Nuxt en modo desarrollo

5. Acceder a:
   - `localhost:3000` — Frontend (Nuxt 3)
   - `localhost:8000` — API (Laravel)
   - `localhost:3001` — Socket.IO (Node.js)
   - `localhost:8025` — Mailpit (emails de desarrollo)

## Cola de trabajo (Laravel Queues)

Para procesar las colas de compra y emails de confirmación:

```bash
docker-compose exec backend php artisan queue:work --tries=3
```

## Reiniciar servicios

```bash
docker-compose restart              # Reiniciar todos
docker-compose restart laravel      # Solo Laravel
docker-compose restart node-socket  # Solo Socket.IO
docker-compose restart redis        # Solo Redis
```
