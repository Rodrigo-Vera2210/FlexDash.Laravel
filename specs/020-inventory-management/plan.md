# Implementation Plan: Inventory & Stock Transfers

**Branch**: `020-inventory-management` | **Date**: 2026-06-28 | **Spec**: [spec.md](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/specs/020-inventory-management/spec.md)

## Summary

We will build the inventory stock consolidated view by branch and implement the Inter-branch Stock Transfer feature inside the `app/Modules/Inventory` module. The transfer feature will validate that the origin branch has sufficient stock, perform the updates inside a single database transaction, record corresponding Kardex entries, and enforce plan limitations (multibodega access restrictions for basic plans).

## Technical Context

- **Language/Version**: PHP 8.2+
- **Primary Dependencies**: Laravel 12.x framework
- **Storage**: SQLite database (via Laravel migrations for `stock_transfers` and `stock_transfer_details` tables; reusing the existing `branch_product` table for branch stocks)
- **Testing**: PHPUnit feature & unit tests inside `app/Modules/Inventory/Tests/` or `tests/Feature/`

## Constitution Check

- **Test-Driven Development**: Create `InventoryTransferTest` asserting stock transfers, validation failures, and plan restrictions.
- **Backend Architecture**: Place controller, request, and service files inside `app/Modules/Inventory/`.
- **Localization**: Interface text displayed in Spanish (`egreso_traslado`, `ingreso_traslado`, `bajo`, `medio`, `alto`).

## Project Structure

### Documentation

```text
specs/020-inventory-management/
├── spec.md              # Feature specification
├── plan.md              # This file
└── tasks.md             # Task list checklist
```

### Source Code

```text
app/Modules/Inventory/
├── Controllers/
│   ├── InventoryStockController.php # Controls inventory stock viewing
│   └── StockTransferController.php  # Controls transfer flows
├── Services/
│   └── StockTransferService.php     # Business rules (atomic transaction, Kardex logs)
├── Models/
│   ├── StockTransfer.php            # StockTransfer model
│   └── StockTransferDetail.php      # Detail model
├── Requests/
│   └── StoreStockTransferRequest.php # Validation rules for transfer
├── Views/
│   ├── stock.blade.php              # Inventory stock index view
│   ├── transfers/
│   │   ├── index.blade.php          # List of past transfers
│   │   └── create.blade.php         # New transfer form
└── Tests/
    └── Feature/
        └── InventoryTransferTest.php # Test suite
```

## Data Schema & Migrations

We will create two tables to record transfers:

1. **`stock_transfers`**:
   - `id` (bigint, PK)
   - `company_id` (foreign key to `companies`, cascade delete)
   - `origin_branch_id` (foreign key to `branches`, cascade delete)
   - `destination_branch_id` (foreign key to `branches`, cascade delete)
   - `user_id` (foreign key to `users` - performer, cascade delete)
   - `timestamps`

2. **`stock_transfer_details`**:
   - `id` (bigint, PK)
   - `stock_transfer_id` (foreign key to `stock_transfers`, cascade delete)
   - `product_id` (foreign key to `products`, cascade delete)
   - `quantity` (decimal/integer, > 0)
   - `timestamps`

Migrations will reside in `database/migrations/` per constitution.
