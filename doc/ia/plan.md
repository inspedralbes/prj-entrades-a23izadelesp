# Plan de implementación IA

## Fase 1 — Diagnóstico
- Capturar logs de `node-socket` en producción.
- Correlacionar `identifier`, `join:session`, `register:queue`, `seat:locked/released`.

## Fase 2 — Cambios
- Introducir helper de identidad cliente por pestaña (`sessionStorage`).
- Refactor de composables/stores para centralizar identidad.
- Ajustar registro de cola para evitar duplicidad temprana.
- Endurecer sincronización realtime y liberación de locks.

## Fase 3 — Validación
- `docker compose run --rm frontend npm run build`.
- `docker compose run --rm laravel php artisan test` en suites de cola/locks.
- Verificación manual en dos pestañas incógnitas.

## Fase 4 — Despliegue
- Commit + push a `main`.
- Verificar ejecución de CI/CD y logs en producción.
