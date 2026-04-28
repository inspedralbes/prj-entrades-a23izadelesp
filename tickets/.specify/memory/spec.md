# Feature Specification: Ticket Booking System

**Feature Branch**: `001-core-booking-flow`
**Status**: Draft
**Owner**: Izan de la Cruz

## User Scenarios & Testing

### User Story 1 - Listado de Eventos (Priority: P1)
Como usuario, quiero ver un listado de eventos disponibles (cine y conciertos) para descubrir qué puedo reservar.

**Independent Test**: El usuario puede cargar la URL `/` y ver los eventos disponibles consumiendo `GET /api/events`. Puede filtrar por tipo (cine/concierto).

**Acceptance Scenarios**:
1. **Given** la home page cargada, **When** el usuario ve la lista, **Then** aparecen tarjetas con imagen, título y tag de tipo (Cine/Concierto).
2. **Given** la home page, **When** el usuario pulsa el filtro "Cine", **Then** solo se muestran eventos de tipo cine.
3. **Given** la home page, **When** el usuario pulsa en una tarjeta de evento, **Then** navega a `/events/{id}`.

**Functional Requirements**:
- **FR-001**: La API debe devolver los eventos con su tipo (`movie` o `concert`), imagen, título y descripción.
- **FR-002**: El frontend debe permitir filtrar eventos por tipo sin recargar la página.

---

### User Story 2 - Sala de Espera (Priority: P1)
Como usuario, quiero acceder a un evento de alta demanda y recibir mi posición en la cola de espera para saber cuándo podré reservar.

**Independent Test**: El usuario puede hacer `POST /api/sessions/{id}/queue/join` y recibir su posición. Via Socket.IO recibe actualizaciones de posición en tiempo real.

**Acceptance Scenarios**:
1. **Given** un evento con alta demanda, **When** el usuario intenta acceder al seat map, **Then** entra en la cola FIFO y ve su posición (ej. "Posición 342").
2. **Given** el usuario en la cola, **When** su posición avanza, **Then** recibe actualización en tiempo real via Socket.IO sin recargar la página.
3. **Given** el usuario en la cola, **When** llega su turno, **Then** recibe notificación "Es tu turno" y se le redirige al seat map.
4. **Given** múltiples usuarios en la cola, **When** se libera un batch, **Then** los usuarios se admiten en orden FIFO estricto.

**Functional Requirements**:
- **FR-003**: La cola debe ser FIFO estricta por `session_id`.
- **FR-004**: La posición del usuario debe actualizarse en tiempo real via Socket.IO.
- **FR-005**: La liberación de usuarios debe ser en batches controlados para no saturar el servidor.
- **FR-006**: El usuario debe recibir feedback "Gestionando su solicitud..." mientras espera.

---

### User Story 3 - Selección de Asientos/Zonas (Priority: P1)
Como usuario, quiero ver el mapa de la sesión y seleccionar asientos (cine) o zonas (concierto), con actualizaciones en tiempo real.

**Independent Test**: El usuario puede cargar la URL `/session/{id}/seats` y ver el mapa. En cine ve un grid de asientos. En concierto ve zonas clicables con disponibilidad.

