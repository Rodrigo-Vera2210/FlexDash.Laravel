# Tasks: Inventory & Stock Transfers

**Input**: Design documents from `/specs/020-inventory-management/`

**Prerequisites**: plan.md, spec.md

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel
- **[Story]**: US1 (Inventory View), US2 (Stock Transfer), US3 (Plan restriction)

---

## Phase 1: Setup & Foundational

**Purpose**: Set up migrations, model structures, and base routes.

- [ ] T001 [P] Create migrations for `stock_transfers` and `stock_transfer_details` tables in `database/migrations/`
- [ ] T002 [P] Create `StockTransfer` model in `app/Modules/Inventory/Models/StockTransfer.php`
- [ ] T003 [P] Create `StockTransferDetail` model in `app/Modules/Inventory/Models/StockTransferDetail.php`
- [ ] T004 Create `StockTransferService` contract and implementation in `app/Modules/Inventory/Services/StockTransferService.php`
- [ ] T005 Register module routes in `routes/web.php`

---

## Phase 2: User Story 1 - Branch Stock Inventory View (Priority: P1)

**Goal**: Allow users to see the consolidated list of products and their stock by branch.

- [ ] T006 [P] [US1] Create test suite `app/Modules/Inventory/Tests/Feature/InventoryTransferTest.php` asserting stock lists.
- [ ] T007 [US1] Implement `InventoryStockController` in `app/Modules/Inventory/Controllers/InventoryStockController.php` (action `stockIndex`).
- [ ] T008 [US1] Design stock list view in `app/Modules/Inventory/Views/stock.blade.php`. Include branch filter dropdown.
- [ ] T009 [US1] Add "Inventario" link button or sub-navigation to the sidebar menu in `resources/views/layouts/app.blade.php`.

---

## Phase 3: User Story 2 - Stock Transfer (Priority: P2)

**Goal**: Allow stock transfers between branches with atomic transactions and Kardex entries.

- [ ] T010 [P] [US2] Add test cases to `InventoryTransferTest.php` asserting transfer successes and validation (same branch, insufficient stock).
- [ ] T011 [US2] Implement `StoreStockTransferRequest` in `app/Modules/Inventory/Requests/StoreStockTransferRequest.php`.
- [ ] T012 [US2] Implement `StockTransferController` in `app/Modules/Inventory/Controllers/StockTransferController.php` (actions `index`, `create`, `store`).
- [ ] T013 [US2] Design transfer lists and creation forms in `app/Modules/Inventory/Views/transfers/index.blade.php` and `create.blade.php`.
- [ ] T014 [US2] Enforce business logic in `StockTransferService`: execute database transaction, subtract stock from origin, add to destination, write Kardex movement logs (`egreso_traslado` and `ingreso_traslado`).

---

## Phase 4: User Story 3 - Plan Restriction (Priority: P3)

**Goal**: Restrict the transfer options to companies whose subscription plan allows multi-branch operations.

- [ ] T015 [P] [US3] Add test cases to `InventoryTransferTest.php` asserting 403 response on transfer routes for basic/single-branch plan companies.
- [ ] T016 [US3] Implement plan check logic (e.g. check if `company->branches()->count() > 1` is allowed or plan multi-branch flag) inside a middleware or controller guard.
- [ ] T017 [US3] Toggle visibility of the "Traslados" button on the inventory pages based on plan allowances.

---

## Phase 5: Polish & Verification

- [ ] T018 Run feature tests and verify all stock transfers execute safely.
- [ ] T019 Translate all labels and actions to Spanish.
