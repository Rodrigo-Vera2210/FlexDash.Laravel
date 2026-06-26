# Tasks: 017 Multilocal (Múltiples Sucursales), Inventario por Local y Configuración de Duración de Suscripción con Descuentos

**Input**: Design documents from `/specs/017-multi-branch-and-plans/`

**Prerequisites**: `plan.md`, `spec.md`

---

## Phase 1: Database Schema & Migrations

- [ ] T001 Create migration `create_branches_and_branch_product_tables`: `branches` table (`id`, `company_id` FK, `name`, `address` nullable, `phone` nullable, `establishment_code` 3 chars, `is_active` boolean default true, timestamps) + `branch_product` pivot (`id`, `branch_id` FK, `product_id` FK, `stock` decimal(14,4) default 0, unique constraint on `[branch_id, product_id]`)
- [ ] T002 Create migration `add_branch_id_to_operational_tables`: add nullable `branch_id` FK to `users`, `sales`, `purchases`, `cash_boxes`, `inventory_movements`
- [ ] T003 Create migration `add_duration_and_pricing_fields_to_subscription_payments`: add `duration_months` (integer default 1), `discount_percentage` (decimal(5,2) default 0.00), `amount` (decimal(10,2) default 0.00) to `subscription_payments`
- [ ] T004 Create migration `add_max_branches_to_plans_and_companies`: add `max_branches` (integer default 1) to `plans` and `companies` tables
- [ ] T005 Run all migrations and verify schema integrity

**Checkpoint**: All new tables and columns exist. `branch_product` pivot has the unique constraint. Existing data is intact.

---

## Phase 2: Eloquent Models & Relations

- [ ] T006 [P] Create `Branch` model (`app/Modules/Branch/Models/Branch.php`) with `BelongsToCompany` trait, fillable fields, casts, relations: `company()`, `users()`, `sales()`, `products()` (belongsToMany via `branch_product` with pivot `stock`), `inventoryMovements()`, `cashBoxes()`
- [ ] T007 Update `Company` model (`app/Modules/Registration/Models/Company.php`): add `branches()` hasMany relation, add `max_branches` to fillable/casts
- [ ] T008 Update `Product` model (`app/Modules/Product/Models/Product.php`): add `branches()` belongsToMany relation via `branch_product` withPivot `stock`, add `getTotalStockAttribute()` accessor that sums stock across all branches
- [ ] T009 Update `User` model: add `branch_id` to fillable, add `branch()` belongsTo relation
- [ ] T010 Update `Sale` model: add `branch_id` to fillable, add `branch()` belongsTo relation
- [ ] T011 Update `Purchase` model: add `branch_id` to fillable, add `branch()` belongsTo relation
- [ ] T012 Update `CashBox` model: add `branch_id` to fillable, add `branch()` belongsTo relation
- [ ] T013 Update `InventoryMovement` model: add `branch_id` to fillable, add `branch()` belongsTo relation
- [ ] T014 Update `Plan` model: add `max_branches` to fillable/casts

**Checkpoint**: All models have correct relations. `Branch` belongs to company. Products have branch-level stock via pivot.

---

## Phase 3: Branch CRUD — User Story 1 (P1)

**Goal**: Full management of multiple branches/locales per company with plan limit enforcement.

**Independent Test**: Navigate to branch management, create a branch, verify it appears. Attempt to exceed plan limit.

- [ ] T015 Create `BranchController` (`app/Modules/Branch/Controllers/BranchController.php`) with `index`, `create`, `store`, `edit`, `update`, `destroy` actions. Enforce `max_branches` limit on `store()`: count active branches vs company plan limit, abort with validation error if exceeded.
- [ ] T016 Register branch resource routes in `routes/web.php` inside authenticated group with `auth.module:settings` middleware
- [ ] T017 Add "Locales / Sucursales" link to sidebar navigation in `app.blade.php` under settings section with `fa-solid fa-store` icon
- [ ] T018 [P] Create `resources/views/branches/index.blade.php` — table of branches with columns (Nombre, Dirección, Teléfono, Código Establecimiento, Estado, Acciones), status badges, create button
- [ ] T019 [P] Create `resources/views/branches/create.blade.php` — form with name, address, phone, establishment_code (3-digit), is_active toggle
- [ ] T020 [P] Create `resources/views/branches/edit.blade.php` — pre-populated edit form
- [ ] T021 On branch creation, seed `branch_product` entries with stock=0 for all existing products of the company

**Checkpoint**: Branches CRUD is functional. Plan limit is enforced. New branches start with zero stock for all products.

---

## Phase 4: Inventory Per Branch — User Story 2 (P0)

**Goal**: Stock tracked per branch in `branch_product`. Product catalog shows columns per branch + total.

**Independent Test**: Entry stock in Branch A, verify Branch B is unaffected, verify total column is correct.

- [ ] T022 Refactor `InventoryService` (move from `app/Services/InventoryService.php` to `app/Modules/Inventory/Services/InventoryService.php`): add mandatory `branch_id` parameter to `entry()`, `exit()`, `adjust()`, `return()` methods. Update `branch_product` pivot instead of `products.stock` directly. Recalculate global `products.stock` as sum of all branch stocks.
- [ ] T023 Update `InventoryController` to pass `branch_id` from request to `InventoryService` methods. Add branch selector dropdown to inventory entry/exit forms.
- [ ] T024 Update `ProductController::index()` to eager load product branches with pivot stock. Pass active branches collection to the view.
- [ ] T025 [P] Update `resources/views/products/index.blade.php`: render dynamic stock columns for each active branch (name as header) plus a "Stock Total" column showing the sum. Handle companies with a single branch gracefully (show single stock column).
- [ ] T026 When creating a new product, automatically seed `branch_product` entries with stock=0 for all active branches. Update `ProductController::store()` or `ProductService`.
- [ ] T027 Update `SaleService::approve()` to use `branch_id` from the sale when calling `InventoryService::exit()`. Stock decrement only affects the sale's assigned branch.
- [ ] T028 Update `PurchaseService::approve()` to use `branch_id` from the purchase when calling `InventoryService::entry()`. Stock increment only affects the purchase's assigned branch.

