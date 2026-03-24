# Task: Cola de Compra y Procesamiento de Pagos

**ID**: T013
**Phase**: 5 - Backend
**Priority**: Alta
**Status**: Pendiente

## Descripción
Implementar la cola de compra que procesa los pagos de forma secuencial. Soporta compras de usuarios logueados y guests. El pago se simula con delay aleatorio y 90% de éxito.

## Objetivos
- Endpoint: `POST /api/bookings` - crear reserva (entra en cola de compra)
- Soporte para guest checkout (solo email, sin autenticación)
- Soporte para usuario logueado (user_id)
- Laravel Job: `ProcessPayment` - procesa el pago (simulado)
- Pago simulado: delay aleatorio 2-5s, 90% de éxito
- Cola de compra en Redis: `purchase:queue` (List de booking_ids)
- Procesamiento secuencial: un pago a la vez
- Publicar eventos Redis pub/sub:
  - `purchase:processing` - el pago está siendo procesado
  - `booking:confirmed` - pago exitoso, reserva confirmada
  - `purchase:failed` - pago fallido
- Al confirmar:
  - Cine: mover soft locks de Redis a `occupied_seats` en PostgreSQL
  - Concierto: crear registros en `occupied_zones` con la cantidad
  - Liberar soft locks de Redis
  - **Enviar email de confirmación** al usuario (user.email o guest_email) con QR y resumen
- Al fallar: liberar soft locks, asientos/zonas vuelven a estar disponibles

## Archivos a crear/modificar
- `backend/app/Http/Controllers/Api/BookingController.php`
- `backend/app/Services/PaymentService.php` (simulación de pago)
- `backend/app/Jobs/ProcessPayment.php`
- `backend/app/Jobs/SendBookingConfirmationEmail.php` (envío async de email)
- `backend/app/Mail/BookingConfirmationMail.php` (mailable con QR)
- `backend/app/Events/PurchaseProcessing.php`
- `backend/app/Events/BookingConfirmed.php`
- `backend/app/Events/PurchaseFailed.php`
- `backend/routes/api.php`
- `backend/tests/Feature/Services/PaymentTest.php`

## Estructura Redis
```
purchase:queue → List [booking_id_1, booking_id_2, ...]
```

## Dependencias
- T002 (Modelos - bookings con guest_email)
- T005 (Soft lock - asientos y zonas)
- T007 (Cola de espera - el usuario debe estar admitido)

## Criterios de verificación
- Al crear una booking, se añade a la cola de compra
- Las compras guest aceptan guest_email sin user_id
- El worker procesa los pagos en orden FIFO
- El pago simulado tiene delay de 2-5s y 90% de éxito
- Al confirmar (cine): los asientos pasan de Redis a occupied_seats
- Al confirmar (concierto): se crean registros en occupied_zones
- Al confirmar: se envía email con QR y resumen al email del comprador
- Al fallar: los soft locks se liberan
- Los eventos pub/sub se publican correctamente
- No se puede procesar el mismo booking dos veces
- El email se envía de forma asíncrona (no bloquea el worker)
