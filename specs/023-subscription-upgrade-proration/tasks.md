# Tasks: Subscription Upgrade Proration and Superadmin Bank Accounts Management

**Input**: Design documents from `/specs/023-subscription-upgrade-proration/`

---

## Phase 1: Setup & Database (Shared Infrastructure)

- [ ] T001 Create migration `create_bank_accounts_table` under `database/migrations/`
- [ ] T002 Implement `BankAccount` model in `app/Modules/SuperAdmin/Models/BankAccount.php`

---

## Phase 2: User Story 1 - Subscription Upgrade Proration (P1)

- [ ] T003 Update `SubscriptionBillingController.php` to calculate unused credit in `index()`
- [ ] T004 Redesign `settings/subscription.blade.php` with AlpineJS billing-style selector and dynamic proration calculation breakdown
- [ ] T005 Update `storePayment()` and `storePaymentSuspended()` inside `SubscriptionBillingController.php` to validate and persist prorated transaction amount

---

## Phase 3: User Story 2 - Superadmin Bank Accounts CRUD (P2)

- [ ] T006 Create `BankAccountController.php` in `app/Modules/SuperAdmin/Controllers/BankAccountController.php` supporting file uploads for the bank logo
- [ ] T007 Register bank accounts resource routes in `routes/web.php` under superadmin middleware
- [ ] T008 Implement CRUD blade views under `resources/views/superadmin/bank-accounts/` including logo image upload form field
- [ ] T009 Dynamically load bank accounts and render logo images in registration step `billing.blade.php`, `suspended.blade.php`, and `subscription.blade.php`

---

## Phase 4: Verification & Polish

- [ ] T010 Create `SubscriptionUpgradeProrationTest.php` in `tests/Feature/SubscriptionUpgradeProrationTest.php`
- [ ] T011 Run all PHPUnit test suites and verify UI aesthetics
