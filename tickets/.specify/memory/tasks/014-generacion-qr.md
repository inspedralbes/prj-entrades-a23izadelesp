# Task: Generación de QR, Confirmación y Email

**ID**: T014
**Phase**: 5 - Frontend+Backend
**Priority**: Media
**Status**: Pendiente

## Descripción
Implementar la generación del código QR, la página de confirmación web, y el envío de email con el QR y resumen. Funciona tanto para compras de usuario logueado como guest.

## Objetivos
- Backend: Generar QR con la información de la reserva
- Backend: Enviar email con QR embebido y resumen de la entrada
- Endpoint: `GET /api/bookings/{id}/qr` - devuelve imagen QR
- Frontend: Página de confirmación `/booking/{id}/confirmed`
- Diseño tipo recibo con líneas de corte simuladas
- QR dentro de una caja con sombra dura Neo-Brutalista
- Resumen: evento, sesión, asientos/zonas, total

## Contenido del QR
- booking_id
- event title
- session date/time
- Para cine: lista de asientos (fila, columna)
- Para concierto: zona + cantidad de entradas
- total

## Email de confirmación
- Se envía al email del comprador (user.email o guest_email)
- Contiene QR embebido como imagen
- Resumen: evento, fecha, asientos/zonas, total
- Diseño HTML con estilo consistente

## Archivos a crear/modificar
- `backend/app/Services/QrService.php`
- `backend/app/Mail/BookingConfirmationMail.php` - Mailable con QR
- `backend/app/Jobs/SendBookingConfirmationEmail.php` - Job async
- `backend/app/Http/Controllers/Api/BookingController.php` (endpoint QR)
- `backend/resources/views/emails/booking-confirmed.blade.php` - template email
- `frontend/pages/booking/[id]/confirmed.vue`
- `frontend/components/BookingSummary.vue`

## Dependencias
- T013 (Cola de compra - la reserva debe estar confirmada)

## Criterios de verificación
- El QR se genera correctamente con la info de la reserva
- La página de confirmación muestra el resumen completo
- Funciona para compras de usuario logueado y guest
- El QR es scaneable y contiene los datos esperados
- El email se envía al email correcto (user o guest)
- El email contiene el QR y el resumen de la entrada
- El diseño web sigue el estilo Neo-Brutalista
- El resumen diferencia entre asientos (cine) y zonas (concierto)
