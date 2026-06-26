# Implementation Plan: Multilocal (Múltiples Sucursales), Inventario por Local y Configuración de Duración de Suscripción con Descuentos (Spec 017)

**Branch**: `017-multi-branch-and-plans` | **Date**: 2026-06-24 | **Spec**: `/specs/017-multi-branch-and-plans/spec.md`

## Summary

This plan outlines the database migrations, backend models, controller revisions, service modifications, and premium UI components required to implement multiple locales (branches) per company with distinct stock tracking, updated subscription plan limits for platform admins, and a dynamic subscription duration and pricing cards interface for customer registration.

---

## Technical Context

- **Language/Version**: PHP 8.2+ with Laravel 12
- **Primary Dependencies**: Laravel Framework, Tailwind CSS, AlpineJS, PhpSpreadsheet, Font Awesome
- **Storage**: SQLite (primary)
- **Testing**: PHPUnit + Pest
- **Constraints**: Multi-tenant via `BelongsToCompany` trait; JWT authentication; `auth.module:settings` and other module-based middleware

---

## Proposed Changes

### Component 1: Database Migrations (DAL)

Create four new database migrations in `database/migrations/`:

#### 1. `[NEW]` `database/migrations/2026_06_24_100001_create_branches_and_branch_product_tables.php`
Define schema for locales and product stock per branch:
- `branches`: `id`, `company_id`, `name`, `address`, `phone`, `establishment_code` (3 chars), `is_active` (boolean, default true), `timestamps`.
- `branch_product`: `id`, `branch_id`, `product_id`, `stock` (decimal: 14, 4, default 0), unique `[branch_id, product_id]`.

#### 2. `[NEW]` `database/migrations/2026_06_24_100002_add_branch_id_to_operational_tables.php`
Add `branch_id` foreign key columns to operational tables:
- `users`: `branch_id` (nullable, cascade/restrict)
- `sales`: `branch_id` (nullable, cascade/restrict)
- `purchases`: `branch_id` (nullable, cascade/restrict)
- `cash_boxes`: `branch_id` (nullable, cascade/restrict)
- `inventory_movements`: `branch_id` (nullable, cascade/restrict)

#### 3. `[NEW]` `database/migrations/2026_06_24_100003_add_duration_and_pricing_fields_to_subscription_payments.php`
Add pricing and duration fields to `subscription_payments` table:
- `duration_months` (integer, default 1)
- `discount_percentage` (decimal: 5, 2, default 0.00)
- `amount` (decimal: 10, 2, default 0.00)

#### 4. `[NEW]` `database/migrations/2026_06_24_100004_add_max_branches_to_plans_and_companies.php`
Add plan-based limitations and update seeder values:
- `plans`: `max_branches` (integer, default 1)
- `companies`: `max_branches` (integer, default 1)
- Seed values: Basic (1), Standard (3), Premium (9999).

---

### Component 2: Models & Core Logistics (Domain/DAL)

#### 5. `[NEW]` [Branch.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Branch/Models/Branch.php)
Implement `Branch` model:
- Implements `BelongsToCompany` trait.
- Relations: `belongsTo(Company)`, `hasMany(User)`, `hasMany(Sale)`.

#### 6. `[MODIFY]` [Company.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Registration/Models/Company.php)
Add relations and limits accessor:
- Relation: `branches() -> hasMany(Branch)`.
- Accessor: `getMaxBranchesAttribute($value)` defaulting to plan limit or override.

#### 7. `[MODIFY]` [Product.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Product/Models/Product.php)
Modify product class:
- Relation: `branches() -> belongsToMany(Branch)->withPivot('stock')`.
- Modify `stock` attribute (or create a custom getter/method) to return the total stock sum of all branches, or the current user's branch stock.

---

### Component 3: Inventory Logic per Branch (Application)

#### 8. `[MODIFY]` [InventoryService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Services/InventoryService.php)
Migrate the legacy global `InventoryService` into `app/Modules/Inventory/Services/InventoryService.php` as per the Constitution:
- Add a mandatory or fallback `branch_id` parameter to `entry()`, `exit()`, `adjust()`, and `return()` methods.
- Update `branch_product` pivot table record instead of writing to `products.stock` column directly.
- Recalculate average weighted cost (`cost` on products table remains global or company-level) but update branch stock.
- Update the global product `stock` column cache (if preserved) as the sum of all branches.

