# Task: Autenticación (Sanctum + Google OAuth)

**ID**: T003-auth
**Phase**: 1 - Backend
**Priority**: Alta
**Status**: Pendiente

## Descripción
Implementar el sistema de autenticación completo con Laravel Sanctum, Google OAuth via Socialite, verificación de email y recuperación de contraseña. La autenticación es opcional: el usuario puede comprar como guest sin registrarse.

## Objetivos
- Registro con email/password + verificación de email
- Login con email/password
- Login con Google OAuth (Socialite)
- Recuperación de contraseña por email
- Endpoints de auth protegidos con Sanctum
- Guest checkout: las compras pueden ser sin autenticación

## Endpoints
| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/auth/register` | Registro con email/password |
| POST | `/api/auth/login` | Login con email/password |
| GET | `/api/auth/google` | Redirige a Google OAuth |
| GET | `/api/auth/google/callback` | Callback de Google OAuth |
| POST | `/api/auth/logout` | Logout (revoca token) |
| GET | `/api/auth/user` | Devuelve usuario autenticado |
| POST | `/api/auth/forgot-password` | Envía email de recuperación |
| POST | `/api/auth/reset-password` | Resetea la contraseña |
| GET | `/api/auth/verify-email/{id}/{hash}` | Verifica el email |

## Archivos a crear/modificar
- `backend/app/Http/Controllers/Api/AuthController.php`
- `backend/app/Http/Controllers/Api/GoogleAuthController.php`
- `backend/app/Http/Requests/Auth/RegisterRequest.php`
- `backend/app/Http/Requests/Auth/LoginRequest.php`
- `backend/app/Models/User.php` (ya en T002)
- `backend/routes/api.php`
- `backend/config/sanctum.php`
- `backend/config/services.php` (Google OAuth)
- `backend/tests/Feature/Auth/AuthTest.php`

## Configuración necesaria
- Sanctum stateful domains para SPA
- Google OAuth credentials (CLIENT_ID, CLIENT_SECRET)
- Mail driver configurado para verificación de email

## Dependencias
- T002 (Modelos User con MustVerifyEmail)

## Criterios de verificación
- El registro crea un usuario y envía email de verificación
- El login devuelve un token Sanctum válido
- Google OAuth crea/vincula cuenta correctamente
- La recuperación de contraseña envía email con enlace
- Las rutas protegidas requieren token válido
- Un guest puede hacer compras sin autenticación
- Tests Pest pasan para todos los flujos de auth
