# Task: Tests de Concurrencia y Carga

**ID**: T013
**Phase**: 6 - Testing
**Priority**: Alta
**Status**: Pendiente

## Descripción
Crear tests automatizados que verifican el comportamiento del sistema bajo carga concurrente. Es crítico asegurar que nunca existan asientos duplicados en `occupied_seats`.

## Objetivos
- Test Pest: 100 peticiones simultáneas al mismo asiento → solo 1 tiene éxito
- Test Pest: Soft lock expira y otro usuario puede bloquear el asiento
- Test Pest: Cola FIFO mantiene el orden correcto bajo carga
- Test Pest: Cola de compra procesa pagos secuencialmente sin duplicados
- Test Pest: Verificar que `occupied_seats` nunca tiene duplicados para `(session_id, row, col)`

## Archivos a crear/modificar
- `backend/tests/Feature/Concurrency/SeatLockConcurrencyTest.php`
- `backend/tests/Feature/Concurrency/QueueConcurrencyTest.php`
- `backend/tests/Feature/Concurrency/PurchaseConcurrencyTest.php`

## Dependencias
- T004 (Soft lock)
- T005 (Cola de espera)
- T011 (Cola de compra)

## Criterios de verificación
- Todos los tests de concurrencia pasan
- No hay race conditions detectadas
- `occupied_seats` no tiene registros duplicados después de los tests
- Los tests ejecutan en menos de 30 segundos
