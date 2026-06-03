---
feature: Multi-Enterprise Registration Wizard with OTP
---

# Tasks: Multi-Enterprise Registration Wizard with OTP

**Input**: Design documents from /specs/001-multi-enterprise-registration/

## Phase 1: Setup (Shared Infrastructure)

- [ ] T001 Create module skeleton `app/Modules/Registration/` with subfolders: `Controllers`, `Requests`, `Services`, `Repositories`, `Models`, `Contracts`, `Notifications`, `Migrations`, `Resources/views/registration/`, and `Tests/` (create directory and placeholder files)
- [ ] T002 Create route file `routes/registration.php` and register it in `routes/web.php`
- [ ] T003 [P] Add FlexDash brand color tokens to `tailwind.config.js` and `resources/css/app.css` per design tokens
- [ ] T004 [P] Add `app/Modules/Registration/RegistrationServiceProvider.php` and register the provider in `config/app.php` (or module loader)
- [ ] T005 [P] Add README stub `app/Modules/Registration/README.md` describing module layout and quickstart

---

## Phase 2: Foundational (Blocking Prerequisites)

- [ ] T006 Create migration `database/migrations/2026_06_02_000001_create_companies_table.php` for `companies` (fields per data-model.md)
- [ ] T007 Create migration `database/migrations/2026_06_02_000002_create_email_verifications_table.php` for `email_verifications`
- [ ] T008 [P] Create models: `app/Modules/Registration/Models/Company.php` and `app/Modules/Registration/Models/EmailVerification.php`
- [ ] T009 [P] Update `app/Models/User.php` to add `company()` relationship and `status` handling (create a migration if needed)
- [ ] T010 [P] Create repository skeletons: `app/Modules/Registration/Repositories/CompanyRepository.php`, `UserRepository.php`, `EmailVerificationRepository.php`
- [ ] T011 [P] Create contract interfaces: `app/Modules/Registration/Contracts/RegistrationServiceInterface.php` and `EmailVerificationServiceInterface.php`
- [ ] T012 Implement `app/Modules/Registration/Notifications/EmailOtpNotification.php` to send OTP emails via Laravel Mail
- [ ] T013 [P] Add mail testing helpers or configure `tests/TestCase.php` to use `Mail::fake()` for feature tests (tests/TestCase.php)
- [ ] T014 Add migrations to `composer.json` autoload if needed and run `php artisan migrate` locally to validate (documentation only) `database/migrations/`

---

## Phase 3: User Story 1 - Legal Entity Company Registration (Priority: P1) 🎯 MVP

**Goal**: Implement the legal-entity registration path that creates a `Company` and an admin `User` (company_representative), and issues an OTP record.

**Independent Test**: Complete legal entity registration form submission and assert `companies` and `users` records are created with `legal_entity_flag = true` and role `company_representative`.

- [ ] T015 [P] [US1] Add Form Request `app/Modules/Registration/Requests/SelectCompanyTypeRequest.php`
- [ ] T016 [US1] Add Form Request `app/Modules/Registration/Requests/RegistrationAccountRequest.php` (email/password/name validation)
- [ ] T017 [US1] Add Form Request `app/Modules/Registration/Requests/RegistrationEntityRequest.php` (legal entity validation rules)
- [ ] T018 [US1] Implement controller `app/Modules/Registration/Controllers/RegistrationController.php` with methods: `showType()`, `postAccount()`, `postEntity()`, `postReview()` (skeleton)
- [ ] T019 [US1] Implement `app/Modules/Registration/Services/RegistrationService.php` with `createPendingRegistration(array $data)` that builds Company + User inside a DB transaction and creates EmailVerification record (OTP generation stubbed)
- [ ] T020 [US1] Add Blade views: `resources/views/registration/wizard.blade.php` and `resources/views/registration/steps/entity-details.blade.php` (legal entity variant)
- [ ] T021 [P] [US1] Add feature test `tests/Feature/LegalEntityRegistrationTest.php` that asserts company+user creation (write test first, expect failing)
- [ ] T022 [US1] Wire routes in `routes/registration.php`: `GET /register/type`, `POST /register/account`, `POST /register/entity`, `POST /register/review`

