# BentoTickets Constitution

## Core Principles

### I. TDD Mandatory (NON-NEGOTIABLE)
El desarrollo es Test-First. Las pruebas deben escribirse y fallar antes de implementar cualquier ruta en Laravel o componente en Nuxt. Pest para backend, Vitest para frontend.

### II. Neo-Brutalism Strictness
La UI debe seguir el estilo Bento Grid: bordes de 2px sólidos (#000000), sin border-radius, y sombras planas (4px 4px 0px 0px #000000). Colores: #F3F4F6, #10B981, #F59E0B.

### III. Gitflow Compliance
Todo cambio debe nacer en `feature/*`, integrarse en `develop` y solo llegar a `main` tras pasar los tests automáticos en el CI.

### IV. API-First & Headless
Nuxt y Laravel deben estar desacoplados. Laravel solo sirve JSON; Nuxt consume la API. No hay Blade ni acoplamiento de servidor.

**Version**: 1.0.0 | **Ratified**: 2026-03-23 | **Last Amended**: 2026-03-23