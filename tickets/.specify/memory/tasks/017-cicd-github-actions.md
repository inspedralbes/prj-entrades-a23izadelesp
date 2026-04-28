# Task: CI/CD con GitHub Actions

**ID**: T015
**Phase**: 0 - Infraestructura Base
**Priority**: Media
**Status**: Pendiente

## Descripción
Configurar pipelines de CI/CD con GitHub Actions para ejecutar tests automáticamente y desplegar la aplicación.

## Objetivos
- Pipeline CI que ejecuta en push a `develop`/`main` y en PRs
- Pipeline CD que ejecuta al hacer tag `v*` en `main`
- CI ejecuta: Pest (backend), Vitest (frontend), tests realtime (Node.js), lint
- CD construye imágenes Docker y despliega al servidor

## Archivos creados
- `.github/workflows/ci.yml` - Pipeline de integración continua
- `.github/workflows/cd.yml` - Pipeline de despliegue continuo

## Secrets necesarios en GitHub
- `DOCKER_USERNAME` - Usuario de Docker Hub
- `DOCKER_PASSWORD` - Password de Docker Hub
- `DEPLOY_HOST` - IP/hostname del servidor de producción
- `DEPLOY_USER` - Usuario SSH del servidor
- `DEPLOY_KEY` - Clave SSH privada del servidor

## Jobs en CI
| Job | Qué hace | Servicios |
|-----|----------|-----------|
| backend-tests | Pest con coverage ≥ 80% | PostgreSQL 16, Redis 7 |
| frontend-tests | Vitest + build check | - |
| realtime-tests | Tests Node.js | Redis 7 |
| lint | Pint (PHP) + ESLint (JS) | - |

## Jobs en CD
| Job | Qué hace |
|-----|----------|
| build | Construye y sube 3 imágenes Docker (backend, realtime, frontend) |
| deploy | SSH al servidor, pull, rebuild, migrate, restart |

## Dependencias
- T001 (Docker infraestructura)
- T002 (Backend con migraciones)

## Criterios de verificación
- Los 4 jobs de CI pasan en un push a develop
- Las imágenes Docker se construyen correctamente
- El deploy por SSH funciona (requiere secrets configurados)
