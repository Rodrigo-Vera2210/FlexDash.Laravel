# Implementation Plan: Multi-Branch Operational Isolation

**Branch**: `021-multi-branch-isolation` | **Date**: 2026-06-28 | **Spec**: [spec.md](file:///c:/Users/RodrigoVera/Documents/Cursos\FlexDash/FlexDash.Laravel/specs/021-multi-branch-isolation/spec.md)

## Summary

We will isolate all operational business transactions (sales, purchases, collections, payments, petty cash cashbox movements, stock transfers, and Kardex movements) by branch. If a company uses a Premium plan, we will offer a branch selector in the header topbar. Selecting a branch will establish the session context (`active_branch_id`), and all queries and new records will be scoped to this branch. Shared entities (products, partners: clients/suppliers) remain global to the company.

## Technical Context

- **Language/Version**: PHP 8.2+
- **Primary Dependencies**: Laravel 12.x framework
- **Storage**: SQLite database (utilizing existing `branch_id` fields on operational tables)
- **Testing**: PHPUnit feature & unit tests inside `tests/Feature/`

## Constitution Check

- **Test-Driven Development**: Create a comprehensive feature test asserting branch data isolation across all operational areas.
- **Backend Architecture**: Place reusable scoping code inside a new Trait `App\Traits\BelongsToBranch` and session switcher logic inside `App\Http\Controllers\BranchSessionController`.
- **Localization**: Interface text displayed in Spanish.

## Project Structure

### Documentation

```text
specs/021-multi-branch-isolation/
├── spec.md              # Feature specification
├── plan.md              # This file
└── tasks.md             # Task list checklist
```

### Source Code

```text
app/Traits/
└── BelongsToBranch.php          # Reusable Eloquent trait to auto-scope by session active branch

app/Http/
├── Controllers/
│   └── BranchSessionController.php # Manages active branch session updates
└── Middleware/
    └── InitializeActiveBranch.php # Ensures session active_branch_id is populated

resources/views/layouts/
└── app.blade.php                # Topbar header selector inclusion
```

## Scoping Implementation Details

We will apply the `BelongsToBranch` trait to the following models:
- `App\Modules\Sale\Models\Sale`
- `App\Modules\Purchase\Models\Purchase`
- `App\Modules\CashBox\Models\CashBox`
- `App\Models\Payment`
- `App\Modules\Inventory\Models\InventoryMovement`
- `App\Modules\Inventory\Models\StockTransfer`

The Trait will enforce:
1. **Query Scoping**: Restricts query results to records matching the active branch ID.
2. **Auto-Assignment**: Populates `branch_id` on model creation.
