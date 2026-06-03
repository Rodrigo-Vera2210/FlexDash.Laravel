# Implementation Plan: Multi-Enterprise Registration Wizard with OTP Email Verification

**Branch**: `main` | **Date**: 2026-06-02 | **Spec**: `/specs/001-multi-enterprise-registration/spec.md`

**Input**: Feature specification from `/specs/001-multi-enterprise-registration/spec.md`

## Summary

Implement FlexDash multi-enterprise registration as a 5-step wizard that supports both Legal Entity and Natural Person workflows, adds OTP-based email confirmation inside the registration flow, and uses Tailwind CSS with FlexDash brand colors. The feature will be built as a self-contained Laravel module aligned with the repository's module-based architecture and TDD-first mandate.

## Technical Context

**Language/Version**: PHP 8.2+ with Laravel 10+ (or current project default)

**Primary Dependencies**: Laravel framework, Tailwind CSS, PHPUnit/Pest, Laravel Mail, Laravel Validation, optional Livewire/Alpine.js for wizard behavior

**Storage**: Relational database via Laravel migrations (SQLite for local/dev, relational DB for production)

**Testing**: PHPUnit + Pest for unit and feature tests; Laravel HTTP tests; optional browser/e2e tests if supported

**Target Platform**: Web application

**Project Type**: Modular Laravel web service with wizard-style registration UI

**Performance Goals**: sub-2s form response time, OTP email delivery under 1 minute in 99.5% of cases, OTP validation in under 5 seconds

**Constraints**: Module-based architecture, layered design, TDD-first, Tailwind CSS, JWT authentication on login, email OTP verification requirement

**Scale/Scope**: Multi-tenant onboarding for enterprise and small-business customers; 5 wizard steps for a single user-facing registration flow

## Constitution Check

- TDD is mandatory and will be observed for all registration services, requests, and workflows.
- Layered architecture is respected by keeping business logic in Services and Repositories, with controllers as orchestration layers only.
- Registration feature will be implemented as a dedicated module, not merged into generic auth controllers.
- Tailwind CSS will be used for all UI styling; no arbitrary CSS classes outside the theme.
- JWT auth is not directly created by registration, but login and verification flows will remain compatible with existing JWT-based auth architecture.

## Project Structure

### Documentation (this feature)

```text
specs/001-multi-enterprise-registration/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
└── contracts/
    └── registration-api.md
```

### Proposed Source Code Structure

```text
app/Modules/Registration/
├── Controllers/
│   └── RegistrationController.php
├── Requests/
│   ├── SelectCompanyTypeRequest.php
│   ├── RegistrationAccountRequest.php
│   ├── RegistrationEntityRequest.php
│   ├── ReviewRegistrationRequest.php
│   └── VerifyOtpRequest.php
├── Services/
│   ├── RegistrationService.php
│   └── EmailVerificationService.php
├── Repositories/
│   ├── CompanyRepository.php
│   ├── UserRepository.php
│   └── EmailVerificationRepository.php
├── Models/
│   ├── Company.php
│   ├── User.php
│   └── EmailVerification.php
├── Contracts/
│   ├── RegistrationServiceInterface.php
│   └── EmailVerificationServiceInterface.php
├── Migrations/
├── Notifications/
│   └── EmailOtpNotification.php
├── Resources/
│   └── views/registration/
│       ├── wizard.blade.php
│       ├── steps/
│       │   ├── type-selection.blade.php
│       │   ├── account-contact.blade.php
│       │   ├── entity-details.blade.php
│       │   ├── review-submit.blade.php
│       │   └── verify-otp.blade.php
│       └── partials/
│           ├── progress-bar.blade.php
│           └── brand-header.blade.php
└── Tests/
    ├── Unit/
    └── Feature/
```

**Structure Decision**: Use a dedicated `Registration` module to keep onboarding code isolated, with Blade views for the wizard and Tailwind-based styling. The module will rely on service contracts and repository abstractions to integrate cleanly with shared auth and user management modules.

## Wizard Step Breakdown

1. **Select Registration Type**
   - Choose between `Legal Entity` and `Natural Person`.
   - Capture the workflow shape before showing inputs.
   - Persist selection in session or temporary wizard state.

