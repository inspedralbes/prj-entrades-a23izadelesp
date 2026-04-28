# Task: Infraestructura Docker

**ID**: T001
**Phase**: 0 - Infraestructura Base
**Priority**: Bloqueante (debe completarse antes de cualquier otra tarea)
**Status**: Pendiente

## Descripción
Configurar Docker Compose con todos los servicios necesarios para el proyecto: Nginx, Laravel, Node.js Socket.IO, PostgreSQL y Redis.

## Objetivos
- Docker Compose funcional con los 5 servicios
- Laravel conectado a PostgreSQL y Redis
- Node.js Socket.IO server levantado y accesible
- Nuxt 3 con proxy a la API de Laravel
- Verificación de comunicación: Laravel → Redis pub/sub → Node Socket.IO → Cliente

## Archivos a crear/modificar
- `docker-compose.yml` (raíz del proyecto)
- `backend/Dockerfile`
- `realtime/Dockerfile`
- `frontend/Dockerfile` (o usar Nuxt dev server)
- `nginx/default.conf`

## Servicios en Docker Compose
| Servicio | Puerto | Imagen base |
|----------|--------|-------------|
| nginx | 80 | nginx:alpine |
| laravel | 9000 | php:8.3-fpm |
| node-socket | 3001 | node:20-alpine |
| postgres | 5432 | postgres:16 |
| redis | 6379 | redis:7-alpine |

## Dependencias
Ninguna - es la primera tarea.

## Criterios de verificación
- `docker-compose up -d` levanta todos los servicios sin errores
- `curl localhost:80` responde desde nginx
- Laravel puede hacer `Redis::ping()` correctamente
- Node Socket.IO acepta conexiones en puerto 3001
