# Tasks: Ticket System

**Input**: Design documents from `/specs/019-ticket-system/`

**Prerequisites**: plan.md, spec.md

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel
- **[Story]**: US1 (User flow), US2 (Superadmin flow), US3 (Chat/Messages), US4 (Exception reporting)

---

## Phase 1: Setup & Foundational

**Purpose**: Set up migrations, model structures, and base routes.

- [ ] T001 [P] Create migrations for `tickets`, `ticket_messages`, and `ticket_attachments` tables in `database/migrations/`
- [ ] T002 [P] Create `Ticket` model in `app/Modules/Tickets/Models/Ticket.php`
- [ ] T003 [P] Create `TicketMessage` model in `app/Modules/Tickets/Models/TicketMessage.php`
- [ ] T003b [P] Create `TicketAttachment` model in `app/Modules/Tickets/Models/TicketAttachment.php`
- [ ] T004 Create `TicketService` contract and implementation in `app/Modules/Tickets/Services/TicketService.php`
- [ ] T005 Register module routes in `routes/web.php` or `routes/api.php`

---

## Phase 2: User Story 1 - User Ticket Reporting and Inbox (Priority: P1)

**Goal**: Allow users to create tickets and view their ticket history.

- [ ] T006 [P] [US1] Create test suite `app/Modules/Tickets/Tests/Feature/TicketFlowTest.php` asserting ticket creation and listing.
- [ ] T006b [US1] Assert in tests that creating a ticket without any attachment returns a 422 error, and that multiple images are correctly uploaded and saved.
- [ ] T007 [US1] Implement `TicketController` in `app/Modules/Tickets/Controllers/TicketController.php` (actions `index`, `create`, `store`).
- [ ] T008 [US1] Design ticket list and create views in `app/Modules/Tickets/Views/index.blade.php` and `create.blade.php`. Include a file input supporting multiple files.
- [ ] T009 [US1] Add ticket link button to the top-right header in `resources/views/layouts/app.blade.php`.

---

## Phase 3: User Story 2 - SuperAdmin Ticket Management (Priority: P2)

**Goal**: Allow superadmins to view, reply to, and classify tickets.

- [ ] T010 [P] [US2] Add test cases to `TicketFlowTest.php` asserting superadmin access, filtering, and status transitions.
- [ ] T011 [US2] Implement `SuperAdminTicketController` in `app/Modules/Tickets/Controllers/SuperAdminTicketController.php` (actions `index`, `show`, `updateStatus`).
- [ ] T012 [US2] Design superadmin ticket inbox and show views in `app/Modules/Tickets/Views/superadmin/index.blade.php` and `show.blade.php`.
- [ ] T013 [US2] Enforce rule in `TicketService`: ticket cannot be updated to `aprobado` or `rechazado` if the superadmin has not replied first.

---

## Phase 4: User Story 3 - Interactive Messaging (Priority: P3)

**Goal**: Allow message exchange between reporting user and superadmin.

- [ ] T014 [P] [US3] Add test cases to `TicketFlowTest.php` asserting message insertion and retrieval.
- [ ] T015 [US3] Implement `sendMessage` action in controllers and service to append message to chat thread.
- [ ] T016 [US3] Display chat timeline in the ticket show views for both user and superadmin.

---

## Phase 5: User Story 4 - Direct Error/Exception Reporting (Priority: P4)

**Goal**: Display "Report to Support" button on system exceptions.

- [ ] T017 [US4] Customize the Laravel global exception handler or error 500 template (`resources/views/errors/500.blade.php`) to display the "Reportar a Soporte" button.
- [ ] T018 [US4] Implement the controller action to automatically create a high-severity ticket with exception trace details.

---

## Phase 6: Polish & Verification

- [ ] T019 Run full test suite and verify ticket system functionality.
- [ ] T020 Translate all ticket interfaces to Spanish (`bajo`, `medio`, `alto`, `pendiente`, `en proceso`, `rechazado`, `aprobado`).
