# Visión general

Volver al [índice](./README.md).

## Qué es QueueLy

QueueLy es una plataforma de venta/reserva de entradas orientada a alta demanda.
Se diseñó para evitar sobreventa y caos en picos de tráfico mediante:

- Sala de espera + cola de compra (FIFO).
- Soft-locks en Redis para asientos y zonas.
- Eventos en tiempo real con Socket.IO.
- Procesamiento asíncrono del pago y confirmación de tickets.

## Problema que resuelve

En escenarios de alta concurrencia (ej. conciertos populares):

- Muchos usuarios intentan comprar a la vez.
- El inventario puede sobreasignarse si no existe control temporal.
- La UX empeora si no hay feedback de posición/estado.

QueueLy soluciona esto separando fases:

1. **Cola**: regula cuántos usuarios pueden pasar al flujo de compra.
2. **Selección**: bloquea temporalmente inventario durante la decisión.
3. **Compra**: confirma o libera inventario según resultado de pago.
4. **Tiempo real**: notifica cambios al instante al resto de clientes.

## Tipos de evento soportados

- **Movie (cine)**: mapa en cuadrícula (`grid`) con filas/columnas.
- **Concert (concierto)**:
  - `general_admission` (aforo por zona, sin asiento concreto).
  - `seated` (asiento por matriz dentro de zona).

## Principios de diseño

- **Consistencia eventual controlada**: Redis para estado inmediato, DB para estado final.
- **Idempotencia práctica**: booking solo procesa estado `pending`.
- **Backpressure**: cola para limitar compra simultánea.
- **Observabilidad básica**: logs y canales Redis de eventos de negocio.

## Componentes principales

- [Backend/API](./03-backend-api.md): Laravel, reglas de negocio, persistencia.
- [Realtime](./05-queues-realtime.md): Socket.IO + suscripción a Redis.
- [Frontend](./06-frontend-flow.md): Nuxt 3, flujo de usuario y consumo de API/socket.
- [Infra](./08-infra-deploy.md): Docker Compose, Nginx, TLS.
