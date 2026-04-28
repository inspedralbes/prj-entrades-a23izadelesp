# Infraestructura i desplegament

Volver al [índex](./README.md).

## Producció amb Docker Compose

Fitxer principal: `docker-compose.prod.yml`.

Serveis:

- `web` (Nginx + frontend estàtic)
- `laravel` (PHP-FPM)
- `laravel-worker` (`queue:work`)
- `node-socket` (Socket.IO)
- `postgres`
- `redis`

## Nginx (producció)

`nginx/prod.conf`:

- Port 80: challenge ACME i redirecció a HTTPS.
- Port 443:
  - `/` -> frontend estàtic.
  - `/api` -> `fastcgi_pass laravel:9000`.
  - `/socket.io/` -> proxy a `node-socket:3001`.

## TLS amb Let's Encrypt

Els certificats es munten via volums:

- `./certbot/conf:/etc/letsencrypt`
- `./certbot/www:/var/www/certbot`

Si certbot s'ha executat al host, sincronitzar:

```bash
rsync -a /etc/letsencrypt/ /opt/queuely/certbot/conf/
```

## Variables `.env` (arrel)

Mínimes típiques:

- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `REDIS_URL`
- `API_BASE_URL=/api`
- `SOCKET_URL=https://queuely.daw.inspedralbes.cat`
- Variables Laravel (`APP_KEY`, etc.)

## Procediment estàndard de deploy manual

```bash
cd /opt/queuely
git pull --ff-only origin main
docker compose -p queuely -f docker-compose.prod.yml --env-file .env up -d --build --remove-orphans
docker compose -p queuely -f docker-compose.prod.yml --env-file .env exec -T laravel php artisan migrate --force
docker compose -p queuely -f docker-compose.prod.yml --env-file .env exec -T laravel php artisan config:cache
docker compose -p queuely -f docker-compose.prod.yml --env-file .env exec -T laravel php artisan route:cache
docker compose -p queuely -f docker-compose.prod.yml --env-file .env exec -T laravel php artisan view:cache
docker compose -p queuely -f docker-compose.prod.yml --env-file .env exec -T laravel php artisan queue:restart
```

## Nota operativa important

Utilitzar sempre `-p queuely` evita conflictes de nom de contenidors entre execucions compose.

## Validacions post-deploy

```bash
docker compose -p queuely -f docker-compose.prod.yml --env-file .env ps
curl -I https://queuely.daw.inspedralbes.cat
curl -i https://queuely.daw.inspedralbes.cat/api/events
```

## Troubleshooting ràpid

### `web` reinicia i no aixeca HTTPS

- Revisar logs `web`.
- Comprovar existència de certs a `/etc/letsencrypt/live/...` dins contenidor.

### `502` a `/api`

- Revisar `laravel` i `web` logs.
- Verificar `fastcgi_pass laravel:9000` i salut del contenidor Laravel.

### Conflictes de noms contenidors

- Eliminar huèrfans i relançar amb `-p queuely`.
