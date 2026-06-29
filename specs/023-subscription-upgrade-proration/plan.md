# Implementation Plan: Subscription Upgrade Proration and Superadmin Bank Accounts Management

**Branch**: `023-subscription-upgrade-proration` | **Date**: 2026-06-29 | **Spec**: [spec.md](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/specs/023-subscription-upgrade-proration/spec.md)

## Summary

The goal of this feature is to implement:
1. Dynamic unused-credit proration calculation when upgrading/changing subscription plans.
2. An interactive pricing layout on `/settings/subscription` identical to `/registration/billing`.
3. A complete bank accounts CRUD panel for Superadmins, loading bank details dynamically in registration and subscription forms.

## Technical Context

- **Proration Discount Formula**:
  `unused_credit = (last_approved_payment->amount / last_approved_payment->duration_months / 30) * remaining_days`
  `amount_to_pay = max(0, new_total_amount - unused_credit)`
- **Bank Accounts DB Schema**:
  `bank_accounts` table: `id`, `bank_name`, `account_type`, `account_number`, `beneficiary_name`, `beneficiary_ruc`, `logo_path`, `is_active`.
- **Primary Dependencies**: Laravel 12.x, Tailwind CSS, AlpineJS.

## Proposed Changes

### Database Migrations
- Create migration `create_bank_accounts_table` under `database/migrations/`.

### Models
- Create [BankAccount.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/SuperAdmin/Models/BankAccount.php).

### Controllers
- Create [BankAccountController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/SuperAdmin/Controllers/BankAccountController.php) for managing bank accounts CRUD.
- Modify [SubscriptionBillingController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Registration/Controllers/SubscriptionBillingController.php):
  - In `index()`: Calculate prorated unused credit and load active bank accounts.
  - In `storePayment()` / `storePaymentSuspended()`: Recalculate and validate prorated amount on the backend.

### Views
- Redesign [subscription.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/settings/subscription.blade.php) with the period selector and plan cards. Add AlpineJS calculator for real-time proration display.
- Modify [billing.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/registration/steps/billing.blade.php) and [suspended.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/subscription/suspended.blade.php) to pull bank details dynamically from database.
- Create CRUD views:
  - `resources/views/superadmin/bank-accounts/index.blade.php`
  - `resources/views/superadmin/bank-accounts/create.blade.php`
  - `resources/views/superadmin/bank-accounts/edit.blade.php`

## Verification Plan

### Automated Tests
- Create `SubscriptionUpgradeProrationTest.php` under `tests/Feature/`.
