# Project: BentoTickets

**Purpose**: Plataforma de reserva de entradas de alto rendimiento para cine y conciertos.
**Owner**: Izan de la Cruz
**Vision**: Crear una experiencia de usuario ultra-moderna basada en Bento Grids y Neo-Brutalismo, con una arquitectura técnica robusta y testeada al 100%.

## Metas del MVP
- Listado dinámico de eventos (Cine vs Conciertos) con filtros por tipo.
- Autenticación con Sanctum + Google OAuth + guest checkout.
- Sistema de reserva híbrido: asientos individuales (cine) y zonas (concierto).
- Sala de espera FIFO con comunicación en tiempo real (Socket.IO).
- Cola de compra secuencial con pago simulado.
- Confirmación con QR y envío de email.
- Perfil de usuario con historial de compras.
- Backend robusto con validaciones estrictas y testing automatizado.
- Dockerización completa para desarrollo y producción.
- CI/CD con GitHub Actions.