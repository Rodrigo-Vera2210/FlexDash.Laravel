# Tasks: Authentication Homogenization and JWT Integration (Spanish only)

## Phase 1: Foundational (JWT Middleware & Routing)

- [x] T001 Create JWT authentication middleware `app/Http/Middleware/EnsureJwtAuthenticated.php` to parse, verify, and resolve users from Bearer headers and cookies.
- [x] T002 Register the middleware alias `auth.jwt` inside `bootstrap/app.php`.
- [x] T003 Update protected routes in `routes/web.php` to use the `auth.jwt` middleware instead of standard session-based `auth`.
- [x] T003a Update `AuthController::login` to support setting the HTTP-only `token` cookie, redirecting HTML requests to the dashboard (or to `/register/verify-otp` if unverified), and adding the logout handler to clear the `token` cookie.
- [x] T004 Create feature test `tests/Feature/JwtAuthenticationTest.php` to assert JWT validation success and redirection behavior.

## Phase 2: View Interlinkage & Spanish Translation

- [x] T005 Translate the login template `resources/views/auth/login.blade.php` to Spanish and link the sign up button to `/register/type`.
- [x] T006 Update the registration wizard views under `resources/views/registration/` (including `wizard.blade.php` and steps views) to use Spanish language only, and verify links to `/login`.
- [x] T007 Translate the forgot-password template `resources/views/auth/forgot-password.blade.php` and reset-password template `resources/views/auth/reset-password.blade.php` to Spanish.

## Phase 3: Polish & Verification

- [x] T008 Add Spanish translation file config / validation responses (ensure system validation errors are localized).
- [x] T009 Run the test suite `php artisan test` locally and verify all 21+ tests pass with JWT and Spanish UI.
