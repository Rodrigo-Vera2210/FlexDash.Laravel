# Feature Specification: Historial de Caja Chica, Corrección de Saldo y Atribución de Transacciones

**Feature Branch**: `016-cashbox-history-and-fixes`

**Created**: 2026-06-24

**Status**: Draft

**Input**: User description: "Crear una sección para el histórico de la caja chica. Porque hasta ahora se puede abrir y cerrar la caja chica, pero no hay donde revisar la caja chica de una fecha específica. También toca validar la cantidad con la que se cierra. Porque abrí con $100, hice un ingreso de $2 y un egreso de $2. Y me dice que tengo que cerrar con $200, cosa que está mal. Toca aumentar en las transacciones el vendedor o administrador que hizo la transacción."

---

## 1. Feature Description & Context

The current Cash Box module (Spec 006) allows opening/closing daily cash register sessions, recording manual inflows/outflows, and processing batch payments. However, three critical issues have been identified:

### Problem 1: Incorrect Expected Closing Balance (Bug — Critical)

When opening a cash box with `$100`, the system doubles the expected closing balance to `$200`. This happens because `CashBoxService::openBox()` sets `expected_closing_balance = $openingBalance` on creation and then calls `recordTransaction()` with the same amount as an `ingreso`. The `recordTransaction()` method adds the amount to `expected_closing_balance` again, resulting in double-counting.

**Root Cause Analysis** (in [CashBoxService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/CashBox/Services/CashBoxService.php)):
```php
// Line 32-36: Box created with expected_closing_balance = $openingBalance ($100)
$box = CashBox::create([
    'expected_closing_balance' => $openingBalance, // Sets to $100
    ...
]);

// Line 42: Then recordTransaction adds $100 again → expected becomes $200
$this->recordTransaction($box, 'ingreso', $openingBalance, 'Saldo inicial / Apertura de caja');
```

**Fix**: Set `expected_closing_balance = 0` on creation so that `recordTransaction()` correctly adds the opening balance once, resulting in `$100`. Alternatively, skip recording the opening balance as a transaction entirely and only track actual inflows/outflows.

### Problem 2: No Cash Box History View (Feature Gap)

After closing a cash box session, there is no way to review historical sessions. Users cannot:
- Browse past closed sessions by date range.
- View the transactions of a specific past session.
- Compare discrepancies across sessions.
- Export past session reports.

### Problem 3: Transaction User Attribution (Enhancement)

While `cash_box_transactions` already stores a `user_id`, the current UI and adjustment form do not prominently display **which seller or administrator** performed each transaction. The user wants clear attribution of who made each inflow/outflow/adjustment, especially in multi-user environments where a shift may have multiple cashiers.

### Key Rules
1. **Available to All Plans**: Cash box functionality remains available to all subscription plans via the `auth.module:caja_chica` middleware.
2. **Multi-Tenant**: Cash boxes are scoped per company (`company_id`) using `BelongsToCompany` trait.
3. **Balance Integrity**: The expected closing balance MUST always equal `opening_balance + Σ(inflows) - Σ(outflows)` — no double-counting.
4. **Transaction Attribution**: Every transaction MUST record and display the authenticated user who created it.

---

## 2. User Stories & Acceptance Criteria

### User Story 1 - Corrección del Cálculo de Saldo Esperado (Priority: P0 — Bug Fix)
> **As a** Cashier / Admin,
> **I want** the expected closing balance to be correctly calculated,
> **So that** the amount shown to close matches the actual expected cash in the register.

**Why this priority**: This is a critical business logic bug that causes confusion and incorrect reconciliation data.

**Independent Test**: Can be tested by opening a cash box with $100, recording an inflow of $2 and an outflow of $2, and verifying the expected balance is $100 (not $200).

**Acceptance Scenarios**:

1. **Given** no cash box is open, **When** I open a new session with an opening balance of $100.00, **Then** the expected closing balance shown is $100.00.
2. **Given** an open cash box with $100 opening balance, **When** I record a manual inflow of $2.00, **Then** the expected closing balance updates to $102.00.
3. **Given** an expected balance of $102.00, **When** I record a manual outflow of $2.00, **Then** the expected closing balance updates to $100.00.
4. **Given** an expected balance of $100.00, **When** I close the cash box with actual counted cash of $100.00, **Then** the difference is $0.00 (no discrepancy).

---

### User Story 2 - Historial de Sesiones de Caja Chica (Priority: P1)
> **As a** Company Owner / Admin,
> **I want to** browse historical cash box sessions by date,
> **So that** I can review past sessions, verify discrepancies, and audit cash movements.

**Why this priority**: Essential for accountability and auditing. Without history, closed sessions are effectively invisible.

**Independent Test**: Can be tested by closing two cash box sessions on different dates, navigating to the history view, filtering by date range, and verifying both sessions appear with correct totals.

**Acceptance Scenarios**:

1. **Given** the user navigates to `/cashbox/history`, **When** the page loads, **Then** a paginated list of all closed cash box sessions is displayed, sorted by most recent first, showing: date, opened by (user), opening balance, expected balance, actual balance, difference, and status.
2. **Given** the history list, **When** the user clicks a date filter and selects a date range, **Then** the list filters to show only sessions opened within that range.
3. **Given** the history list, **When** the user clicks on a specific closed session, **Then** a detail view shows the full session summary and all transactions for that session.
4. **Given** the session detail view, **When** the user clicks "Exportar Excel", **Then** the existing Excel export function generates a report for the selected historical session.
5. **Given** the session detail view, **When** the user reviews the transactions list, **Then** each transaction displays the responsible user's name alongside the date, concept, type, and amount.