2. **Account & Contact Information**
   - Collect email, password, and representative name.
   - For Legal Entity: representative full name + corporate contact email.
   - For Natural Person: owner full name + email and password.
   - Apply email uniqueness and password strength validation.

3. **Entity Details**
   - Legal Entity path: company name, tax ID, legal address, city, state/province, postal code, country.
   - Natural Person path: personal/business name, national ID, address, city, state/province, postal code, country.
   - Use conditional fields based on the selected registration type.

4. **Review & Submit**
   - Show a summary of entered information.
   - Require explicit consent to terms and privacy policy.
   - Allow user to edit prior steps before submission.

5. **Email OTP Verification**
   - Send a time-limited OTP to the provided email address.
   - Display a verification input form with resend countdown and fallback messaging.
   - Mark the user as verified only after entering the correct OTP.

## Technical Design Notes by Step

### Step 1: Registration Type

- Route: GET `/register/type`
- Persist `company_type` as `legal_entity` or `natural_person` in wizard state.
- Keep step lightweight and mobile-friendly.
- Offer help text: “Legal Entity for company registration, Natural Person for sole proprietors.”

### Step 2: Account & Contact

- Route: POST `/register/account`
- Validate:
  - email required, valid format, unique
  - password required, min 8 chars, mixed case, numbers, symbol
  - name required
- Use a `RegistrationAccountRequest` to enforce layered validation.
- Store progress in session or temporary pending registration model.

### Step 3: Entity Details

- Route: POST `/register/entity`
- Validate different payloads per company type.
- For legal entities:
  - `company_name`, `tax_id`, `legal_address`, `city`, `state_province`, `postal_code`, `country`
- For natural persons:
  - `full_name`, `id_number`, `address`, `city`, `state_province`, `postal_code`, `country`
- Use the same wizard backend service to merge the data with prior step state.

### Step 4: Review & Submit

- Route: POST `/register/review`
- Show computed summary and a consent checkbox.
- On submit, create database entities inside a transaction:
  - `Company` record with company type flags
  - `User` record with `role = company_representative|owner`
  - `EmailVerification` record with OTP, expiry, and pending status
- Use the `RegistrationService` to keep controllers thin.

### Step 5: Email OTP Verification

- Route: POST `/register/verify-otp`
- Send OTP using `EmailOtpNotification` with a secure 6-digit code or hashed token.
- Validate OTP code, expiration, and retry limits.
- Mark `email_verified_at` on the `User` record after successful verification.
- If the OTP expires or is invalid, allow resend via `/register/resend-otp`.

## Data Model and Entity Interaction Updates

### Key Entities

- `Company`
  - `id`
  - `company_type` enum: `legal_entity` | `natural_person`
  - `name`
  - `tax_id` nullable
  - `legal_address` nullable
  - `address` nullable
  - `city`
  - `state_province`
  - `postal_code`
  - `country`
  - `legal_entity_flag` boolean
  - `natural_entity_flag` boolean
  - `created_at`, `updated_at`

- `User`
  - `id`
  - `company_id`
  - `email`
  - `password`
  - `name`
  - `role` enum: `company_representative` | `owner` | `staff`
  - `email_verified_at` nullable
  - `status` enum: `pending_verification` | `active`
  - `created_at`, `updated_at`

- `EmailVerification`
  - `id`
  - `user_id`
  - `verification_code` (hashed)
  - `expires_at`
  - `attempts` integer
  - `created_at`, `updated_at`

### Interaction Notes

- Build the company and user in a single transaction to avoid partially created registrations.
- For natural person flow, copy name and address into both `Company` and `User` records consistently.
- Use `company_type` as the canonical discriminator, with `legal_entity_flag` and `natural_entity_flag` retained if needed for compatibility with existing schema.
- Keep email verification in a separate entity to avoid bloating `users` with temporary fields.
- Use the `EmailVerificationService` to encapsulate OTP generation, expiry, resend limits, and validation.

## UI/UX Considerations

