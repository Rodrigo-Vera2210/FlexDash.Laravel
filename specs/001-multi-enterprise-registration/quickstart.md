# Quickstart: Implementing the Registration Wizard

1. Create `app/Modules/Registration` with Controllers, Requests, Services, Repositories, Models, and Tests.
2. Add migrations for `companies`, `users`, and `email_verifications`.
3. Define registration routes in a dedicated route file, e.g. `routes/registration.php`.
4. Build the wizard views under `resources/views/registration/` with Tailwind styling and brand color tokens.
5. Implement form request validation for each step and write failing tests first.
6. Implement `RegistrationService` to orchestrate wizard state, company/user creation, and OTP issuance.
7. Implement `EmailVerificationService` for OTP generation, expiration, validation, and resend.
8. Add `EmailOtpNotification` to send emails through Laravel Mail.
9. Add feature tests for both legal entity and natural person flows, including OTP verification.
10. Validate login behavior to deny access until `email_verified_at` is set.
11. Ensure responsive wizard behavior across mobile and desktop breakpoints.
12. Run tests and iterate until all acceptance criteria pass.

## Implementation Details

### Directory Structure
- **Module Source**: [app/Modules/Registration/](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Registration/)
  - `Controllers/RegistrationController.php` (Wizard steps & OTP verification orchestrator)
  - `Services/RegistrationService.php` (DB Transactions & Record Creation)
  - `Services/EmailVerificationService.php` (OTP Lifecycle Management)
  - `Requests/` (Step-by-step FormRequests)
  - `Models/` (Company & EmailVerification models)
  - `Notifications/EmailOtpNotification.php` (Verification email delivery notification)
- **Views**: [resources/views/registration/](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/registration/)
- **Routes**: [routes/registration.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/routes/registration.php)

### Running Verification Tests
Execute the entire test suite locally to verify the registration and email verification flows:
```bash
php artisan test
```
