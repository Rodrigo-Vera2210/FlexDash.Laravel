# Implementation Plan: Historial de Caja Chica, Corrección de Saldo y Atribución de Transacciones (Spec 016)

**Branch**: `016-cashbox-history-and-fixes` | **Date**: 2026-06-24 | **Spec**: `/specs/016-cashbox-history-and-fixes/spec.md`

## Summary

This plan addresses three issues in the Cash Box module: (1) a critical bug where the expected closing balance is doubled when opening a session, (2) the lack of a historical view for reviewing past closed sessions, and (3) improved visibility of which user performed each transaction. No database migrations are needed — all changes are in the service logic layer, controller, and views.

---

## Technical Context

- **Language/Version**: PHP 8.2+ with Laravel 12
- **Primary Dependencies**: Laravel Framework, Tailwind CSS, AlpineJS, Font Awesome
- **Storage**: SQLite (primary)
- **Testing**: PHPUnit + Pest
- **Constraints**: Multi-tenant via `BelongsToCompany` trait; JWT authentication; `auth.module:caja_chica` middleware

---

## Constitution Check

| Principle | Status | Notes |
|-----------|--------|-------|
| TDD (Red-Green-Refactor) | ✅ | Tests defined for balance fix and history |
| Layered Architecture | ✅ | Controller → Service → Model (no migration changes) |
| Module-Based Backend | ✅ | All changes within `app/Modules/CashBox/` |
| Clean Code & SOLID | ✅ | Methods ≤30 lines; SRP enforced |
| Technology Stack | ✅ | Laravel, Tailwind CSS, SQLite |
| JWT Authentication | ✅ | All routes behind `auth.jwt` + `auth.module:caja_chica` |
| Localization (Ecuador/SRI) | ✅ | Spanish labels; monetary formatting USD `$` |
| Migrations in `database/migrations/` | ✅ | No new migrations needed |

---

## Proposed Changes

### Phase 1: Bug Fix — Expected Closing Balance Double-Count

#### [MODIFY] [CashBoxService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/CashBox/Services/CashBoxService.php)

**Problem**: In `openBox()`, the cash box is created with `expected_closing_balance = $openingBalance` and then `recordTransaction()` is called with the same amount, which adds it again.

**Fix**: Change the initial `expected_closing_balance` to `0` so that `recordTransaction()` correctly adds the opening balance exactly once:

```diff
 $box = CashBox::create([
     'user_id'                  => auth()->id() ?? 1,
     'status'                   => 'OPEN',
     'opening_balance'          => $openingBalance,
-    'expected_closing_balance' => $openingBalance,
+    'expected_closing_balance' => 0,
     'opened_at'                => now(),
     'notes'                    => $notes,
 ]);
```

After this fix, the flow is:
1. Box created with `expected_closing_balance = 0`
2. `recordTransaction('ingreso', 100, 'Saldo inicial')` → `expected_closing_balance = 0 + 100 = 100` ✅

---

### Phase 2: Cash Box History — Controller & Routes

#### [MODIFY] [CashBoxController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/CashBox/Controllers/CashBoxController.php)

Add two new methods:

- `history(Request $request)`: Lists all **CLOSED** cash box sessions for the authenticated company, sorted by `closed_at` descending. Supports date range filtering via `date_from` and `date_to` query parameters. Returns paginated results (15 per page). Each session eagerly loads `user` (opener).

- `historyShow(CashBox $cashBox)`: Shows the detail of a specific closed session with all transactions eagerly loaded with their `user` relation. Validates that the session belongs to the authenticated user's company and is CLOSED.

#### [MODIFY] [routes/web.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/routes/web.php)

Add inside the `cashbox` route group:
```php
Route::get('/history', [CashBoxController::class, 'history'])->name('history');
Route::get('/history/{cashBox}', [CashBoxController::class, 'historyShow'])->name('history.show');
```

#### [MODIFY] [app.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/layouts/app.blade.php)

Add a sub-link or complementary navigation item for "Historial de Caja" under the existing "Caja Chica" sidebar link, or add a visible link/button on the cash box index page.

---

### Phase 3: Cash Box History — Views

