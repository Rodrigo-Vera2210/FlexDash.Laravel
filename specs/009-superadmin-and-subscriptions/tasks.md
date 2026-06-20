# Tasks: Superadministrator Portal & Subscription Management (Spec 009)

**Input**: Design documents from `/specs/009-superadmin-and-subscriptions/`

**Prerequisites**: `plan.md`, `spec.md`

---

## Phase 1: Database Setup, Tenant Scoping & Seeders

- [ ] T001 Create migration to add `subscription_plan` (string, default 'basic'), `subscription_status` (string, default 'pending_approval'), and `subscription_expires_at` (timestamp, nullable) columns to the `companies` table.
- [ ] T002 Create migration for `subscription_payments` table to log all billing uploads, plans, bank details, statuses, and types ('signup', 'upgrade', 'renewal').
- [ ] T003 Create migration to add nullable `company_id` columns to all system tables (categories, taxes, payment_methods, partners, products, inventory_movements, sales, sale_details, purchases, purchase_details, payments, audit_logs, cash_boxes).
- [ ] T004 Create `BelongsToCompany` trait in `app/Traits/BelongsToCompany.php` that implements the global query scope and automatic creation hooks. Apply it to all tenant models.
- [ ] T005 Create migration/seeder to create the default Superadmin user account (`superadmin@flexdash.com`, role `superadmin`, company_id `null`).
- [ ] T006 Run migrations and verify database schema updates.

---

## Phase 2: Registration Wizard Payment Step

- [ ] T007 Modify `RegistrationController` and views to insert the Step 4 "Planes y Pago" screen (input bank of origin, select destination bank, upload screenshot image).
- [ ] T008 Update `RegistrationService` to store the receipt upload in `subscription_payments` as type `signup` and create the Company with `subscription_status = 'pending_approval'` and user with `status = 'pending_activation'`.

---

## Phase 3: Middleware & Access Suspensions (TDD)

- [ ] T009 Write failing feature tests in `tests/Feature/SubscriptionEnforcementTest.php` to assert:
  - Users of companies with status `pending_approval`, `inactive`, or `suspended` (or whose `subscription_expires_at` has passed) are redirected to `/subscription-suspended` on standard requests.
  - JSON requests from blocked companies return 403 Forbidden.
- [ ] T010 Implement company subscription status and expiration checks inside `EnsureJwtAuthenticated` middleware.
- [ ] T011 Create the `EnsureSuperAdmin` middleware and register it.
- [ ] T012 Create the `RestrictSellerAccess` middleware to block `vendedor` accounts from accessing dashboard, purchases, products, suppliers list, cashbox, audit logs, and catalog settings. Register it as `auth.admin_only`.
- [ ] T013 Register `/subscription-suspended` route and create the view in `resources/views/subscription/suspended.blade.php`.
- [ ] T014 Verify that all auth/subscription restriction tests pass.

---

## Phase 4: Seller Management (TDD)

- [ ] T015 Write failing feature tests in `app/Modules/Seller/Tests/Feature/SellerManagementTest.php` to assert list, create, and plan limits enforcement.
- [ ] T016 Create `SellerService` class inside `app/Modules/Seller/Services/` to validate limits and create accounts with role `vendedor` and status `active`.
- [ ] T017 Create `SellerController` under `app/Modules/Seller/Controllers/` and register resource routes.
- [ ] T018 Implement Blade templates for sellers list (`index.blade.php`) and creation (`create.blade.php`).
- [ ] T019 Verify that all Seller Management tests pass.

---

## Phase 5: Company Admin Plan Changes & Renewals (TDD)

- [ ] T020 Write failing feature tests in `tests/Feature/CompanySubscriptionBillingTest.php` to assert:
  - Company admins can view their subscription settings, active plan, and expiration date.
  - Company admins can request renewals (minimum 1 month term extension) or plan changes (upgrades/downgrades) by uploading bank transfer details and screenshots.
  - Upgrade/renewal requests create pending payment records and do not take effect until approved.
- [ ] T021 Register billing upgrade and renewal endpoints in `routes/web.php`.
- [ ] T022 Create the company subscription settings view at `resources/views/settings/subscription.blade.php` with upgrade form and payment uploads.
- [ ] T023 Implement renewal/upgrade registration logic in `RegistrationService` or a specialized billing controller.
- [ ] T024 Verify that all plan change and renewal request tests pass.

---

## Phase 6: Superadministrator Panel (TDD)

- [ ] T025 Write failing feature tests in `app/Modules/SuperAdmin/Tests/Feature/SuperAdminDashboardTest.php` to assert company activation, rejection, auditing receipt images, and approving pending upgrades or renewals.
- [ ] T026 Create `SuperAdminService` under `app/Modules/SuperAdmin/Services/` and `SuperAdminController`.
- [ ] T027 Register `/superadmin/*` routes guarded by `auth.superadmin`.
- [ ] T028 Implement the superadmin portal view (`superadmin/dashboard.blade.php`) containing pending subscriptions/upgrades/renewals with receipt preview modal, and registered companies table.
- [ ] T029 Verify that all Superadmin tests pass.

---

## Phase 7: Multi-Tenant Data Isolation (TDD)

- [ ] T030 Write failing integration tests in `tests/Feature/TenantIsolationTest.php` to verify database operations isolate records by `company_id`.
- [ ] T031 Assert that the `BelongsToCompany` trait successfully intercepts all model operations and scopes queries.

---

## Phase 8: UI Navigation, Alerts & Polish

- [ ] T032 Update the sidebar navigation layout in `resources/views/layouts/app.blade.php` to conditionally render menus based on user role.
- [ ] T033 Implement a top warning banner component in `layouts/app.blade.php` that displays if `subscription_expires_at` is 5 days or less in the future.
- [ ] T034 Perform manual verification of the signup wizard, admin plan upgrade requests, monthly renewals, superadmin receipt modal audit, and company activations.
- [ ] T035 Run all system tests using `php artisan test`.
