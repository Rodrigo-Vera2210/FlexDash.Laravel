# Feature Specification: Cash Box Management (Administración de Caja Chica)

**Feature Branch**: `006-cash-box-management`

**Created**: 2026-06-11

**Status**: Draft

**Input**: User description: "apartado para administrar nuestros movimientos contables con requisitos de caja chica, cierre de caja con ingresos y egresos, y cancelar valores pendientes seleccionando varias ordenes del mismo cliente o proveedor."

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Open Cash Box Register (Priority: P1)

As an cashier or admin user, I want to open the daily cash register session by defining a starting cash balance so that all subsequent monetary transactions can be tracked.

**Why this priority**: It is the mandatory starting point for tracking any drawer operations.

**Independent Test**: Can be tested by opening the cash box dashboard when no box is open, entering an opening balance, and verifying the cash box is marked as "OPEN".

**Acceptance Scenarios**:
1. **Given** no cash box is currently open, **When** I navigate to the Cash Box section, **Then** I see an "Open Cash Box" form.
2. **Given** the "Open Cash Box" form, **When** I enter a valid opening balance (e.g., S/ 200.00) and click "Open", **Then** the cash box status changes to "OPEN", the session start timestamp is recorded, and the cash box dashboard is displayed.

---

### User Story 2 - Daily Cash Drawer Closure (Priority: P1)

As a cashier or manager, I want to perform the daily close of the cash box session, reporting the counted cash balance so that discrepancies between actual and expected balances are recorded.

**Why this priority**: Required to reconcile the day's inflows and outflows and secure the session data.

**Independent Test**: Can be tested by opening a session, performing transactions, executing the close action with a specific counted cash amount, and verifying status becomes "CLOSED".

**Acceptance Scenarios**:
1. **Given** an open cash box session, **When** I view the close modal, **Then** I see the session summary: opening balance, total inflows, total outflows, and calculated expected closing balance.
2. **Given** the close modal, **When** I input the actual counted cash (e.g. S/ 250.00) and click "Close Cash Box", **Then** the status updates to "CLOSED", the closing timestamp is recorded, and any discrepancy (difference between actual and expected balance) is permanently logged.

---

### User Story 3 - Manual Cash Movements (Petty Cash Inflows/Outflows) (Priority: P2)

As a cashier, I want to record miscellaneous inflows or outflows (petty cash adjustments) that are not linked to sales or purchases (e.g., buying office supplies or adding cash).

**Why this priority**: Crucial for tracking overhead or non-order expenses (e.g., transport, food, cleaning supplies) that affect physical cash.

**Independent Test**: Can be tested by executing manual adjustments from the active session panel and verifying the balance changes immediately.

**Acceptance Scenarios**:
1. **Given** an open cash box session, **When** I submit an outflow transaction for S/ 15.00 with the concept "Comida", **Then** the transaction is logged as an "egreso" and the cash box expected balance decreases by S/ 15.00.
2. **Given** an open cash box session, **When** I submit an inflow transaction for S/ 50.00 with the concept "Aporte de caja", **Then** the transaction is logged as an "ingreso" and the cash box expected balance increases by S/ 50.00.

---

### User Story 4 - Single-Partner Batch Payment (Priority: P2)

As an accountant or cashier, I want to select multiple pending sales (for a client) or purchases (for a provider) and register a single consolidated payment from the cash box to pay off their balances.

**Why this priority**: Enables batch operations, making payment processing fast and efficient.

**Independent Test**: Can be tested by selecting a client with multiple pending sales, checking three of their sales, entering a total payment amount, and asserting that the sales balances are updated chronologically.

**Acceptance Scenarios**:
1. **Given** an open cash box session, **When** I select a client/provider in the batch payment page, **Then** only their pending orders (status approved/pending balance > 0) are listed.
2. **Given** a list of pending orders for a partner, **When** I check multiple orders and submit a payment that is less than or equal to the total pending amount, **Then** the system creates polymorphic payments, decreases the pending balances of the orders in chronological order (FIFO), updates order statuses to PAID where applicable, and logs the transactions in the cash box.

---

## Edge Cases

- **Multiple Open Sessions**: The system must enforce that a user (or the system globally) can have at most one active "OPEN" cash box at a time.
- **Transactions on Closed Box**: All payment creations (Sales and Purchases) must verify if there is an active cash box. If not, they must throw an exception or prompt to open one, preventing untracked cash drawer updates.
- **Negative Discrepancies**: If the actual closing balance is less than expected, it must log a negative difference without blocking the close action.
- **Zero or Negative Payments**: Batch payments must validate that the total paid amount is greater than zero and does not exceed the sum of pending balances for the selected orders.
- **Mixed Partners**: The UI and backend must prevent selecting orders belonging to different partners for the same batch payment transaction.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST support opening a cash box session with a decimal opening balance.
- **FR-002**: System MUST allow closing an open cash box session by supplying the actual counted cash balance and optional notes.
- **FR-003**: System MUST record daily movements categorizing them as `ingreso` (inflow) or `egreso` (outflow) with amount, concept, and timestamp.
- **FR-004**: System MUST calculate the expected balance dynamically: `opening_balance + sum(inflows) - sum(outflows)`.
- **FR-005**: System MUST allow selecting multiple pending orders (sales or purchases) for a single partner to apply a batch payment.
- **FR-006**: Batch payments MUST distribute the paid amount chronologically (oldest issue date first) across the selected orders.
- **FR-007**: Standard payments registered from sales or purchases views MUST automatically log a transaction in the open cash box if one is active.

### Key Entities

- **CashBox**: Represents a register session. Has `user_id`, `status` (open/closed), `opening_balance`, `expected_closing_balance`, `actual_closing_balance`, `difference`, `opened_at`, `closed_at`, and `notes`.
- **CashBoxTransaction**: Represents a cash drawer movement. Has `cash_box_id`, `user_id`, `type` (ingreso/egreso), `amount`, `concept`, `payment_id` (optional, links to polymorphism table `payments`), and timestamps.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Cashiers can open or close a cash box session in under 3 clicks from the sidebar.
- **SC-002**: Reconciling and paying off three invoices of a single supplier at once takes under 15 seconds through the batch payment UI.
- **SC-003**: Every single financial transaction in Sales or Purchases is 100% accounted for in the Cash Box log if a session is open.
- **SC-004**: System successfully prevents closure validation bypasses (all closures must log differences).

---

## Assumptions

- We assume a single central drawer register per company (or per logged-in user session) is sufficient for v1.
- We assume payments made via credit cards or transfers should also flow through the cash box log but tagged with their corresponding payment method.
