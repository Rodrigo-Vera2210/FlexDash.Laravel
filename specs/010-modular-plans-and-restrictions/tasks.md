# Tasks: Modular Plans and Subscription Customizations

## Phase 1: Database Setup and Seeding
- [x] T001 Create migration to create the `plans` table and add override fields (`active_modules`, `max_monthly_transactions`, `max_admins`, `max_sellers`) to the `companies` table.
- [x] T002 Create a migration/seeder to pre-populate default plans: `basic`, `standard`, and `premium`.
- [x] T003 Run migrations and verify SQLite table schemas.

---

## Phase 2: Models and Relationships
- [x] T004 Create `app/Models/Plan.php` model.
- [x] T005 Update `Company.php` model with relationships, cast for `active_modules` as array, helper accessors to fallback to plan settings, and `hasModuleAccess()` helper method.
- [x] T006 Update `SellerService::checkLimitReached()` to retrieve seller limit from `company->max_sellers` dynamically instead of checking hardcoded plan strings.

---

## Phase 3: Module Restrictions Middleware and Route Mappings
- [x] T007 Create `EnsureModuleAccess.php` middleware to map route prefixes/parameters to specific module names and verify access.
- [x] T008 Register `auth.module` middleware in the HTTP bootstrap/kernel file.
- [x] T009 Apply `auth.module` middleware to modular route groups (`sales`, `purchases`, `partners`, `cashbox`, `inventory`, `products`, `sellers`, `settings`) in `routes/web.php`.
- [x] T010 Resolve edge-case mappings for `partners` based on type query (cliente -> module `clientes`, proveedor -> module `proveedores`).

---

## Phase 4: Transaction Limits Check in Sale/Purchase Controllers
- [x] T011 Update `SaleController::store()` to count monthly transactions and block storage if `max_monthly_transactions` is reached.
- [x] T012 Update `PurchaseController::store()` to do the same check.
- [x] T013 Verify that validation exceptions are thrown when the limits are saturated.

---

## Phase 5: Superadmin Auditing Route & Sidebar Fix
- [x] T014 Register `/superadmin/audits` route pointing to `AuditController@index` in `routes/web.php`.
- [x] T015 Update `EnsureJwtAuthenticated` to allow `superadmin` users to pass to `/superadmin/audits` and `superadmin.audits` without redirecting.
- [x] T016 Correct `layouts/app.blade.php` sidebar links for `superadmin` to point to `route('superadmin.audits')`.
- [x] T017 Update `resources/views/layouts/app.blade.php` for company users to hide sidebar links if their company lacks access to a specific module.
- [x] T018 Update `resources/views/audit/index.blade.php` to format custom subscription events beautifully with styling.

---

## Phase 6: Superadmin Plan CRUD and Override UI
- [x] T019 Implement plan management routes `/superadmin/plans` (index, create, store, edit, update, destroy) in `routes/web.php`.
- [x] T020 Implement CRUD logic in `SuperAdminController` and `SuperAdminService`.
- [x] T021 Create Blade templates for plan management: `superadmin/plans/index.blade.php` and `superadmin/plans/edit.blade.php`.
- [x] T022 Update `/superadmin/companies/{company}` details page with a form allowing the superadmin to customize limits and toggle modules for the company.
- [x] T023 Implement save/update logic for company limits and modules in `SuperAdminController@updateCustomLimits`.

---

## Phase 7: Verification and Polish
- [x] T024 Write feature tests in `tests/Feature/PlanManagementTest.php` for plan CRUD.
- [x] T025 Write feature tests in `tests/Feature/SubscriptionModuleAccessTest.php` to assert module access redirects and monthly transaction limits.
- [x] T026 Run the entire test suite `php artisan test` and verify that all tests pass.
- [x] T027 Conduct manual smoke tests on the UI to ensure navigation works and error alerts show up on screen.

---

## Phase 8: Self-Reactivation UI for Suspended Clients
- [x] T028 Create POST route `/subscription-suspended/payment` and add exclusion in `EnsureJwtAuthenticated` to accept payments when suspended.
- [x] T029 Add `storePaymentSuspended` method to `SubscriptionBillingController` to store receipt uploads, auto-classify them as renewal/upgrade, and update company status to `pending_approval`.
- [x] T030 Redesign `resources/views/subscription/suspended.blade.php` with a double-column layouts integrating plan cards, billing upload fields, destination banks, and status messages.
- [x] T031 Add automated verification tests to `SubscriptionEnforcementTest.php` for viewing and submitting the self-reactivation form.
- [x] T032 Confirm entire test suite is green (108/108 passed).

---

## Phase 9: Secure Receipts Serving & Superadmin Dashboard Modal
- [x] T033 Create secure GET route `/receipts/{filename}` and add exception in `EnsureJwtAuthenticated` for superadmin and suspended owners.
- [x] T034 Implement `showReceipt()` in `SubscriptionBillingController` checking that the requester is either superadmin or owner of the associated company.
- [x] T035 Redesign receipt preview on `superadmin/dashboard.blade.php` to trigger an AlpineJS lightbox modal instead of opening in a new tab.
- [x] T036 Update payment receipt paths in `dashboard.blade.php`, `company-detail.blade.php`, and `payments.blade.php` to use `route('receipts.show', basename(...))`.
- [x] T037 Write automated verification tests asserting secure image downloads and role restrictions (111/111 passed).
