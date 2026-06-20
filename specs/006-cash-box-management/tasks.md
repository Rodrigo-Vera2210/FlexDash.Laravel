# Tasks: Cash Box Management (Spec 006)

**Input**: Design documents from `/specs/006-cash-box-management/`

**Prerequisites**: `plan.md`, `spec.md`

---

## Phase 1: Setup (Shared Infrastructure)

- [ ] T001 Create module directories for CashBox in `app/Modules/CashBox/` (Controllers, Services, Models, Tests).
- [ ] T002 Implement the database migration in `database/migrations/2026_06_11_000001_create_cash_boxes_table.php`.
- [ ] T003 [P] Add the "Caja Chica" navigation link into the sidebar in `resources/views/layouts/app.blade.php`.

---

## Phase 2: Foundational Models & Services

- [ ] T004 Create `CashBox` model in `app/Modules/CashBox/Models/CashBox.php` with relations.
- [ ] T005 Create `CashBoxTransaction` model in `app/Modules/CashBox/Models/CashBoxTransaction.php` with relations.
- [ ] T006 Implement base service methods in `app/Modules/CashBox/Services/CashBoxService.php` for open/close registers and transactions.
- [ ] T007 [P] Create controller `app/Modules/CashBox/Controllers/CashBoxController.php` with standard base responses.
- [ ] T008 [P] Map Web routes inside `routes/web.php` under the `auth.jwt` middleware group.

---

## Phase 3: User Story 1 - Open Cash Box (Priority: P1)

- [ ] T009 Write failing test for opening a cash box session in `app/Modules/CashBox/Tests/Feature/CashBoxManagementTest.php`.
- [ ] T010 Implement `open()` logic in `CashBoxService.php` and `CashBoxController.php`.
- [ ] T011 Build the initial "Caja Cerrada" UI view with the Open Cash Box form in `resources/views/cashbox/index.blade.php`.
- [ ] T012 Assert the tests pass successfully.

---

## Phase 4: User Story 2 - Daily Close & Ledger (Priority: P1)

- [ ] T013 Write failing test for closing register and ledger summary verification.
- [ ] T014 Implement expected balance calculations and close action in `CashBoxService.php` and `CashBoxController.php`.
- [ ] T015 Implement the "Cierre de Caja" modal (actual cash inputs, discrepancies, closed_at status) using AlpineJS in `cashbox/index.blade.php`.
- [ ] T016 Verify tests pass for register closures and transaction lists.

---

## Phase 5: User Story 3 - Petty Cash Movements (Priority: P2)

- [ ] T017 Write tests for manual inflows (ingresos) and outflows (egresos).
- [ ] T018 Implement manual adjustments endpoint in `CashBoxController.php` and record functions in `CashBoxService.php`.
- [ ] T019 Update `cashbox/index.blade.php` with the Quick Inflow/Outflow forms and ledger movement table.
- [ ] T020 [MODIFY] Update `app/Services/PaymentService.php` to inject `CashBoxService` and automatically record a `CashBoxTransaction` for every new payment if a cash box is open.

---

## Phase 6: User Story 4 - Single-Partner Batch Payment (Priority: P2)

- [ ] T021 Write failing integration tests for single-partner multi-document payment distribution (FIFO chronological order).
- [ ] T022 Implement partner's pending documents AJAX retriever in `CashBoxController.php`.
- [ ] T023 Implement the batch payment processor in `CashBoxService.php` distributing amounts across selected sales/purchases.
- [ ] T024 Create the Batch Payment view template in `resources/views/cashbox/batch-payment.blade.php` (checkbox table, real-time calculations).

---

## Phase 7: Polish & Verification

- [ ] T025 Run the full suite `php artisan test` and verify all tests pass.
- [ ] T026 Execute frontend bundle checks using `npm run build` to ensure assets assemble correctly.
