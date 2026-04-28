# Design System & User Flow: BentoTickets

## 1. Estética Neo-Brutalista (Tendencia 2026)
El diseño se basa en la honestidad de los materiales digitales: bordes marcados, colores planos y estructuras rígidas.

- **Layout**: Sistema de "Bento Grids" (cajas rectangulares y cuadradas que encajan perfectamente).
- **Bordes**: `2px solid #000000` en todos los contenedores y botones.
- **Radios**: `border-radius: 0px` (esquinas completamente afiladas).
- **Sombras**: `box-shadow: 4px 4px 0px 0px #000000` (sombras duras, sin difuminado).
- **Tipografía**: Plus Jakarta Sans (Headers en ExtraBold, Body en Medium).

## 2. Paleta de Colores
- **Fondo Principal**: `#F3F4F6` (Gris claro neutro).
- **Acento Primario**: `#10B981` (Verde esmeralda - Éxito/Selección).
- **Acento Secundario**: `#F59E0B` (Ámbar - Alertas/Call to Action).
- **Contraste**: `#000000` (Negro puro para bordes y texto).

## 3. Flujo de Pantallas (Mobile-First)

### A. Discovery (Home)
- **Top Bar**: Logo y perfil en cajas cuadradas independientes.
- **Hero Bento**: Tarjeta grande con el evento destacado del día.
- **Feed**: Grid vertical de tarjetas de eventos. Cada tarjeta muestra Imagen, Título y un Tag (`Cine` o `Concierto`).

### B. Detalle del Evento
- **Imagen Hero**: Ocupa el 40% superior con borde negro inferior grueso.
- **Bento Info**: Fichas con duración, género y calificación.
- **Selector de Sesión**: Lista horizontal de días (cuadrados blancos que pasan a Ámbar al seleccionar).

### C. Seat Map (El Núcleo)
- **Visualización**: Grid de cuadrados pequeños representados como celdas de una tabla.
- **Estados**:
    - Blanco: Libre.
    - Negro: Ocupado.
    - Verde (#10B981): Seleccionado por el usuario actual.
- **Sticky Footer**: Caja blanca fija con el total y botón "Reservar Ahora".

### D. Checkout & QR
- **Resumen**: Tarjeta tipo recibo con líneas de corte simuladas.
- **Confirmación**: Generación de un código QR minimalista dentro de una caja con sombra dura.