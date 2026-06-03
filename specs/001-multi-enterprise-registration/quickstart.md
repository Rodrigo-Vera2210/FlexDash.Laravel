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
