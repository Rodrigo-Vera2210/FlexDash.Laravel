# Implementation Plan: Authentication Homogenization and JWT Integration (Spanish only)

**Branch**: `002-auth-homogenization` | **Date**: 2026-06-07 | **Spec**: `/specs/002-auth-homogenization/spec.md`

## Summary

This plan details the implementation of a custom JWT validation middleware to secure all backend controllers, linking the login and registration wizard views, and translating all user-facing auth elements exclusively into Spanish.

## Technical Context

- **Language/Version**: PHP 8.2+ with Laravel 11
- **Primary Dependencies**: Laravel Framework, Tailwind CSS, PHPUnit/Pest
- **Testing**: PHPUnit feature and unit tests to verify JWT protection and route redirection.
- **Constraints**: Force stateless JWT validation via headers/cookies, enforce Spanish language globally in the authentication flow.

## Proposed Changes

### 1. JWT Middleware Integration

We will create a custom middleware `EnsureJwtAuthenticated` to intercept, parse, verify, and resolve JWT tokens.

#### [NEW] [EnsureJwtAuthenticated.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Http/Middleware/EnsureJwtAuthenticated.php)
- Parse token from the HTTP `Authorization` Bearer header or the `token` cookie.
- Split the JWT and re-hash the signature using `APP_KEY` (compatible with `AuthController::login` format).
- Validate the expiration time (`exp` claim).
- Find the user by `user_id`.
- Resolve the user resolver on the request via `$request->setUserResolver()` and call `Auth::login($user)`.
- If invalid or missing, redirect to `/login` for HTML requests or return JSON `401 Unauthorized` for JSON/AJAX requests.

#### [MODIFY] [app.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/bootstrap/app.php)
- Register `EnsureJwtAuthenticated` as a middleware alias (e.g. `'auth.jwt'`).

#### [MODIFY] [web.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/routes/web.php)
- Replace the `'auth'` middleware group protector with our custom `'auth.jwt'` middleware.

---

### 2. View Connections & Spanish Translation

We will translate all authentication views to Spanish and ensure they are connected.

#### [MODIFY] [login.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/auth/login.blade.php)
- Translate to Spanish: Update all text headers, placeholders, buttons, and errors.
- Connect: Change the "Sign up" link to point to `/register/type` (the registration wizard start).
- On login success, `AuthController::login` will set the JWT token in the HTTP-only `token` cookie and redirect the browser directly to `/dashboard`. If the user is unverified, it redirects back to `/register/verify-otp` with `registered_user_id` session setup.

#### [MODIFY] [web.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/routes/web.php)
- Replace standard session-based `'auth'` middleware group protector with our custom `'auth.jwt'` middleware. This will secure the Dashboard, Partners, Products, Inventory, Sales, Purchases, and Audit controllers using JWT.

#### [MODIFY] [registration views](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/registration/)
- Translate all labels, button texts, error messages, and descriptions inside `steps/` and `wizard.blade.php` to Spanish.
- Verify "Sign in" buttons point to `/login`.

#### [MODIFY] [forgot-password.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/auth/forgot-password.blade.php)
- Translate to Spanish.

#### [MODIFY] [reset-password.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/auth/reset-password.blade.php)
- Translate to Spanish.

---

### 3. Verification & Testing

#### [NEW] [JwtAuthenticationTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/tests/Feature/JwtAuthenticationTest.php)
- Write tests to verify:
  - Request without JWT to protected routes redirects to `/login` or returns 401.
  - Request with valid JWT (Authorization header or cookie) allows access.
  - Request with expired/invalid JWT is rejected.
- Execute all tests: `php artisan test`

## Verification Plan

### Automated Tests
- Run `php artisan test` to verify the JWT authentication middleware blocks or authorizes requests correctly.

### Manual Verification
- Access `/register/type` and verify all labels are in Spanish, and clicking the sign in button links to `/login`.
- Access `/login` and verify that the sign up button links to `/register/type`, and all texts are in Spanish.