**Acceptance Scenarios (Cine - Grid)**:
1. **Given** sesión de cine y usuario admitido, **When** el usuario ve el seat map, **Then** aparece un grid de asientos con estados: libre (blanco), ocupado (negro), no existe (gris).
2. **Given** el seat map cargado, **When** el usuario hace clic en un asiento libre, **Then** el asiento cambia a verde (#10B981) y se bloquea temporalmente en Redis.
3. **Given** un asiento bloqueado por el usuario actual, **When** otro usuario ve el seat map, **Then** ese asiento aparece en negro (ocupado).
4. **Given** un asiento con soft lock, **When** el TTL expira sin pago, **Then** el asiento vuelve a blanco (libre) para todos los usuarios via Socket.IO.

**Acceptance Scenarios (Concierto - Zonas)**:
5. **Given** sesión de concierto y usuario admitido, **When** el usuario ve el mapa, **Then** aparecen las zonas (Pista, Grada, VIP) con nombre, precio y disponibilidad.
6. **Given** el mapa de zonas, **When** el usuario selecciona una zona, **Then** puede elegir cantidad de entradas (máximo según capacidad disponible).
7. **Given** zonas con entradas seleccionadas, **When** otro usuario ve el mapa, **Then** la disponibilidad se actualiza en tiempo real via Socket.IO.

**Functional Requirements**:
- **FR-007**: El sistema debe impedir la reserva de asientos/zonas ya ocupados en la misma `session_id`.
- **FR-008**: Las sesiones de cine usan `venue_config` tipo `grid` con rows/cols. Las de conciertos usan tipo `zones` con precio por zona.
- **FR-009**: El soft lock debe tener un TTL configurable (5-10 minutos).
- **FR-010**: La API debe devolver error 422 si se intenta bloquear un asiento/zona ya ocupado.
- **FR-011**: Los cambios de estado deben propagarse en tiempo real via Socket.IO.
- **FR-012**: En conciertos, el precio se define por zona. En cine, el precio es fijo por sesión.

---

### User Story 4 - Autenticación (Priority: P2)
Como usuario, quiero poder crear una cuenta e iniciar sesión para guardar mi historial de compras, pero también quiero poder comprar como invitado sin registrarme.

**Independent Test**: El usuario puede registrarse con email/password o Google, iniciar sesión, o comprar como guest sin cuenta.

**Acceptance Scenarios**:
1. **Given** la página de login, **When** el usuario pulsa "Registrarse", **Then** puede crear cuenta con email, password, nombre y recibe un email de verificación.
2. **Given** la página de login, **When** el usuario pulsa "Continuar con Google", **Then** se autentica via Google OAuth y se crea/vincula su cuenta.
3. **Given** un usuario sin verificar email, **When** intenta acceder a funciones protegidas, **Then** recibe aviso de verificar su email.
4. **Given** el usuario olvida su password, **When** pulsa "Recuperar contraseña", **Then** recibe un email con enlace para restablecerla.
5. **Given** el flujo de compra, **When** el usuario no está logueado, **Then** puede comprar como guest introduciendo solo su email.

**Functional Requirements**:
- **FR-020**: El sistema debe soportar login con email/password via Laravel Sanctum.
- **FR-021**: El sistema debe soportar login con Google OAuth.
- **FR-022**: El registro requiere verificación de email.
- **FR-023**: El sistema debe soportar recuperación de contraseña.
- **FR-024**: Las compras pueden ser de usuarios logueados (user_id) o guests (email).

---

### User Story 5 - Cola de Compra (Priority: P2)
Como usuario, quiero que mi petición de compra entre en una cola y se procese de forma ordenada, recibiendo feedback del estado del pago.

**Independent Test**: El usuario puede hacer `POST /api/bookings` con sus asientos/zonas seleccionados. La compra entra en la cola y se procesa secuencialmente.

**Acceptance Scenarios**:
1. **Given** asientos/zonas seleccionados, **When** el usuario pulsa "Reservar", **Then** la petición entra en la cola de compra y recibe feedback "Procesando su compra...".
2. **Given** una compra en la cola, **When** el worker la procesa, **Then** el usuario recibe notificación "Procesando pago..." via Socket.IO.
3. **Given** un pago exitoso, **When** se confirma, **Then** los asientos/zonas pasan de Redis (soft lock) a PostgreSQL y el usuario recibe "Pago confirmado".
4. **Given** un pago fallido, **When** se rechaza, **Then** los soft locks se liberan y los asientos/zonas vuelven a estar libres y el usuario recibe "Pago rechazado".
5. **Given** múltiples compras en la cola, **When** se procesan, **Then** se procesan una a una (secuencial) sin duplicados.

**Functional Requirements**:
- **FR-025**: Los pagos se simulan con un delay aleatorio (2-5 segundos) y un 90% de éxito.
- **FR-026**: Al confirmar el pago, los soft locks de Redis deben persistirse en la base de datos.
- **FR-027**: Al fallar el pago, los soft locks deben liberarse.
- **FR-028**: No se puede procesar el mismo booking dos veces.
- **FR-029**: El usuario debe recibir feedback del estado del pago via Socket.IO.

---

### User Story 6 - Confirmación, QR y Email (Priority: P2)
Como usuario, quiero recibir un email con mi QR y resumen de la reserva, y ver la confirmación en la web.

**Independent Test**: Tras una compra confirmada, el usuario recibe un email con el QR y puede acceder a `/booking/{id}/confirmed` para ver el resumen.

**Acceptance Scenarios**:
1. **Given** una compra confirmada, **When** se procesa el pago, **Then** se envía un email al usuario (logueado o guest) con el QR y resumen de la entrada.
2. **Given** una compra confirmada, **When** el usuario accede a la página de confirmación, **Then** ve un resumen con evento, sesión, asientos/zonas y total.
3. **Given** la página de confirmación, **When** el usuario ve el QR, **Then** el código QR es scaneable y contiene la información de la reserva.
4. **Given** el resumen de la reserva, **When** el usuario ve la página, **Then** el diseño sigue el estilo Neo-Brutalista (recibo con sombras duras).

**Functional Requirements**:
- **FR-030**: El QR debe contener: booking_id, evento, sesión, asientos/zonas.
- **FR-031**: La página de confirmación debe mostrar un resumen estilo recibo.
- **FR-032**: El QR debe ser generado en el backend y servido como imagen.
- **FR-033**: Al confirmar la compra, se envía un email con el QR y resumen al email del usuario (user.email o guest_email).
- **FR-034**: El email de confirmación debe incluir: evento, fecha, asientos/zonas, total y QR embebido.

---

### User Story 7 - Perfil de Usuario (Priority: P2)
Como usuario registrado, quiero ver mi historial de compras y acceder a mis entradas desde mi perfil.

**Independent Test**: Un usuario logueado puede acceder a `/profile/tickets` y ver todas sus compras con opción a descargar el QR.

**Acceptance Scenarios**:
1. **Given** un usuario logueado, **When** accede a `/profile/tickets`, **Then** ve una lista de todas sus reservas con estado (confirmada, cancelada).
2. **Given** el historial de compras, **When** el usuario pulsa en una reserva, **Then** ve el detalle completo (evento, sesión, asientos/zonas, total, QR).
3. **Given** una reserva confirmada, **When** el usuario pulsa "Descargar QR", **Then** se descarga o muestra el código QR de la entrada.
4. **Given** un usuario sin compras, **When** accede al perfil, **Then** ve un mensaje indicando que no tiene entradas.

**Functional Requirements**:
- **FR-035**: El perfil solo es accesible para usuarios autenticados (requiere token Sanctum).
- **FR-036**: El historial muestra todas las bookings del usuario con su estado.
- **FR-037**: El usuario puede ver el detalle y QR de cada reserva desde el perfil.
- **FR-038**: Las compras guest no aparecen en ningún perfil (solo se acceden por email).

## Non-Functional Requirements
- **NFR-01**: El sistema debe soportar picos de miles de usuarios concurrentes.
- **NFR-02**: La comunicación en tiempo real debe tener latencia menor a 500ms.
- **NFR-03**: Los soft locks nunca deben permitir que dos usuarios reserven el mismo asiento/zona.
- **NFR-04**: La cola FIFO debe garantizar orden estricto de llegada.
- **NFR-05**: Las compras guest deben funcionar sin autenticación previa.
- **NFR-06**: Los emails deben enviarse de forma asíncrona (Laravel Queues) para no bloquear la respuesta.
