# Registration Module

> **Full spec**: [`specs/001-multi-enterprise-registration/spec.md`](/specs/001-multi-enterprise-registration/spec.md)

## Overview

The `Registration` module implements a **5-step multi-enterprise registration wizard** with OTP-based email verification. It supports two company types:

- **Legal Entity** — a formal company with a tax ID and a `company_representative` admin user.
- **Natural Person** — a sole proprietor whose personal details are shared across both the `Company` and `User` records.

The wizard collects registration data across five steps (type selection → account & contact → entity details → review & submit → email OTP verification), persists it in a single database transaction, and activates the account only after the user confirms their email with a time-limited OTP code.

This module is self-contained and follows the FlexDash layered architecture: controllers orchestrate only, all business rules live in Services, database access goes through Repositories, and contracts/interfaces ensure loose coupling.

---

## Directory Structure

```
app/Modules/Registration/
├── Contracts/                      # Service interfaces (loose coupling)
│   ├── RegistrationServiceInterface.php
│   └── EmailVerificationServiceInterface.php
│
├── Controllers/                    # Thin HTTP orchestration layer
│   └── RegistrationController.php
│
├── Models/                         # Eloquent models owned by this module
│   ├── Company.php
│   └── EmailVerification.php
│
├── Notifications/                  # Laravel notification classes
│   └── EmailOtpNotification.php    # Sends the 6-digit OTP via email
│
├── Repositories/                   # Data access layer (no raw queries in services)
│   ├── CompanyRepository.php
│   ├── UserRepository.php
│   └── EmailVerificationRepository.php
│
├── Requests/                       # Form request validation (one per wizard step)
│   ├── SelectCompanyTypeRequest.php
│   ├── RegistrationAccountRequest.php
│   ├── RegistrationEntityRequest.php
│   ├── ReviewRegistrationRequest.php
│   └── VerifyOtpRequest.php
│
├── Resources/
│   └── views/registration/         # Blade views (wizard shell + per-step partials)
│       ├── wizard.blade.php
│       ├── steps/
│       │   ├── type-selection.blade.php
│       │   ├── account-contact.blade.php
│       │   ├── entity-details.blade.php
│       │   ├── natural-person-details.blade.php
│       │   ├── review-submit.blade.php
│       │   └── verify-otp.blade.php
│       └── partials/
│           ├── progress-bar.blade.php
│           └── brand-header.blade.php
│
├── Services/                       # Business logic / application layer
│   ├── RegistrationService.php     # Wizard state, DB transaction, OTP trigger
│   └── EmailVerificationService.php# OTP generation, validation, resend throttle
│
├── Tests/                          # All module tests (co-located with module)
│   ├── Unit/
│   └── Feature/
│
└── RegistrationServiceProvider.php # Bootstraps views, routes, and IoC bindings
```

---

## Quickstart

### 1. Run migrations

The migration files reside in the standard `database/migrations/` directory. Run them the normal Laravel way:

```bash
php artisan migrate
```

To roll back only the registration migrations:

```bash
php artisan migrate:rollback --step=2
```

### 2. How routes are loaded

Routes are defined in `routes/registration.php` and auto-loaded by `RegistrationServiceProvider` via `loadRoutesFrom()`. No manual import in `routes/web.php` is required once the provider is registered.

Named routes exposed by the module:

| Name                       | Method | URI                        |
|----------------------------|--------|----------------------------|
| `registration.type`        | GET    | `/register/type`           |
| `registration.account`     | POST   | `/register/account`        |
| `registration.entity`      | POST   | `/register/entity`         |
| `registration.review`      | POST   | `/register/review`         |
| `registration.verify-otp`  | POST   | `/register/verify-otp`     |
| `registration.resend-otp`  | POST   | `/register/resend-otp`     |

### 3. Run module tests

Run all tests (unit + feature) for this module:

```bash
# Using Pest
./vendor/bin/pest tests/ --filter Registration

# Using PHPUnit
./vendor/bin/phpunit --filter Registration
```

Run only unit tests:

```bash
./vendor/bin/pest app/Modules/Registration/Tests/Unit
```

Run only feature tests:

```bash
./vendor/bin/pest app/Modules/Registration/Tests/Feature
```

> **TDD note**: Per the project constitution, write failing tests first, get them reviewed, then implement. The tasks marked `[P]` in `tasks.md` include tests that should be written before the corresponding implementation tasks.

---

## Key Contracts and How to Extend Them

### `RegistrationServiceInterface`

```
app/Modules/Registration/Contracts/RegistrationServiceInterface.php
```

Bound to `RegistrationService` in `RegistrationServiceProvider::register()`. Covers the core wizard flow: merging wizard step data, creating the `Company` + `User` + `EmailVerification` records in a single DB transaction, and triggering the OTP notification.

**To extend or swap the implementation:**

1. Create a new class that implements `RegistrationServiceInterface`.
2. Update the binding in `RegistrationServiceProvider::register()`:

```php
$this->app->bind(
    RegistrationServiceInterface::class,
    YourCustomRegistrationService::class,
);
```

### `EmailVerificationServiceInterface`

```
app/Modules/Registration/Contracts/EmailVerificationServiceInterface.php
```

Bound to `EmailVerificationService`. Responsible for OTP generation, expiry management, validation, resend throttling, and marking `email_verified_at` on the `User` record after successful verification.

**To extend or swap the implementation:**

1. Create a new class that implements `EmailVerificationServiceInterface`.
2. Update the binding in `RegistrationServiceProvider::register()`:

```php
$this->app->bind(
    EmailVerificationServiceInterface::class,
    YourCustomEmailVerificationService::class,
);
```

### Adding a new wizard step

1. Add a new `FormRequest` class in `Requests/`.
2. Add a new controller method in `RegistrationController` that uses the request and delegates to a service method.
3. Register the new route in `routes/registration.php`.
4. Add a Blade view in `Resources/views/registration/steps/`.
5. Write feature tests in `Tests/Feature/` before implementing.

---

## Related Documentation

- Full feature spec: [`specs/001-multi-enterprise-registration/spec.md`](/specs/001-multi-enterprise-registration/spec.md)
- Implementation plan: [`specs/001-multi-enterprise-registration/plan.md`](/specs/001-multi-enterprise-registration/plan.md)
- Task list: [`specs/001-multi-enterprise-registration/tasks.md`](/specs/001-multi-enterprise-registration/tasks.md)
- Project constitution: [`.specify/memory/constitution.md`](/.specify/memory/constitution.md)
