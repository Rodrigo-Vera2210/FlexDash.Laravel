# Implementation Plan: Ticket System

**Branch**: `019-ticket-system` | **Date**: 2026-06-28 | **Spec**: [spec.md](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/specs/019-ticket-system/spec.md)

## Summary

We will build a self-contained Ticket System module inside `app/Modules/Tickets` following the constitutional guidelines. The system allows users to report bugs (including direct reporting from exception views), exchange messages, and lets the superadmin manage, classify, and reply to tickets before changing their status to approved or rejected.

## Technical Context

- **Language/Version**: PHP 8.2+
- **Primary Dependencies**: Laravel 12.x framework core
- **Storage**: SQLite database (via Laravel migrations for new tables `tickets` and `ticket_messages`)
- **Testing**: PHPUnit feature & unit tests inside `app/Modules/Tickets/Tests/`
- **Target Platform**: Web application (Responsive view with Tailwind CSS)
- **Project Type**: Module-Based Backend Architecture

## Constitution Check

- **Test-Driven Development**: Tests will be written for both Ticket services and Controllers.
- **Backend Architecture**: Organised under `app/Modules/Tickets/` (Controllers, Services, Models, Requests, Views, Tests).
- **Timezone / Localization**: Interface strings in Spanish (`bajo`, `medio`, `alto`, `pendiente`, `en proceso`, `rechazado`, `aprobado`).

## Project Structure

### Documentation

```text
specs/019-ticket-system/
├── spec.md              # Feature specification
├── plan.md              # This file
└── tasks.md             # Task list checklist
```

### Source Code

The Ticket System will be structured as a backend module:

```text
app/Modules/Tickets/
├── Controllers/
│   ├── TicketController.php         # User facing actions
│   └── SuperAdminTicketController.php # Superadmin actions
├── Services/
│   └── TicketService.php            # Business logic (rules, message tracking)
├── Models/
│   ├── Ticket.php                   # Ticket eloquent model
│   └── TicketMessage.php            # TicketMessage eloquent model
├── Requests/
│   ├── CreateTicketRequest.php      # Validation rules for ticket creation
│   └── StoreMessageRequest.php      # Validation rules for messages
├── Views/
│   ├── index.blade.php              # User tickets index
│   ├── show.blade.php               # User ticket chat detail
│   ├── create.blade.php             # New ticket form
│   ├── superadmin/
│   │   ├── index.blade.php          # Superadmin inbox
│   │   └── show.blade.php           # Superadmin ticket response detail
└── Tests/
    ├── Feature/
    │   ├── TicketFlowTest.php       # Complete flow test
    └── Unit/
        └── TicketServiceTest.php    # Business rules (e.g. check message exists before approve)
```

## Data Schema & Migrations

We will create three tables:

1. **`tickets`**:
   - `id` (bigint, PK)
   - `user_id` (foreign key to `users`, cascade delete)
   - `company_id` (foreign key to `companies`, nullable, cascade delete)
   - `title` (varchar 255)
   - `description` (text)
   - `severity` (enum: `bajo`, `medio`, `alto`)
   - `status` (enum: `pendiente`, `en proceso`, `rechazado`, `aprobado`, default: `pendiente`)
   - `error_trace` (text, nullable - for direct exception reports)
   - `timestamps`

2. **`ticket_messages`**:
   - `id` (bigint, PK)
   - `ticket_id` (foreign key to `tickets`, cascade delete)
   - `user_id` (foreign key to `users` - sender, cascade delete)
   - `message` (text)
   - `timestamps`

3. **`ticket_attachments`**:
   - `id` (bigint, PK)
   - `ticket_id` (foreign key to `tickets`, cascade delete)
   - `file_path` (varchar 255)
   - `timestamps`

Migrations will reside in `database/migrations/` per constitution.
