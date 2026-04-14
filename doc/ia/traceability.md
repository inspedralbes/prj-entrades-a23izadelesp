# Trazabilidad IA (prompts → cambios)

## Prompt principal
"La primera entrada en dos pestañas se comporta como si fueran la misma sala/usuario."

## Hipótesis evaluadas
1. Prefijo Redis distinto entre Laravel y realtime.
2. No recepción de eventos por suscripción de canales.
3. Colisión de `identifier` guest entre pestañas.
4. Registro duplicado de socket en cola.

## Evidencias
- Logs de `node-socket` mostrando mismo `guest_...` en conexiones distintas.
- Doble evento de `register:queue` en algunos flujos.

## Decisiones aplicadas
- `identifier` guest por pestaña con `sessionStorage`.
- Registro de cola explícito en acción de "Empezar selección".
- Limpieza de estado activo de cola al desconectar.
- Lock de asiento atómico en Redis para evitar carrera.

## Resultado
- Realtime más consistente desde primer acceso.
- Cola FIFO mantenida.
- Tests de cola/locks y build frontend validados por Docker.
