# Task: Base de Datos y Modelos Eloquent

**ID**: T002
**Phase**: 1 - Backend
**Priority**: Alta
**Status**: Pendiente

## Descripción
Crear las migraciones de la base de datos PostgreSQL y los modelos Eloquent con sus relaciones. Incluye soporte para autenticación (Sanctum, Google OAuth), guest checkout, y sistema híbrido de venue (grid para cine, zonas para conciertos).

## Objetivos
- Migraciones para las tablas del sistema
- Models Eloquent con relaciones definidas
- Índices únicos para evitar duplicados
- Seeder de datos de prueba (eventos de cine y concierto)

## Migraciones a crear
- `users` - con campos de auth (email_verified_at, google_id, password nullable)
- `personal_access_tokens` - Sanctum
- `password_reset_tokens` - recuperación de contraseña
- `events` - eventos (movie/concert)
- `sessions` - sesiones con venue_config JSON y price
- `bookings` - con user_id nullable y guest_email (guest checkout)
- `occupied_seats` - asientos individuales (solo cine)
- `occupied_zones` - entradas por zona (solo conciertos)

## Models Eloquent
- `backend/app/Models/User.php` - con HasApiTokens (Sanctum), MustVerifyEmail
- `backend/app/Models/Event.php`
- `backend/app/Models/Session.php`
- `backend/app/Models/Booking.php` - con scope para guest vs user
- `backend/app/Models/OccupiedSeat.php`
- `backend/app/Models/OccupiedZone.php`

## Seeder de datos de prueba
- 2 eventos de cine con sesiones (venue_config tipo grid)
- 2 eventos de concierto con sesiones (venue_config tipo zones)
- 1 usuario de prueba (test@bentotickets.com)

## Relaciones
```
User ──1:N──▶ Booking
Event ──1:N──▶ Session
Session ──1:N──▶ Booking
Session ──1:N──▶ OccupiedSeat
Session ──1:N──▶ OccupiedZone
Booking ──1:N──▶ OccupiedSeat
Booking ──1:N──▶ OccupiedZone
```

## Archivos a crear/modificar
- `backend/database/migrations/xxxx_create_users_table.php`
- `backend/database/migrations/xxxx_create_personal_access_tokens_table.php`
- `backend/database/migrations/xxxx_create_password_reset_tokens_table.php`
- `backend/database/migrations/xxxx_create_events_table.php`
- `backend/database/migrations/xxxx_create_sessions_table.php`
- `backend/database/migrations/xxxx_create_bookings_table.php`
- `backend/database/migrations/xxxx_create_occupied_seats_table.php`
- `backend/database/migrations/xxxx_create_occupied_zones_table.php`
- `backend/app/Models/User.php`
- `backend/app/Models/Event.php`
- `backend/app/Models/Session.php`
- `backend/app/Models/Booking.php`
- `backend/app/Models/OccupiedSeat.php`
- `backend/app/Models/OccupiedZone.php`
- `backend/database/seeders/DatabaseSeeder.php`

## Dependencias
- T001 (Docker infraestructura)

## Criterios de verificación
- `php artisan migrate` ejecuta sin errores
- `php artisan db:seed` crea datos de prueba con cine y conciertos
- Las relaciones Eloquent funcionan correctamente
- No se puede insertar un `occupied_seat` duplicado en la misma sesión
- Las bookings guest aceptan guest_email sin user_id
- El venue_config se parsea correctamente (grid vs zones)
