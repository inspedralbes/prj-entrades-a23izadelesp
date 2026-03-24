# Tests

## Backend (Pest)
```bash
docker-compose exec backend ./vendor/bin/pest
docker-compose exec backend ./vendor/bin/pest --filter=SeatLock
docker-compose exec backend ./vendor/bin/pest --filter=Concurrency
docker-compose exec backend ./vendor/bin/pest --coverage
```

## Frontend (Vitest)
```bash
docker-compose exec frontend npm run test:unit
docker-compose exec frontend npm run test:unit -- --coverage
```

## End-to-End (Cypress)
```bash
docker-compose exec frontend npx cypress run
docker-compose exec frontend npx cypress open    # Modo interactivo
```

## Tests de concurrencia (Pest)
```bash
docker-compose exec backend ./vendor/bin/pest --filter=Concurrency
docker-compose exec backend ./vendor/bin/pest --filter=SeatLockConcurrency
docker-compose exec backend ./vendor/bin/pest --filter=QueueConcurrency
docker-compose exec backend ./vendor/bin/pest --filter=PurchaseConcurrency
```

## Todos los tests
```bash
docker-compose exec backend ./vendor/bin/pest && docker-compose exec frontend npm run test:unit
```