**Checkpoint**: Inventory is fully segmented per branch. Product catalog shows per-branch columns. Sales/purchases affect only their branch's stock.

---

## Phase 5: Superadmin Plan Editing — User Story 3 (P1)

**Goal**: Superadmin can set `max_branches` and `monthly_invoice_limit` when creating/editing plans.

**Independent Test**: Edit Standard plan, set max_branches=3 and monthly_invoice_limit=500, save, verify values persisted.

- [ ] T029 Update `SuperAdminController::plansStore()` validation: add `max_branches => ['required', 'integer', 'min:1']` and `monthly_invoice_limit => ['required', 'integer', 'min:0']`
- [ ] T030 Update `SuperAdminController::plansUpdate()` validation: add same rules for `max_branches` and `monthly_invoice_limit`
- [ ] T031 [P] Update `resources/views/superadmin/plans/edit.blade.php`: add input fields for "Límite de Locales" (`max_branches`) and "Límite de Facturas Mensuales" (`monthly_invoice_limit`) with proper labels, placeholders, and existing value binding
- [ ] T032 Update plan seeder/defaults: Basic → max_branches=1, Standard → max_branches=3, Premium → max_branches=9999

**Checkpoint**: Superadmin can fully configure branch and invoice limits per plan.

---

## Phase 6: Signup Dynamic Pricing & Period Calculator — User Story 4 (P1)

**Goal**: Premium registration step 4 with dynamic pricing cards and subscription duration selector with discounts.

**Independent Test**: Go to registration step 4, toggle from 1 month to 12 months, verify Standard card shows 15% discount badge, crossed-out price, and correct total.

- [ ] T033 [P] Redesign `resources/views/registration/steps/billing.blade.php`:
  - Premium card layout (Basic, Standard, Premium) matching reference design with glassmorphism/gradient styling
  - "MÁS VENDIDO" badge on Standard card
  - Period selector buttons/dropdown: 1, 3, 6, 12, 24, 36 meses
  - JavaScript/Alpine logic for dynamic price calculation with discount map (3m=5%, 6m=10%, 12m=15%, 24m=20%, 36m=25%)
  - Crossed-out original price, discounted price per month, discount badge (e.g. "-15%"), total amount text (e.g. "Obtén 12 meses por $601.80")
  - Hidden inputs: `subscription_duration_months`, `subscription_amount`, `subscription_discount_percentage`
- [ ] T034 Update `RegistrationController` step 4 validation: add `subscription_duration_months => ['required', 'in:1,3,6,12,24,36']` rule
- [ ] T035 Update `RegistrationService` to save `duration_months`, `discount_percentage`, and `amount` to `subscription_payments` table during signup
- [ ] T036 Update `SuperAdminService::approveCompany()` to set `subscription_expires_at = now()->addMonths($payment->duration_months ?? 1)` instead of hardcoded 1 month

**Checkpoint**: Registration pricing calculator works dynamically. Discounts apply correctly. Payment records store duration and discount data. Approval extends subscription by selected duration.

---

## Phase 7: Integration Wiring & Branch Assignment (P1)

**Goal**: Wire branch_id into sales, purchases, cash boxes creation flows. Auto-assign default branch on company registration.

- [ ] T037 On company registration approval (`SuperAdminService::approveCompany()`), auto-create a default branch named "Matriz" with establishment_code "001" and assign it to the company admin user
- [ ] T038 Update `SaleController::store()` to assign `branch_id` from the authenticated user's branch or request
- [ ] T039 Update `PurchaseController::store()` to assign `branch_id` from the authenticated user's branch or request
- [ ] T040 Update `CashBoxService::openBox()` to assign `branch_id` from the authenticated user's branch
- [ ] T041 Update user creation/editing in admin panel to optionally assign a `branch_id`

**Checkpoint**: All operational entities correctly reference a branch. Default branch auto-created for new companies.

---

## Phase 8: Automated Tests & Verification (P2)

- [ ] T042 Write `tests/Feature/BranchManagementTest.php`:
  - `test_can_create_branch`: create a branch, verify it exists in DB
  - `test_cannot_create_branches_beyond_plan_limit`: standard plan (max 3), create 3 branches, attempt 4th → blocked
  - `test_branch_product_seeded_on_creation`: create branch, verify all products have a `branch_product` entry with stock=0
- [ ] T043 Write `tests/Feature/BranchInventoryTest.php`:
  - `test_stock_decrements_only_at_assigned_branch`: entry in branch A, sale in branch A, verify branch B unchanged
  - `test_general_inventory_shows_branch_columns`: GET `/products`, verify HTML contains stock cells per branch and correct total
  - `test_total_stock_is_sum_of_all_branches`: verify `getTotalStockAttribute()` returns correct sum
- [ ] T044 Write `tests/Feature/SubscriptionPeriodTest.php`:
  - `test_signup_stores_duration_and_applies_discount`: signup with 12 months, verify payment has 15% discount and correct amount
  - `test_approval_sets_correct_expiration`: approve 12-month payment, verify `subscription_expires_at` is now + 12 months
  - `test_invalid_duration_rejected`: attempt signup with duration=7, verify validation error
- [ ] T045 Run the complete test suite (`php artisan test`) and verify zero failures
- [ ] T046 Run asset build (`npm run build`) and verify client-side compilation succeeds
