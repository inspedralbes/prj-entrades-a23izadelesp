# CI/CD (GitHub Actions)

Volver al [índex](./README.md).

## Workflows

- `CI - Tests` (`.github/workflows/ci.yml`)
- `CD - Deploy` (`.github/workflows/cd.yml`)

## CI: què valida

### Job backend

- Setup PHP 8.3 + extensions (`pdo_pgsql`, `redis`, etc.).
- Services: `postgres`, `redis`.
- `composer install` a `backend/`.
- Prepara `.env` de testing des de `.env.example`.
- `php artisan key:generate`.
- `php artisan migrate --force`.
- `php artisan test`.

### Job frontend

- Node 20.
- `npm ci`, `npm run build`, `npm run generate`.

### Job realtime

- Node 20.
- `npm ci`.
- `node --check src/server.js`.

## CD: què fa

Trigger: push a `main` (o manual).

Via `appleboy/ssh-action`:

1. SSH al servidor.
2. `git pull origin main`.
3. `docker compose ... up -d --build`.
4. Migracions + caches + `queue:restart`.

## Secrets requerits al repositori

- `SERVER_HOST`
- `SERVER_PORT`
- `SERVER_USER`
- `SERVER_SSH_KEY`
- `SERVER_APP_PATH`

## Bones pràctiques

- Mantenir `.env` només al servidor (no a secrets si no cal).
- Evitar vendor/node_modules en repo.
- Mantenir tests deterministes (Redis flush entre tests, migracions netes).

## Errors típics i solucions

### `cp .env.testing .env` falla

S'ha corregit preparant `.env` des de `.env.example` al workflow.

### Tests de cua inestables

Evitar supòsits de client Redis i controlar retorn de `lpop` buit (`null|false`).

### Constraint de booking status

Assegurar que DB permet estats usats pel codi (`failed` inclòs).
