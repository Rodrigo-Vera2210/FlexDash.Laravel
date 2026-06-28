# Feature Specification: Ticket System

**Feature Branch**: `019-ticket-system`

**Created**: 2026-06-28

**Status**: Draft

**Input**: User description: "Crear un módulo de tickets para reportar todos los errores que ocurren en el sistema..."

## User Scenarios & Testing

### User Story 1 - User Ticket Reporting and Inbox (Priority: P1)

Como usuario autenticado, quiero poder acceder a la sección de tickets desde cualquier parte de la aplicación (mediante un botón en la barra superior derecha) para reportar problemas y ver el estado de mis reportes.

**Why this priority**: Es la base del flujo de usuario, permitiendo reportar y hacer seguimiento a sus propios tickets.

**Independent Test**: Se puede verificar accediendo al botón del header, rellenando el formulario de reporte, enviándolo y viéndolo listado en la pantalla `/tickets`.

**Acceptance Scenarios**:

1. **Given** un usuario autenticado en la plataforma, **When** hace clic en el botón de la barra superior derecha, **Then** es redirigido a `/tickets` donde se listan sus tickets reportados.
2. **Given** la página de creación de un nuevo ticket, **When** presiona "Nuevo Ticket", rellena el título, descripción, nivel de severidad (bajo, medio, alto) y adjunta al menos una imagen de evidencia válida, **Then** el ticket es creado con estado `pendiente` y se muestra en su lista.
3. **Given** la página de creación de un nuevo ticket, **When** intenta enviar el formulario sin adjuntar ninguna imagen de evidencia, **Then** el sistema muestra un mensaje de error de validación y no permite guardar el ticket.

---

### User Story 2 - SuperAdmin Ticket Management and Severity Classification (Priority: P2)

Como superadmin, quiero poder ver un buzón con todos los tickets del sistema para gestionarlos, clasificarlos por severidad e interactuar con los usuarios.

**Why this priority**: Permite al superadministrador responder a las solicitudes y cambiar los estados del ticket.

**Independent Test**: El superadmin accede a `/superadmin/tickets`, ve la lista completa, puede filtrar por severidad y hacer clic para ver el detalle.

**Acceptance Scenarios**:

1. **Given** un superadmin autenticado, **When** accede a `/superadmin/tickets`, **Then** ve un listado de todos los tickets con título, fecha, usuario y severidad.
2. **Given** el detalle de un ticket, **When** el superadmin intenta cambiar el estado a `aprobado` o `rechazado` sin haber respondido con un mensaje primero, **Then** el sistema muestra un error y bloquea la acción.
3. **Given** el detalle de un ticket con al menos una respuesta del superadmin, **When** cambia el estado a `aprobado` o `rechazado`, **Then** el estado se actualiza en la base de datos y se refleja al usuario.

---

### User Story 3 - Interactive Messaging & Chat (Priority: P3)

Como usuario o superadmin, quiero poder enviar y recibir mensajes dentro de un ticket para conversar sobre el avance del problema reportado.

**Why this priority**: Facilita la comunicación directa entre el cliente y el soporte.

**Independent Test**: El usuario escribe un mensaje en el chat del ticket y el superadmin lo ve inmediatamente al cargar la página, pudiendo responderle.

**Acceptance Scenarios**:

1. **Given** un ticket abierto, **When** un usuario o superadmin escribe un mensaje en la sección de discusión y lo envía, **Then** el mensaje se guarda en la base de datos y se muestra en la línea de tiempo del ticket.

---

### User Story 4 - Direct Error / Exception Reporting (Priority: P4)

Como usuario del sistema, cuando ocurre una excepción o error inesperado (pantalla de error 500 o fallas controladas), quiero que el sistema me ofrezca un botón para reportar el error directamente como ticket.

**Why this priority**: Automatiza y facilita el reporte de bugs adjuntando detalles técnicos del fallo.

**Independent Test**: Se genera un error controlado, la vista de error muestra el botón "Reportar Error", al hacer clic se crea automáticamente un ticket de severidad alta con los detalles del error.

**Acceptance Scenarios**:

1. **Given** un error de servidor (500) en el sistema, **When** se renderiza la vista de excepción, **Then** se muestra un botón "Reportar a Soporte" que pre-rellena y crea un ticket con el detalle del error (mensaje y traza) asignado al usuario actual.

---

### Edge Cases

- **Usuario sin sesión reportando un error**: Si ocurre un error 500 y el usuario no ha iniciado sesión, el reporte de error directo debe requerir iniciar sesión o registrar el ticket como anónimo.
- **Cambio de estado concurrente**: Si el superadmin intenta aprobar un ticket mientras el usuario envía una respuesta al mismo tiempo.

## Requirements

### Functional Requirements

- **FR-001**: El sistema DEBE mostrar un botón de acceso a tickets en el header para todos los usuarios autenticados.
- **FR-002**: El sistema DEBE guardar los tickets con los siguientes estados obligatorios: `pendiente`, `en proceso`, `rechazado`, `aprobado`.
- **FR-003**: El sistema DEBE permitir clasificar los tickets en niveles de severidad: `bajo`, `medio`, `alto`.
- **FR-004**: El sistema DEBE impedir cambiar el estado de un ticket a `aprobado` o `rechazado` si no existe al menos un mensaje/respuesta por parte del superadmin en ese ticket.
- **FR-005**: El sistema DEBE ofrecer un botón de reporte directo en el manejador global de excepciones del sistema.
- **FR-006**: La base de datos DEBE persistir la relación de cada ticket con el `user_id` y `company_id` del usuario que reportó.
- **FR-007**: El sistema DEBE validar que al crear un ticket se adjunte como mínimo una imagen de evidencia válida.
- **FR-008**: El sistema DEBE soportar la carga de múltiples imágenes de evidencia para un mismo ticket.

### Key Entities

- **Ticket**: Representa el problema reportado. Atributos: `id`, `user_id`, `company_id`, `title`, `description`, `severity` (bajo, medio, alto), `status` (pendiente, en proceso, rechazado, aprobado), `created_at`, `updated_at`.
- **TicketMessage**: Representa la discusión/chat de un ticket. Atributos: `id`, `ticket_id`, `user_id` (remitente), `message`, `created_at`.
- **TicketAttachment**: Representa las imágenes de evidencia asociadas a un ticket. Atributos: `id`, `ticket_id`, `file_path`, `created_at`.

## Success Criteria

### Measurable Outcomes

- **SC-001**: Los usuarios pueden crear un ticket en menos de 30 segundos.
- **SC-002**: El sistema bloquea el 100% de los intentos de aprobación/rechazo de tickets que no posean respuestas del superadmin.
- **SC-003**: El botón de reporte directo en excepciones captura y guarda la traza de error correctamente en la descripción del ticket.

## Assumptions

- Se asume que el sistema de excepciones de Laravel (`app/Exceptions/Handler.php` o `bootstrap/app.php` en Laravel 11/12) se puede extender para pasar variables de sesión o contexto de error a la vista de error.