---

### User Story 3 - Atribución de Usuario en Transacciones (Priority: P1)
> **As a** Company Owner / Admin,
> **I want to** see which seller or administrator created each cash box transaction,
> **So that** I can audit individual accountability for every cash movement.

**Why this priority**: Critical for trust and audit trail in multi-user environments. The data already exists but needs UI exposure.

**Independent Test**: Can be tested by having two different users record transactions in the same session and verifying each transaction shows the correct user name.

**Acceptance Scenarios**:

1. **Given** an open cash box session, **When** User A (admin) records a manual inflow, **Then** the transaction ledger shows "User A" in the "Responsable" column for that transaction.
2. **Given** the same session, **When** User B (seller) records a payment via a sale, **Then** the automatically recorded cash box transaction shows "User B" as the responsible user.
3. **Given** the cash box history detail view, **When** reviewing a past session's transactions, **Then** each transaction shows the responsible user who performed it.
4. **Given** the cash box Excel export, **When** exporting a session report, **Then** the "Usuario" column contains the actual user who made each transaction (not the session opener by default).

---

### Edge Cases

- **Opening with $0 balance**: System must accept $0.00 as a valid opening balance. Expected closing balance should start at $0.00.
- **Multiple rapid transactions**: Concurrent transactions from different users must not corrupt the expected balance (row-level locking already exists via `lockForUpdate()`).
- **History with no closed sessions**: If no sessions have been closed yet, the history view should show an empty state with a helpful message.
- **Session spanning midnight**: A session opened on June 23 and closed on June 24 should appear when filtering by either date.
- **Large number of transactions**: Pagination must be enforced on the transaction list (both active and historical).

---

## 3. Requirements

### Functional Requirements

- **FR-001** (Bug Fix): The `expected_closing_balance` MUST be calculated as `opening_balance + Σ(inflows) - Σ(outflows)` without double-counting the opening balance.
- **FR-002**: System MUST provide a historical view of all closed cash box sessions at `/cashbox/history`.
- **FR-003**: History view MUST support filtering by date range (opened_at).
- **FR-004**: History view MUST display: date opened, date closed, opened by (user name), opening balance, expected closing balance, actual closing balance, difference, and session status.
- **FR-005**: System MUST allow viewing the full transaction detail of any historical session.
- **FR-006**: System MUST allow exporting any historical session to Excel (reuse existing export).
- **FR-007**: Every `CashBoxTransaction` MUST display the name of the user who created it in all views (active ledger, history detail, Excel export).
- **FR-008**: History sessions MUST be paginated (15 per page).
- **FR-009**: The current active session (if any) MUST NOT appear in the history list (only CLOSED sessions).

### Key Entities (Modifications)

- **CashBox**: No schema change. Logic fix in `CashBoxService::openBox()` to set `expected_closing_balance = 0` before the opening transaction is recorded.
- **CashBoxTransaction**: No schema change. `user_id` already exists and is populated. UI and queries need to expose this data.

---

## 4. Proposed Database Changes

No migration changes are required. The existing schema already supports all needed data:

```
cash_boxes
├── user_id (FK → users) — who opened the session
├── expected_closing_balance — will be fixed via logic
└── ...

cash_box_transactions
├── user_id (FK → users) — who made the transaction (already stored)
└── ...
```

The fix is purely at the **service logic layer** and **presentation layer**.

---

## 5. Security & Validation Constraints

1. **Authentication**: All cash box endpoints require JWT validation (`auth.jwt`) and `auth.module:caja_chica`.
2. **Multi-Tenant Isolation**: All CashBox queries scoped by `company_id` via `BelongsToCompany` trait.
3. **Authorization**: Only users with access to the `caja_chica` module can view history.
4. **Data Integrity**: History is read-only — closed sessions cannot be modified.

---

## 6. Success Criteria

### Measurable Outcomes
- **SC-001**: Opening a cash box with $100 results in expected closing balance of $100 (not $200). Regression test passes.
- **SC-002**: Users can browse and filter past sessions in under 3 clicks from the sidebar.
- **SC-003**: Each transaction in the ledger and history displays the correct user responsible, with 100% attribution accuracy.
- **SC-004**: Excel exports of historical sessions include the responsible user per transaction.

---

## 7. Assumptions

- The existing `company_id` scoping via `BelongsToCompany` trait on `CashBox` is sufficient for multi-tenant isolation in history queries.
- The existing Excel export logic in `CashBoxController::exportExcel()` will be reused for historical sessions with minimal modifications (it already accepts a `CashBox` model).
- The `user_id` field in `cash_box_transactions` is already correctly populated for all existing transactions (both manual and automatic via `PaymentService`).
- The inflow stats calculation in `CashBoxController::index()` correctly excludes the opening balance transaction via the `!= 'Saldo inicial / Apertura de caja'` filter. After the fix, this filter will still be needed to avoid showing the opening deposit as a "real" inflow in the stats cards.
