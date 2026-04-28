# Task: API REST - Eventos y Sesiones

**ID**: T004-api
**Phase**: 1 - Backend
**Priority**: Alta
**Status**: Pendiente

## Descripción
Crear los endpoints de la API REST para listar eventos, ver detalle de un evento, y obtener las sesiones disponibles. Incluye soporte para los dos tipos de venue (grid y zones).

## Objetivos
- `GET /api/events` - Listar todos los eventos (con filtro por tipo)
- `GET /api/events/{id}` - Detalle de un evento con sus sesiones
- `GET /api/sessions/{id}` - Detalle de una sesión con venue_config
- `GET /api/sessions/{id}/seats` - Estado actual de asientos (cine) o disponibilidad de zonas (concierto)
- Uso de Laravel API Resources para formatear respuestas
- Tests Pest para cada endpoint

## Archivos a crear/modificar
- `backend/app/Http/Controllers/Api/EventController.php`
- `backend/app/Http/Controllers/Api/SessionController.php`
- `backend/app/Http/Resources/EventResource.php`
- `backend/app/Http/Resources/SessionResource.php`
- `backend/app/Http/Resources/SeatResource.php`
- `backend/app/Http/Resources/ZoneResource.php`
- `backend/routes/api.php`
- `backend/tests/Feature/Api/EventTest.php`
- `backend/tests/Feature/Api/SessionTest.php`

## Dependencias
- T002 (Modelos Eloquent)

## Criterios de verificación
- Los endpoints devuelven JSON válido con status 200
- `GET /api/sessions/{id}/seats` devuelve el estado combinado de DB + Redis
- Para cine: devuelve grid con estados de cada asiento
- Para concierto: devuelve zonas con disponibilidad calculada
- Tests Pest pasan para cada endpoint
- Error 404 cuando el evento/sesión no existe