---

### Component 4: Superadmin Plans Management (Presentation/Application)

#### 9. `[MODIFY]` [SuperAdminController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/SuperAdmin/Controllers/SuperAdminController.php)
- Update `plansStore` validation to include:
  - `max_branches => ['required', 'integer', 'min:1']`
  - `monthly_invoice_limit => ['required', 'integer', 'min:0']`
- Update `plansUpdate` validation to include these two parameters.

#### 10. `[MODIFY]` [plans/edit.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/superadmin/plans/edit.blade.php)
- Add two input fields inside the plan form:
  - "Límite de Locales" (`max_branches`)
  - "Límite de Facturas Mensuales" (`monthly_invoice_limit`)

---

### Component 5: Signup dynamic pricing & period calculator (Presentation/Application)

#### 11. `[MODIFY]` [billing.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/registration/steps/billing.blade.php)
- Implement a modern subscription selector following the reference layout.
- Include a select dropdown or button bar for period selector: **1, 3, 6, 12, 24, 36 meses**.
- Include three beautiful CSS plan cards: Basic, Standard, Premium. Highlight Standard card as "MÁS VENDIDO".
- Include JavaScript logic to dynamically:
  - Calculate discounted price based on selected months.
  - Apply discounts: 3 months = 5%, 6 months = 10%, 12 months = 15%, 24 months = 20%, 36 months = 25%.
  - Render original price crossed out, new price per month, and total price description.
  - Update hidden inputs `subscription_duration_months`, `subscription_amount`, and `subscription_discount_percentage`.

#### 12. `[MODIFY]` [RegistrationController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Registration/Controllers/RegistrationController.php)
- Validate step 4 inputs: `subscription_plan`, `subscription_duration_months`, `bank_origin`, `account_destination`, `payment_receipt`.

#### 13. `[MODIFY]` [RegistrationService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Registration/Services/RegistrationService.php)
- Save subscription payment logs with duration, discount, and actual paid amount.
- Update `buildCompanyData()` to default the registration subscription.

#### 14. `[MODIFY]` [SuperAdminService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/SuperAdmin/Services/SuperAdminService.php)
- Update `approveCompany()` to set company subscription expiration:
  `$company->subscription_expires_at = now()->addMonths($payment->duration_months ?? 1);`

---

## Verification Plan

### Automated Tests

#### `[NEW]` [tests/Feature/BranchManagementTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/tests/Feature/BranchManagementTest.php)
- **test_cannot_create_branches_beyond_plan_limit**: Verify standard plan blocks 4th branch registration.
- **test_branch_isolation_in_transactions**: Salesperson at branch A cannot view or post sales in branch B.

#### `[NEW]` [tests/Feature/BranchInventoryTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/tests/Feature/BranchInventoryTest.php)
- **test_stock_decrements_only_at_assigned_branch**: Record sale in branch A, verify stock in branch A is decremented while branch B remains untouched.
- **test_general_inventory_shows_branch_columns**: GET `/products` and verify HTML response contains stock cells for each active branch and a correct total.

#### `[NEW]` [tests/Feature/SubscriptionPeriodTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/tests/Feature/SubscriptionPeriodTest.php)
- **test_signup_stores_duration_and_applies_discount**: Simulate signup with 12 months, verify payment is created with 15% discount and correct amount.
- **test_approval_sets_correct_expiration**: Approve 12-month renewal, verify company's expiration is extended by 12 months.

---

### Manual Verification
1. Access superadmin dashboard, edit Standard plan, change "Límite de Locales" to 2, and "Límite de Facturas" to 200. Save and verify.
2. Register a new user, go to step 4, toggle selector from 1 month to 36 months, verify Standard plan displays 25% discount and total price adjusts dynamically.
3. Complete registration, log in as company admin, navigate to Settings / Locales, create a branch with establishment code "002".
4. Eent stock in "Matriz" (001) for Product X. Entry stock in "Local Norte" (002) for Product X. Verify catalog lists Product X with matrix column stocks and correct total stock.
5. Create a sale at Local Norte, approve it, verify Local Norte stock is decremented and Matriz stock is untouched.