#### [NEW] [cashbox/history.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/cashbox/history.blade.php)

A premium-styled paginated table showing closed sessions:
- **Filters**: Date range picker (`date_from`, `date_to`) with filter/clear buttons.
- **Columns**: Fecha Apertura, Fecha Cierre, Responsable (user.name), Saldo Inicial, Saldo Esperado, Saldo Real, Diferencia (color-coded: green if ≥ 0, red if < 0), Acciones (Ver Detalle, Exportar Excel).
- **Empty State**: Friendly message when no historical sessions exist.
- **Pagination**: Standard Laravel pagination links.

#### [NEW] [cashbox/history-show.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/cashbox/history-show.blade.php)

A read-only detail view of a specific closed session:
- **Session Summary Cards**: Opening balance, total inflows, total outflows, expected balance, actual balance, difference (with color coding).
- **Session Metadata**: Opened by, opened at, closed at, notes.
- **Transactions Table**: Date/Time, Concepto, **Responsable** (user who made the transaction), Tipo (badge: ingreso/egreso), Monto. Paginated.
- **Actions**: "Exportar Excel" button (reuses existing export route), "Volver al Historial" link.

---

### Phase 4: Transaction User Attribution — UI Improvements

#### [MODIFY] [cashbox/index.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/cashbox/index.blade.php)

- Ensure the "Usuario" column in the active session transactions table prominently displays the user name with consistent styling.
- Add a "Ver Historial" button/link in the header actions area so users can easily navigate to closed sessions.

#### [MODIFY] [CashBoxController.php — exportExcel()](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/CashBox/Controllers/CashBoxController.php)

- Verify and ensure the existing Excel export already renders the correct `$tx->user->name` for each transaction (it does at line 256: `$tx->user->name ?? 'Usuario'`).
- Add actual/closing balance and difference to the summary section of the Excel export for closed sessions.

---

## Validation Rules

### History Filtering
| Parameter | Rules |
|-----------|-------|
| `date_from` | `nullable\|date` |
| `date_to` | `nullable\|date\|after_or_equal:date_from` |

No other new validation rules are needed since all other endpoints already exist.

---

## Verification Plan

### Automated Tests

#### [NEW] [tests/Feature/CashBoxBalanceFixTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/tests/Feature/CashBoxBalanceFixTest.php)

- **test_opening_balance_not_doubled**: Open box with $100 → assert `expected_closing_balance == 100` (not 200).
- **test_balance_after_inflow_and_outflow**: Open $100, add inflow $2, add outflow $2 → assert `expected_closing_balance == 100`.
- **test_close_with_correct_expected_balance**: Open $100, inflow $50, outflow $20 → expected = $130. Close with $130 → assert `difference == 0`.
- **test_close_with_discrepancy**: Open $100 → close with $95 → assert `difference == -5`.

#### [NEW] [tests/Feature/CashBoxHistoryTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/tests/Feature/CashBoxHistoryTest.php)

- **test_history_shows_only_closed_sessions**: Create an open and a closed session → GET `/cashbox/history` → assert only the closed session appears.
- **test_history_filters_by_date_range**: Create sessions on different dates → filter by range → assert correct results.
- **test_history_show_displays_session_detail**: Create and close a session with transactions → GET `/cashbox/history/{id}` → assert transactions are listed.
- **test_transaction_shows_user_attribution**: Create transactions with different users → assert each transaction displays the correct user name.

#### Test Suite Command
```bash
php artisan test --filter=CashBoxBalanceFix
php artisan test --filter=CashBoxHistory
php artisan test
```

### Manual Verification

1. Open a cash box with $100 → Verify expected balance shows $100 (not $200).
2. Add an inflow of $2 → Verify expected balance shows $102.
3. Add an outflow of $2 → Verify expected balance shows $100.
4. Close the session with $100 → Verify difference is $0.
5. Navigate to `/cashbox/history` → Verify the closed session appears in the list with correct totals.
6. Click on the session → Verify transactions display with user names.
7. Filter by today's date → Verify session appears.
8. Export Excel → Verify user names appear for each transaction.
9. Have a second user make a transaction → Verify attribution shows the correct user.
