# Implementation Plan: Cash Box Management (Spec 006)

**Branch**: `006-cash-box-management` | **Date**: 2026-06-11 | **Spec**: `/specs/006-cash-box-management/spec.md`

## Summary
This plan details the implementation of the Cash Box module (`App\Modules\CashBox`). It provides petty cash tracking, opening/closing session controls (cierre de caja), manual adjustment logging, and single-partner batch payments for outstanding sales/purchases.

---

## Technical Context
- **Language/Version**: PHP 8.2+ with Laravel 12
- **Primary Dependencies**: Laravel Framework, Tailwind CSS, AlpineJS, Font Awesome
- **Storage**: SQLite database.
- **Testing**: PHPUnit / Pest (integration and unit tests).

---

## Constitution Check
- **TDD Requirement**: Failing tests for sessions, adjustments, and batch distribution must be written first.
- **Module Architecture**: All backend files will reside inside `app/Modules/CashBox/` including controllers, services, models, and tests. All database migrations must reside in the global `database/migrations/` directory.
- **Services Directory**: Business logic service `CashBoxService` will live in `app/Modules/CashBox/Services/`.
- **JWT Auth**: Dashboard endpoints protected under `auth.jwt` middleware.

---

## Project Structure
We will organize the code using the standard module layout:
```text
app/Modules/CashBox/
├── Controllers/
│   └── CashBoxController.php
├── Services/
│   └── CashBoxService.php
├── Models/
│   ├── CashBox.php
│   └── CashBoxTransaction.php
├── Views/
│   └── (integrated with global resources/views/cashbox/)
└── Tests/
    └── Feature/CashBoxManagementTest.php
```

---

## Proposed Changes

### 1. Database Migrations

#### [NEW] [2026_06_11_000001_create_cash_boxes_table.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/database/migrations/2026_06_11_000001_create_cash_boxes_table.php)
- Define `cash_boxes` table:
  - `id` (primary key)
  - `user_id` (foreign key to `users`)
  - `status` (string/enum: `OPEN`, `CLOSED`)
  - `opening_balance` (decimal: 14,2)
  - `expected_closing_balance` (decimal: 14,2)
  - `actual_closing_balance` (decimal: 14,2, nullable)
  - `difference` (decimal: 14,2, nullable)
  - `opened_at` (timestamp)
  - `closed_at` (timestamp, nullable)
  - `notes` (text, nullable)
  - `timestamps`
- Define `cash_box_transactions` table:
  - `id` (primary key)
  - `cash_box_id` (foreign key to `cash_boxes`, cascade delete)
  - `user_id` (foreign key to `users`)
  - `payment_id` (foreign key to `payments`, nullable, cascade null)
  - `type` (enum: `ingreso`, `egreso`)
  - `amount` (decimal: 14,2)
  - `concept` (string, 255)
  - `timestamps`

### 2. Models & Service Layer

#### [NEW] [CashBox.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/CashBox/Models/CashBox.php)
- Defines Eloquent relationships: `user()`, `transactions()`.
- Scope `active()` / `isOpen()`.

#### [NEW] [CashBoxTransaction.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/CashBox/Models/CashBoxTransaction.php)
- Defines relationships: `cashBox()`, `user()`, `payment()`.

#### [NEW] [CashBoxService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/CashBox/Services/CashBoxService.php)
- `openBox(float $balance, ?string $notes): CashBox`
- `closeBox(CashBox $box, float $actualCash, ?string $notes): CashBox`
- `recordTransaction(CashBox $box, string $type, float $amount, string $concept, ?int $paymentId = null): CashBoxTransaction`
- `processBatchPayment(string $partnerType, int $partnerId, array $documentIds, float $totalPaid, int $paymentMethodId, string $date, ?string $ref): void`
  - Fetches the selected documents (Sales/Purchases) ordered chronologically.
  - Distributes the amount and calls `PaymentService::register()` for each.
  - Automatically records cash box transactions linked to the payments.

#### [MODIFY] [PaymentService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Services/PaymentService.php)
- Update `register()` to verify if there is an active open `CashBox`.
- If strict cash tracking is enabled and no cash box is open, it throws an exception to prevent untracked cash flow.
- If a cash box is open, after successfully persisting the `Payment` record, it automatically triggers `CashBoxService::recordTransaction()` to log an `ingreso` (for Sales) or `egreso` (for Purchases) associated with the payment.

### 3. Controller & Routes

#### [NEW] [CashBoxController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/CashBox/Controllers/CashBoxController.php)
- `index()`: Display the status of the current open session or show the opening form. Lists today's transactions.
- `open(Request)`: Validates and opens cash register.
- `close(Request)`: Validates and closes register.
- `adjust(Request)`: Logs a manual inflow/outflow.
- `batchPaymentForm()`: Renders the batch payment screen, loading partners.
- `getPendingDocuments(Partner $partner)`: API endpoint to fetch pending orders for checkbox selections.
- `storeBatchPayment(Request)`: Executes single-partner multi-document payment.

#### [MODIFY] [routes/web.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/routes/web.php)
- Mount `/cashbox` routes protected by `auth.jwt`:
  - `GET /cashbox` -> `CashBoxController@index`
  - `POST /cashbox/open` -> `CashBoxController@open`
  - `POST /cashbox/close` -> `CashBoxController@close`
  - `POST /cashbox/adjust` -> `CashBoxController@adjust`
  - `GET /cashbox/batch-payment` -> `CashBoxController@batchPaymentForm`
  - `GET /cashbox/pending-docs/{partner}` -> `CashBoxController@getPendingDocuments`
  - `POST /cashbox/batch-payment` -> `CashBoxController@storeBatchPayment`

### 4. Frontend UI

#### [MODIFY] [app.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/layouts/app.blade.php)
- Append **Caja Chica** to sidebar links (`fa-solid fa-cash-register`).

#### [NEW] [cashbox/index.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/cashbox/index.blade.php)
- Clean, premium dashboard displaying drawer status (OPEN/CLOSED).
- Dynamic stats cards for: Opening Balance, Total Inflows, Total Outflows, Expected Balance.
- "Cierre de Caja" modal utilizing AlpineJS.
- Manual Adjustment form for adding quick inflows/outflows.

#### [NEW] [cashbox/batch-payment.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/cashbox/batch-payment.blade.php)
- Single-partner multi-document selector.
- Dropdown select for client/provider.
- AlpineJS/AJAX driven table lists pending orders with checkable inputs.
- Real-time calculation of total selected, input for total amount, and distribution summary.

---

## Verification Plan

### Automated Tests
- **`tests/Feature/CashBoxManagementTest.php`**
  - Verify only one cash box can be open.
  - Verify manual inflows and outflows update expected balance correctly.
  - Verify cash drawer closures compute correct discrepancies.
  - Verify batch payments distribute balances chronologically and register payments.

### Manual Verification
- Access `/cashbox` to open register with S/ 100.
- Execute a manual outflow of S/ 15 for office food and check ledger.
- Register standard payments on Sales and verify they are recorded under the open session.
- Select a client with 2 pending sales (S/ 50 and S/ 80). Submit a batch payment of S/ 100. Confirm the first sale becomes PAID (pending balance S/ 0) and the second is partially paid (pending balance S/ 30).
- Close the register with S/ 185 (assert S/ 0 discrepancy).