- Use FlexDash brand palette: primary blue, teal/cyan gradient splash, yellow/orange CTA accents.
- Create a visually distinct wizard shell with:
  - top progress bar / step indicator
  - left or top summary panel on desktop
  - large primary CTA buttons in brand yellow/orange
  - secondary actions in neutral text or subtle teal accent
- Keep forms mobile-first and stack fields vertically at 375px.
- Use Tailwind utility classes with theme tokens such as:
  - `bg-brand-blue`, `text-brand-blue`, `border-brand-teal`, `bg-gradient-to-r from-brand-teal via-brand-cyan to-brand-blue`, `text-brand-yellow`, `bg-brand-yellow`
- Show dynamic field groups clearly when switching between Legal Entity and Natural Person.
- Include inline guidance for password strength and email validation.
- For OTP step:
  - clearly show the target email address
  - display a 6-digit input UI or single input field
  - show resend countdown and “Didn’t receive it?” messaging
  - support copy/paste and clear validation feedback
- Maintain brand consistency across all 5 steps with the same header, colors, and responsive card layout.

## Testing Strategy

### Unit Tests

- `RegistrationServiceTest`
  - valid wizard state merges correctly
  - company and user creation inside transaction
  - role assignment for `legal_entity` vs `natural_person`
- `EmailVerificationServiceTest`
  - OTP generation and expiry
  - invalid/expired code rejection
  - resend count and throttle behavior
- `CompanyRepositoryTest` and `UserRepositoryTest`
  - persistence and retrieval of created records
- Validation rule tests for all `FormRequest` classes

### Feature Tests

- Complete legal entity registration flow from type selection to OTP verification
- Complete natural person registration flow from type selection to OTP verification
- Prevent login when `email_verified_at` is null
- Allow login after successful OTP validation
- Reject registration when email already exists
- Reject weak password input
- Reject expired OTP and allow resend

### Integration Tests

- Mail fake injection for OTP delivery
- Transaction rollback when email sending fails
- Route coverage for wizard step transitions
- Logging audit events for registration and verification attempts

### E2E / Browser Tests (if available)

- Wizard step progression and back navigation
- Dynamic field visibility for company type selection
- OTP entry and error display
- Responsive rendering at 375px, 768px, 1024px widths

### TDD Workflow

1. Define acceptance tests for each wizard step and OTP scenario
2. Add failing tests for validation rules and service behavior
3. Implement minimal code to pass tests
4. Refactor while preserving all test coverage

## Architectural Decisions

- Implement registration as `app/Modules/Registration/` with its own service layer and request objects.
- Keep all business rules in Services and Domain classes; controllers only orchestrate requests/responses.
- Persist wizard progress in session state or a temporary `pending_registration` storage layer depending on whether the app needs resume-after-expiry behavior.
- Use `RegistrationServiceInterface` and `EmailVerificationServiceInterface` to preserve loose coupling.
- Route the wizard with named routes such as:
  - `registration.type`
  - `registration.account`
  - `registration.entity`
  - `registration.review`
  - `registration.verify-otp`
  - `registration.resend-otp`
- Use a `EmailOtpNotification` notification class to send email codes and maintain testability.
- Perform all create operations inside a database transaction to prevent partial registration records.
- Use a separate `EmailVerification` entity for OTP state, avoiding mutable temporary fields on `users`.
- Retain compatibility with existing multi-enterprise logic by mapping company type into `Company` flags and roles precisely.
- If the registration page is implemented with Livewire or Alpine.js, keep UI state in Blade components and route to server endpoints for each step.

## Implementation Notes

- The wizard should be tightly scoped: 5 steps maximum.
- OTP is required before finalizing the registration workflow; verification should happen before the user can proceed to login.
- The system should allow resend if the OTP expires or is not received, while safeguarding against abuse with rate limiting.
- Because the existing spec already requires `legal_entity_flag` and `natural_entity_flag`, keep these fields in the data model while introducing `company_type` as canonical.
- Use brand-colors from the FlexDash logo image by translating them into Tailwind theme tokens and applying them consistently in the registration layout.

## Next Deliverables

- `research.md` describing any remaining design clarifications and tradeoffs
- `data-model.md` detailing entity fields and relationships
- `quickstart.md` with implementation steps for the registration module
- `contracts/registration-api.md` describing the new registration route contract
