# Tasks: Historial de Caja Chica, Corrección de Saldo y Atribución de Transacciones (Spec 016)

**Input**: Design documents from `/specs/016-cashbox-history-and-fixes/`

**Prerequisites**: `plan.md`, `spec.md`

---

## Phase 1: Bug Fix — Expected Closing Balance Double-Count (P0)

**Goal**: Fix the critical bug where opening a cash box doubles the expected closing balance.

**Independent Test**: Open a cash box with $100, verify expected_closing_balance is $100 (not $200).

- [x] T001 Write failing test `test_opening_balance_not_doubled` in `tests/Feature/CashBoxBalanceFixTest.php`: open box with $100, assert `expected_closing_balance == 100`.
- [x] T002 Write failing test `test_balance_after_inflow_and_outflow`: open $100, inflow $2, outflow $2, assert `expected_closing_balance == 100`.
- [x] T003 Write failing test `test_close_with_correct_expected_balance`: open $100, inflow $50, outflow $20, close with $130, assert `difference == 0`.
- [x] T004 Write failing test `test_close_with_discrepancy`: open $100, close with $95, assert `difference == -5`.
- [x] T005 Fix `CashBoxService::openBox()` in `app/Modules/CashBox/Services/CashBoxService.php`: change `expected_closing_balance` from `$openingBalance` to `0` on creation, so `recordTransaction()` adds it exactly once.
- [x] T006 Run tests T001–T004 and verify all pass.

**Checkpoint**: Opening a cash box with $100 correctly shows $100 as expected balance. Inflows/outflows correctly adjust the expected balance.

---

## Phase 2: Cash Box History — Backend (P1)

**Goal**: Add controller methods and routes for browsing closed sessions.

**Independent Test**: Close a session, navigate to `/cashbox/history`, verify the session appears in the list.

- [x] T007 Add `history(Request $request)` method to `CashBoxController.php`: query CLOSED cash boxes for the company, support `date_from`/`date_to` filters, paginate 15, eager load `user`.
- [x] T008 Add `historyShow(CashBox $cashBox)` method to `CashBoxController.php`: load session with all transactions and their `user` relation, verify it belongs to the company and is CLOSED.
- [x] T009 Register routes in `routes/web.php` inside the `cashbox` group:
  - `GET /cashbox/history` → `CashBoxController@history` (name: `cashbox.history`)
  - `GET /cashbox/history/{cashBox}` → `CashBoxController@historyShow` (name: `cashbox.history.show`)

**Checkpoint**: History routes are registered and controllers return data correctly.

---

## Phase 3: Cash Box History — Views (P1)

**Goal**: Create the history list and detail views with premium styling.

**Independent Test**: Navigate to `/cashbox/history`, filter by date, click on a session, verify detail view loads.

- [x] T010 [P] Create `resources/views/cashbox/history.blade.php`: paginated table of closed sessions with columns (Fecha Apertura, Fecha Cierre, Responsable, Saldo Inicial, Saldo Esperado, Saldo Real, Diferencia, Acciones), date range filter form, empty state.
- [x] T011 [P] Create `resources/views/cashbox/history-show.blade.php`: read-only detail view with session summary cards (KPI style), metadata block, paginated transactions table with user attribution column, Excel export button, "Volver al Historial" link.
- [x] T012 [P] Update `resources/views/cashbox/index.blade.php`: add a "Ver Historial" button/link in the header actions area (visible whether box is open or closed) so users can navigate to `/cashbox/history`.
- [x] T013 [P] Update sidebar navigation in `resources/views/layouts/app.blade.php`: add "Historial" sub-link under the "Caja Chica" section, or ensure the history page is discoverable from the cash box index.

**Checkpoint**: Users can browse, filter, and drill into historical cash box sessions.

---

## Phase 4: Transaction User Attribution — UI (P1)

**Goal**: Ensure every transaction prominently displays which user created it.

**Independent Test**: Have two different users create transactions, verify each shows the correct user name.

- [x] T014 Verify the active session transactions table in `cashbox/index.blade.php` displays `$tx->user->name` in the "Responsable" column (already partially implemented — confirm styling and edge cases).
- [x] T015 Verify `CashBoxController::index()` eager loads `transactions.user` correctly for the active session (already at line 29 — confirm it works).
- [x] T016 Verify `CashBoxController::exportExcel()` renders the user name per transaction in the "Usuario" column (already at line 256 — confirm). Add actual/closing balance and difference to the summary section for closed sessions.

**Checkpoint**: User attribution is correct and visible in all views and exports.

---

## Phase 5: History Integration Tests (P2)

**Goal**: Write automated tests for history functionality.

- [x] T017 Write test `test_history_shows_only_closed_sessions` in `tests/Feature/CashBoxHistoryTest.php`: create open and closed sessions, GET `/cashbox/history`, assert only closed ones appear.
- [x] T018 Write test `test_history_filters_by_date_range`: create sessions on different dates, filter by range, assert correct results.
- [x] T019 Write test `test_history_show_displays_session_detail`: create and close a session with transactions, GET `/cashbox/history/{id}`, assert transactions are listed.
- [x] T020 Write test `test_transaction_shows_user_attribution`: create transactions with different user IDs, assert user names displayed in response.

**Checkpoint**: All history tests pass.

---

## Phase 6: Verification & Polish

- [x] T021 Run `tests/Feature/CashBoxBalanceFixTest.php` and verify all balance tests pass (0 failures).
- [x] T022 Run `tests/Feature/CashBoxHistoryTest.php` and verify all history tests pass (0 failures).
- [x] T023 Run the complete test suite (`php artisan test`) and verify zero failures across all 150+ tests.
- [x] T024 Run asset build (`npm run build`) and verify client-side compilation succeeds.