---

## Phase 4: User Story 2 - Natural Person Self-Registration (Priority: P1)

**Goal**: Implement the natural-person registration path that creates `Company` flagged as natural person and an owner `User`, sharing personal details across records.

**Independent Test**: Complete natural person registration and assert `companies` and `users` records match personal info and flags.

- [ ] T023 [P] [US2] Reuse `RegistrationAccountRequest.php` and add conditional validation for natural person in `RegistrationEntityRequest.php`
- [ ] T024 [US2] Add Blade view `resources/views/registration/steps/natural-person-details.blade.php`
- [ ] T025 [US2] Update `RegistrationController.php` to branch on `company_type` and persist natural person fields
- [ ] T026 [P] [US2] Add feature test `tests/Feature/NaturalPersonRegistrationTest.php` (write test first, expect failing)
- [ ] T027 [US2] Ensure `RegistrationService.php` copies personal fields into both `Company` and `User` records for natural person flows

---

## Phase 5: User Story 3 - Email Validation Workflow (Priority: P1)

**Goal**: Implement OTP generation, delivery, verification, and resend flows.

**Independent Test**: Create OTP for a pending user, verify token acceptance and expiry, and confirm user `email_verified_at` is set after successful verification.

- [ ] T028 [P] [US3] Implement `app/Modules/Registration/Services/EmailVerificationService.php` with methods: `generateOtp(user)`, `validateOtp(user, code)`, `resendOtp(user)`
- [ ] T029 [US3] Add request `app/Modules/Registration/Requests/VerifyOtpRequest.php` and controller endpoints `postVerifyOtp()` and `postResendOtp()` in `RegistrationController.php`
- [ ] T030 [US3] Send OTP via `Notifications/EmailOtpNotification.php` inside `RegistrationService::createPendingRegistration` (invoke `EmailVerificationService`)
- [ ] T031 [US3] Add feature test `tests/Feature/EmailVerificationTest.php` covering: OTP sent, valid OTP verifies account, expired/invalid OTP rejected, resend respects rate limits
- [ ] T032 [US3] Add database indexes on `email_verifications.verification_code_hash` and `expires_at` for lookup performance (migration update)

---

## Phase 6: Brand-Compliant Registration UI (Priority: P2)

**Goal**: Apply FlexDash brand colors and responsive layout to the registration wizard views.

- [ ] T033 [P] [US4] Implement `resources/views/registration/partials/progress-bar.blade.php` and `resources/views/registration/partials/brand-header.blade.php`
- [ ] T034 [US4] Complete `resources/views/registration/wizard.blade.php` to use Tailwind tokens and responsive layout (mobile-first)
- [ ] T035 [US4] Add a small visual smoke test `tests/Feature/RegistrationUiSmokeTest.php` that asserts view renders and contains brand token classes

---

## Phase N: Polish & Cross-Cutting Concerns

- [ ] T036 [P] Update `specs/001-multi-enterprise-registration/quickstart.md` with implemented setup instructions
- [ ] T037 [P] Add unit tests: `tests/Unit/RegistrationServiceTest.php`, `tests/Unit/EmailVerificationServiceTest.php` (write tests first)
- [ ] T038 [P] Add logging for registration and verification events in `app/Modules/Registration/Services/*`
- [ ] T039 Security: Add input sanitization and server-side validation confirmations (update Requests)
- [ ] T040 Run test suite locally and fix failing tests: `vendor/bin/pest` or `vendor/bin/phpunit` (documentation task)

---

## Dependencies & Execution Order

- Setup (Phase 1) must complete before Foundational (Phase 2) work begins.
- Foundational (Phase 2) blocks all user stories and must complete before Phase 3+ start.
- User stories US1, US2, and US3 are P1 priorities and can proceed in parallel once foundational tasks complete. US4 (UI) is P2 and can be implemented alongside or after P1 work depending on team capacity.

## Parallel Opportunities Identified

- Tasks marked with `[P]` can be executed in parallel by different developers: T003, T004, T008, T010, T011, T013, T021, T023, T026, T028, T033, T036, T037, T038

---

Generated by automation from spec and plan. Follow TDD: write failing tests before implementing corresponding feature tasks.
