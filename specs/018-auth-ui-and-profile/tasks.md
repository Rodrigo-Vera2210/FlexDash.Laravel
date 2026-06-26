# Tasks: Enhanced Authentication UI & User Profile Management

**Feature**: 018-auth-ui-and-profile | **Branch**: `018-auth-ui-and-profile` | **Status**: Ready for Implementation

**Architecture**: Blade (server-side templates) + Alpine.js (client-side interactivity) + JSON API endpoints

**Feature Scope**: Profile UI improvements, theme toggle accessibility (already works), password change with OTP, profile inline editing, user preferences management.

**Test Approach**: TDD-First (Red-Green-Refactor); leverage existing Auth/Registration OTP infrastructure; reuse Blade layouts and CSS variables from constitution.md.

**Key Constraint**: Project uses Blade templates + Alpine.js (NOT Vue SPA). Auth module, Registration module, and OTP system already functional. Focus on **integration and Blade view enhancement**, not rebuilding existing features.

---

## Overview: Execution Strategy

### Key Context: Blade + Alpine.js Architecture

⚠️ **Critical Note**: This project uses **Blade templates + Alpine.js** (NOT Vue SPA). The theme toggle is already functional via localStorage and CSS variables. Profile views exist in `resources/views/profile/`. Leverage existing:
- Layout system (`layouts/app.blade.php`, `layouts/guest.blade.php`)
- CSS variables defined in constitution.md (`--primary`, `--text-*`, etc.)
- Alpine.js for interactive components (no separate state management layer needed)
- Existing navigation and theme infrastructure

**Tasks focus on enhancement/integration, not UI recreation.**

### User Story Prioritization & Dependencies

| Story | Title | Priority | Dependencies | MVP Scope | Status |
|-------|-------|----------|--------------|-----------|--------|
| US1 | Navigation Between Auth Screens | P1 | Existing auth routes | ✓ Yes | Layout/JS enhance |
| US2 | Theme Toggle Always Accessible | P1 | ✅ Already implemented | ✓ Yes | Polish only |
| US3 | View & Edit Personal Information | P1 | Profile controller, Blade view exist | ✓ Yes | Enhance inline editing |
| US4 | Change Password with OTP Verification | P2 | Reuse EmailVerificationService | Optional | Modal component |
| US5 | Additional Profile Settings | P2 | User model extensions | Optional | Form component |

### Parallel Execution Opportunities

- **Phase 1 (Code Audit)**: T001–T003 (understand existing Blade structure, verify theme system) — parallelizable
- **Phase 2 (Data Model Updates)**: T004–T008 (add missing fields) — parallelizable, optional if fields already exist
- **Phase 3 (Backend Integration)**: T009–T020 (controllers, services, validation) — mostly sequential (Phase 2 → Phase 3)
- **Phase 4 (Blade View Enhancements)**: T021–T030 (update existing Blade templates, Alpine.js interactions) — parallelizable after Phase 3
- **Phase 5 (OTP Integration)**: T031–T040 (password change modal component, OTP reuse) — sequential (Phase 4 → Phase 5)
- **Phase 6 (Navigation & Polish)**: T041–T048 (wire components, testing, docs) — parallelizable

### Independent Test Criteria (Per User Story)

- **US1**: User navigates login ↔ registration without full page reload; form state persists in localStorage; back button visible
- **US2**: Theme toggle in top-right corner visible on all pages; persists via localStorage; applies instantly (<100ms); ✅ **Already works**
- **US3**: Profile Blade view displays all user info; inline edit with pencil icon (Alpine.js toggle); save/cancel inline; changes persist to DB
- **US4**: Password change requires OTP (reuse existing EmailVerificationService); session invalidated on OTHER devices; modal component in Blade
- **US5**: Language/timezone/notifications form persist per user in DB; apply on page reload; integrated in profile view

### MVP Scope (Recommended Start)

**Phase 1–4 = US1 + US2 + US3**
- Estimated effort: **1–2 days** (leverage existing Blade infrastructure)
- All core profile and theme features working
- Deferred: Password change with OTP (US4), advanced preferences (US5)


## Phase 1: Code Audit & Infrastructure Validation

**Goal**: Verify existing Blade views, layouts, theme system, and Auth/Registration services are ready for integration.

**Test Criteria**: Clear mapping of Blade templates; theme variables confirmed; OTP service validated; no breaking changes.

### T001–T004: Audit Existing Infrastructure

- [ ] T001 [P] Audit Blade infrastructure: verify `layouts/app.blade.php`, `layouts/guest.blade.php` exist; confirm CSS variables (--primary, --text-main, etc.) in root; verify dark mode script functional; test on mobile/desktop
- [ ] T002 [P] Audit existing views: `resources/views/auth/*.blade.php` (login, register, forgot-password); `resources/views/profile/edit.blade.php`; document current form layouts and identify Alpine.js enhancements needed
- [ ] T003 [P] Audit `app/Modules/Auth/` and `app/Modules/Registration/`: verify EmailVerificationService (OTP logic), PasswordController, existing methods; identify what ProfileController needs to add
- [ ] T004 Verify theme toggle implementation: check localStorage theme key ('theme'), CSS class application (dark:), system preference fallback; ensure no conflicts with existing styles

---

## Phase 2: Data Model Updates (Conditional)

**Goal**: Add missing user preference fields ONLY if not already present in User model.

**Test Criteria**: Migrations run without errors; User model has new fields (if added); no data loss on existing users.

### T005–T008: User Preference Fields (If Missing)

- [ ] T005 [P] Check `app/Models/User.php` for existing fields: theme_preference, language, timezone, notifications_enabled; if ALL exist, skip to Phase 3; if ANY missing, create single migration `database/migrations/2026_06_XX_add_missing_user_preferences.php`
- [ ] T006 [P] If migration needed: add theme_preference (enum: light|dark|system, default 'system'), language (enum: es|en, default 'es'), timezone (string, default 'America/Guayaquil'), notifications_enabled (bool, default true); backfill existing users with defaults
- [ ] T007 Run migration: `php artisan migrate` and verify schema with `php artisan migrate:status`
- [ ] T008 [P] Update `app/Models/User.php`: add new fields to fillable array, set appropriate $casts, add accessor for theme preference if needed for Blade templates

---

## Phase 3: Backend Integration (Controllers & API Endpoints)

**Goal**: Extend existing Auth controllers with profile endpoints and password change logic via JSON API.

**Test Criteria**: All endpoints respond with correct JSON data; validation works; OTP integration verified; endpoints testable via AJAX/Axios from Blade.

### T009–T019: Profile & Password Controllers

- [ ] T009 [P] Create or extend `app/Modules/Auth/Controllers/ProfileController.php` with methods:
  - `show()` [GET /api/profile] — return current user profile as JSON
  - `update()` [PUT /api/profile] — update name, email, phone via JSON request
  - Reuse validation rules from existing ProfileRequest if available
- [ ] T010 [P] Create or extend `app/Modules/Auth/Controllers/PasswordController.php` with methods:
  - `requestOtp()` [POST /api/password/request-otp] — validate current password, generate OTP, send email, return success
  - `verifyOtp()` [POST /api/password/verify-otp] — validate OTP token, return confirmation
  - `resetPassword()` [POST /api/password/reset] — update password, invalidate other sessions, return success
- [ ] T011 Create or update `app/Modules/Auth/Requests/UpdateProfileRequest.php` with validation: name (required, string), email (required, email, unique:users), phone (nullable, phone format)
- [ ] T012 Create `app/Modules/Auth/Requests/RequestPasswordOtpRequest.php` with validation: current_password (required, must match user's password hash)
- [ ] T013 Create `app/Modules/Auth/Requests/VerifyPasswordOtpRequest.php` with validation: otp_code (required, string, length 6)
- [ ] T014 Create `app/Modules/Auth/Requests/ResetPasswordRequest.php` with validation: new_password (required, min 12 chars, uppercase+digit+symbol, not same as current)
- [ ] T015 [P] Create or extend `app/Modules/Auth/Services/ProfileService.php` with TDD approach:
  - `getProfile(User $user): array` — fetch user with company data
  - `updateProfile(User $user, array $data): User` — validate and persist updates
  - `updatePassword(User $user, string $newPassword): User` — hash and save new password
- [ ] T016 [P] Create `app/Modules/Auth/Services/PasswordChangeOtpService.php` to bridge existing EmailVerificationService:
  - `generateOtp(User $user): string` — reuse EmailVerificationService logic
  - `validateOtp(User $user, string $otp): bool` — reuse validation
  - Ensure OTP table has purpose='password_change' to distinguish from email_verification OTPs
- [ ] T017 Create test `tests/Feature/ProfileControllerTest.php` (TDD-first) covering: GET /api/profile (happy path), PUT /api/profile (valid/invalid updates), validation errors
- [ ] T018 Create test `tests/Feature/PasswordChangeControllerTest.php` (TDD-first) covering: POST request-otp, POST verify-otp, POST reset (with session validation)
- [ ] T019 Register routes in `routes/api.php` (all behind auth middleware):
  ```php
  Route::middleware('auth:sanctum')->group(function () {
      Route::get('/profile', [ProfileController::class, 'show']);
      Route::put('/profile', [ProfileController::class, 'update']);
      Route::post('/password/request-otp', [PasswordController::class, 'requestOtp']);
      Route::post('/password/verify-otp', [PasswordController::class, 'verifyOtp']);
      Route::post('/password/reset', [PasswordController::class, 'resetPassword']);
  });
  ```

---

## Phase 4: Blade View Enhancements (Profile & Auth Screens)

**Goal**: Update/enhance existing Blade templates with Alpine.js interactions for profile viewing, inline editing, and form state persistence.

**Test Criteria**: Views render correctly with Alpine.js enhancements; inline edit works; form state persists; validation shows inline; uses CSS variables from constitution.

### T020–T030: Blade View Updates & Alpine.js Enhancements

- [ ] T020 [P] Update `resources/views/profile/edit.blade.php` to add profile header section:
  - User avatar, name, company name (read-only)
  - Display all profile fields: email, phone, theme preference (select)
  - Inline edit mode toggle via pencil icon (Alpine.js x-show/x-if)
  - Save/Cancel buttons that appear only in edit mode
  - Validation error messages displayed inline using existing `<x-input-error>` component
  - Use CSS variables: `--primary` for buttons, `--text-main` for labels, `--bg-secondary` for edit background
- [ ] T021 [P] Create Alpine.js module `resources/js/profile-form.js` to handle:
  - Form state management (editMode, formData, loading)
  - GET /api/profile AJAX call to load profile data on page load
  - PUT /api/profile AJAX call on save
  - Error/success message display
  - localStorage for form state recovery if needed
  - Attach to profile form via `x-data="profileFormHandler()"` in Blade
- [ ] T022 [P] Create partial `resources/views/profile/partials/inline-edit-toggle.blade.php`:
  - Pencil icon button that toggles edit mode
  - Uses Alpine.js x-cloak and transition classes
  - Styled with Tailwind + CSS variables
- [ ] T023 Create partial `resources/views/profile/partials/theme-selector.blade.php`:
  - Dropdown select for theme_preference (light|dark|system)
  - Integrated in profile form
  - Calls setTheme Alpine action on change
- [ ] T024 Update `resources/views/auth/login.blade.php`:
  - Add Alpine.js script to persist form state (email field) to localStorage on input
  - Add "¿Volver al Registro?" link at bottom with back button behavior
  - Load email from localStorage on page load if present
  - Clear localStorage on successful login
- [ ] T025 Update `resources/views/auth/register.blade.php`:
  - Add Alpine.js script to persist form state (non-sensitive fields) to localStorage
  - Add "Volver al Login" button at bottom
  - Load form data from localStorage on page load
  - Clear localStorage on successful registration
- [ ] T026 [P] Create Alpine.js component `resources/js/auth-navigation.js`:
  - Handle login ↔ register navigation via form state in localStorage
  - AJAX-based or form submission based on existing auth routes
  - Preserve user-entered data when navigating between screens
- [ ] T027 Create partial `resources/views/components/back-button.blade.php`:
  - Reusable back button component for auth screens
  - Takes route parameter or uses JavaScript history.back()
  - Styled consistently with design system (`--primary` color)
- [ ] T028 [P] Update `resources/views/layouts/app.blade.php` (verify/enhance):
  - Confirm theme toggle button in top-right is functional
  - Verify Alpine.js x-cloak is applied to avoid flash of unstyled content
  - Test dark mode class application on html element
  - Verify localStorage theme key is 'theme'
- [ ] T029 Update `resources/views/components` if needed: verify `<x-input-error>`, `<x-form-label>`, `<x-input-text>` components exist and match design system
- [ ] T030 Create feature test `tests/Feature/ProfileBladeViewTest.php`:
  - Load profile page via GET /profile (Blade route)
  - Verify profile data displays correctly
  - Submit profile form via Blade form or AJAX
  - Verify updates persist to database
  - Test inline edit toggle visibility

---

## Phase 5: Password Change Modal (Blade + Alpine.js)

**Goal**: Create password change modal component (Blade template + Alpine.js) that reuses existing OTP infrastructure.

**Test Criteria**: Modal displays correctly; 3-step flow works (current password → OTP → new password); password change completes; session invalidated on OTHER devices.

### T031–T040: Password Change Modal

- [ ] T031 [P] Create partial `resources/views/profile/partials/password-change-modal.blade.php`:
  - 3-step modal using Alpine.js x-show/x-transition
  - Step 1: Current password input + "Request OTP" button
  - Step 2: OTP input field + 30-second countdown timer + "Resend" button (disabled during cooldown)
  - Step 3: New password input + confirm password + save button
  - Modal header with close button (X), step indicator (1/2/3)
  - Use existing `<x-input-error>` for validation messages
  - Styled with Tailwind + CSS variables (`--primary`, `--bg-secondary`, etc.)
- [ ] T032 [P] Create Alpine.js handler `resources/js/password-change.js`:
  - State: currentStep (1|2|3), loading, errors, cooldownSeconds, showModal
  - Action handleRequestOtp(currentPassword): POST /api/password/request-otp, validate response, move to step 2
  - Action handleVerifyOtp(otpCode): POST /api/password/verify-otp, validate response, move to step 3
  - Action handleResetPassword(newPassword, confirmPassword): POST /api/password/reset, validate, show success, close modal, reload page or redirect
  - Action startCooldown(): 30s countdown timer, disable resend button
  - Action resendOtp(): POST /api/password/request-otp again, restart cooldown
  - Error handling: display messages inline, allow retry
  - Attach to modal via `x-data="passwordChangeHandler()"` in Blade
- [ ] T033 [P] Add "Change Password" button/link to profile view:
  - Button appears in profile header or action menu
  - Click opens password change modal
  - Uses Alpine.js x-show to toggle modal visibility
- [ ] T034 Create feature test `tests/Feature/PasswordChangeOtpIntegrationTest.php` (TDD-first):
  - Test POST /api/password/request-otp: valid password → OTP sent
  - Test POST /api/password/request-otp: invalid password → error returned
  - Test POST /api/password/verify-otp: valid OTP → confirmation returned
  - Test POST /api/password/verify-otp: invalid OTP, max attempts → error and lockout
  - Test POST /api/password/reset: new password → password updated, other sessions invalidated
  - Verify current device session remains active
- [ ] T035 [P] Create Pest feature test `tests/Feature/PasswordChangeBladeTest.php`:
  - Load profile page
  - Verify password change button/link visible
  - Test OTP modal renders correctly
- [ ] T036 Create acceptance test `tests/Feature/PasswordChangeAcceptanceTest.php`:
  - Full workflow: request OTP → verify OTP → change password → redirect/success message
  - Verify email sent (mock Resend)
  - Verify session invalidation on OTHER device
- [ ] T037 Backend (implemented in Phase 3 T009-T016, tested here):
  - Verify PasswordController.requestOtp() sends OTP email within 30s
  - Verify PasswordController.verifyOtp() validates OTP (max 3 attempts, 10-min expiration)
  - Verify PasswordController.reset() updates password and calls logoutOtherDevices() correctly
- [ ] T038 [P] Add localization keys to `lang/es/auth.php`:
  - password_change_title, request_otp_button, verify_otp_button, new_password_label, current_password_required, otp_sent, otp_expired, password_changed_success
- [ ] T039 Add localization keys to `lang/en/auth.php`: English translations
- [ ] T040 Create Cypress e2e test `tests/e2e/password-change-otp.spec.ts`:
  - Load profile
  - Click "Change Password"
  - Enter current password, request OTP
  - Verify email sent (or mock in test)
  - Enter OTP code
  - Enter new password, confirm
  - Verify success message
  - Verify redirect or page refresh
  - Verify OTHER session logged out (test with second browser session if possible)

---

## Phase 6: Navigation, User Menu & Integration

**Goal**: Wire navigation between auth screens, implement user menu linking to profile, ensure theme persistence on app init.

**Test Criteria**: Auth navigation works; user menu visible and functional; profile accessible from menu; theme loads correctly on page reload.

### T041–T048: Navigation Wiring

- [ ] T041 [P] Update `resources/views/auth/login.blade.php`:
  - Add "¿No tienes cuenta? {{ link('Regístrate aquí', route('register')) }}" link
  - Implement via existing back button component or direct link
  - Form state (email) persists to localStorage via Alpine.js
- [ ] T042 [P] Update `resources/views/auth/register.blade.php`:
  - Add "¿Ya tienes cuenta? {{ link('Inicia Sesión aquí', route('login')) }}" link
  - Form state (name, email, phone - non-sensitive) persists via Alpine.js
  - Alpine.js state object or form submission as per existing auth flow
- [ ] T043 [P] Verify/enhance user menu in `resources/views/layouts/app.blade.php`:
  - User name/avatar button in top-right or bottom-left
  - Dropdown menu with: "Perfil", "Preferencias", "Cambiar Contraseña", "Cerrar Sesión"
  - Dropdown implemented via Alpine.js x-show or existing UI pattern
  - Links use route() helper: route('profile.show'), route('profile.edit'), route('logout')
  - Styled consistently with CSS variables
- [ ] T044 Create or verify route `GET /profile` (Blade route) to show profile.edit.blade.php view
  - Route in `routes/web.php` or `routes/auth.php`
  - Requires auth middleware
  - Passes current user to view
- [ ] T045 Update `resources/views/layouts/app.blade.php` app initialization script:
  - On page load, fetch current user via GET /api/profile (or pass in Blade via @inject or auth()->user())
  - Initialize theme from user.theme_preference (fallback to localStorage, fallback to 'system')
  - Apply theme via Alpine.js setTheme action or direct CSS class
  - Initialize other user state (name, avatar) for user menu display
- [ ] T046 Create API endpoint `GET /api/auth/user` (if needed for frontend hydration):
  - Returns current authenticated user as JSON
  - Useful for single-page or AJAX-heavy apps
  - Not strictly needed if using server-side Blade rendering
- [ ] T047 Create feature test `tests/Feature/NavigationIntegrationTest.php`:
  - Test GET /profile loads profile page
  - Test user menu links resolve to correct routes
  - Test auth navigation between login/register
  - Test localStorage form state persistence
- [ ] T048 Create Pest feature test `tests/Feature/AuthNavigationBladeTest.php`:
  - Load login page → verify register link visible
  - Load register page → verify login link visible
  - Test form state persistence via localStorage

---

## Phase 7: Preferences & Advanced Features (Optional / Post-MVP)

**Goal**: Add language, timezone, notification settings to profile form.

**Test Criteria**: Preferences persist in DB; apply on reload; UI reflects user choices.

### T049–T056: Preferences Management (Optional)

- [ ] T049 [P] Create or verify `app/Enums/Language.php` with values: es, en (if not auto-generated by Laravel)
- [ ] T050 [P] Create or verify `app/Enums/Timezone.php` with IANA timezone list (America/Guayaquil as default for Ecuador) (if not already defined)
- [ ] T051 Create `app/Modules/Auth/Requests/UpdatePreferencesRequest.php` with validation:
  - language: required, in:es,en
  - timezone: required, timezone IANA
  - notifications_enabled: boolean
- [ ] T052 Extend `app/Modules/Auth/Controllers/ProfileController.php` with method updatePreferences():
  - Route: PUT /api/profile/preferences
  - Validate UpdatePreferencesRequest
  - Call ProfileService.updatePreferences()
- [ ] T053 Extend `app/Modules/Auth/Services/ProfileService.php` with method:
  - `updatePreferences(User $user, array $data): User`
  - Validate and persist language, timezone, notifications_enabled
- [ ] T054 Add route `PUT /api/profile/preferences` in `routes/api.php` (protected by auth middleware)
- [ ] T055 [P] Create partial `resources/views/profile/partials/preferences-form.blade.php`:
  - Language dropdown (es|en)
  - Timezone select with search (Alpine.js searchable select or plain select)
  - Notifications toggle checkbox
  - Save button
  - Integrated into profile.edit.blade.php as section or modal
- [ ] T056 Create Pest feature test `tests/Feature/UserPreferencesTest.php`:
  - PUT /api/profile/preferences with valid language/timezone/notifications
  - Verify database update
  - Verify on next page load, preferences persist

---

## Phase 8: Error Handling, Testing & Documentation

**Goal**: Comprehensive error handling, localization, testing, and documentation for all features.

**Test Criteria**: All user stories covered by tests; performance meets SLAs; docs complete; no critical bugs; code quality gates pass.

### T057–T071: QA, Localization & Docs

- [ ] T057 [P] Add comprehensive error handling to all ProfileController and PasswordController methods:
  - Catch validation exceptions, throw ProfileException with localized message
  - Return consistent JSON error response format
  - Include error code, message (Spanish/English), and validation details if applicable
- [ ] T058 [P] Create exception class `app/Modules/Auth/Exceptions/ProfileException.php` extending Exception:
  - Custom error codes and messages for profile operations
  - Localization support (getMessage returns Spanish by default, English if header Accept-Language: en)
- [ ] T059 [P] Add logging to ProfileService and PasswordController:
  - Log profile updates: `Log::info('Profile updated', ['user_id' => $user->id, 'fields' => [...]])`
  - Log OTP requests: `Log::info('Password change OTP requested', ['user_id' => $user->id])`
  - Log password changes: `Log::info('Password changed', ['user_id' => $user->id])`
  - Log failed OTP attempts: `Log::warning('OTP verification failed', ['user_id' => $user->id, 'attempts' => ...])`
- [ ] T060 Create or update localization file `lang/es/auth.php` with keys:
  - profile.title, profile.save, profile.cancel, profile.edit, profile.updated_success
  - password.change_title, password.current_password, password.new_password, password.confirm_password
  - password.request_otp, password.otp_sent, password.otp_expired, password.otp_resend, password.changed_success
  - validation messages for all fields
- [ ] T061 Create or update localization file `lang/en/auth.php` with English translations
- [ ] T062 [P] Create integration test `tests/Integration/ProfileWorkflowTest.php`:
  - Login → GET /profile → verify profile displays → PUT /profile with changes → verify updates → logout
  - Full workflow covering happy path
- [ ] T063 [P] Create integration test `tests/Integration/PasswordChangeWorkflowTest.php`:
  - Login → initiate password change → request OTP → verify OTP → reset password → verify session invalidated
  - Test other session logged out
- [ ] T064 [P] Create performance test `tests/Performance/ProfilePageLoadTimeTest.php`:
  - Measure profile page load time
  - Target: <1.5s for DOMContentLoaded
  - Verify no N+1 queries
- [ ] T065 [P] Create performance test `tests/Performance/ThemeToggleTest.php`:
  - Measure theme toggle response time
  - Target: <100ms from button click to visual change
  - Test localStorage write doesn't block UI
- [ ] T066 [P] Run PHPStan level 9:
  ```bash
  vendor/bin/phpstan analyse app/Modules/Auth --level=9
  ```
  - Fix any violations
  - Verify no type errors
- [ ] T067 [P] Run Laravel Pint (code formatter):
  ```bash
  ./vendor/bin/pint app/Modules/Auth
  ```
  - Ensure consistent code style
- [ ] T068 [P] Run Pest test suite with coverage:
  ```bash
  ./vendor/bin/pest tests/Feature/ --coverage --min=80
  ```
  - Target 80%+ code coverage
  - Generate coverage report
- [ ] T069 Create comprehensive test file `tests/Feature/ProfileEdgeCasesTest.php`:
  - Test updating only one field (partial update)
  - Test invalid email format
  - Test email collision (another user's email)
  - Test concurrent profile updates
  - Test permission checks (user cannot update other user's profile)
- [ ] T070 Create comprehensive test file `tests/Feature/PasswordChangeEdgeCasesTest.php`:
  - Test wrong current password → error
  - Test OTP max attempts (3) → lockout
  - Test OTP expiration (10 min) → new OTP required
  - Test concurrent OTP requests → previous token invalidated
  - Test password same as current → error
  - Test new password validation (length, complexity)
- [ ] T071 Create or update implementation documentation `specs/018-auth-ui-and-profile/IMPLEMENTATION_GUIDE.md`:
  - Overview of 8 phases
  - Architecture: Blade + Alpine.js + API endpoints
  - How to run tests
  - How to deploy
  - Known issues or TODOs
  - Links to all related files

---

## Task Execution Flow & Dependencies

### Critical Path (Minimum to Complete MVP)

```
Phase 1 (Code Audit: Verify Blade/Alpine/Theme)
    ↓
Phase 2 (Data Model: Add user preference fields if missing) [OPTIONAL]
    ↓
Phase 3 (Backend APIs: ProfileController, PasswordController, Services)
    ↓
Phase 4 (Blade View Enhancements: Update profile/auth views with Alpine.js)
    ↓
Phase 5 (Password Change Modal: Blade partial + Alpine handler) [builds on Phase 4]
    ↓
Phase 6 (Navigation & User Menu: Link auth screens, wire menu)
    ↓
Phase 8 (Testing & Documentation)
```

### Parallel Opportunities

**Within Phase 1** (T001–T004): All audit tasks parallelizable

**Within Phase 3** (T009–T019):
- T009–T010 (ProfileController + PasswordController) — parallelizable
- T011–T014 (Request form classes) — parallelizable with T009–T010
- T015–T016 (Services) — parallelizable with T011–T014
- T017–T018 (Feature tests) — parallelizable after T009–T016 are stubbed

**Within Phase 4** (T020–T030):
- T020, T022–T023 (Blade partials) — parallelizable
- T021, T026 (Alpine.js modules) — parallelizable with T020–T023
- T024–T025 (Auth screen updates) — parallelizable
- T027–T029 (Component verification) — can run in parallel

**Between Phases 4–5**: T031–T040 (Password modal) requires Phase 4 Blade foundation

**Phase 6** (T041–T048): Can run in parallel with Phase 5 (navigation doesn't depend on password modal)

**Phase 7** (T049–T056): Optional; can run in parallel with Phase 6 or after

**Phase 8** (T057–T071): Can start in parallel with Phase 5–7 for documentation; testing happens throughout via TDD

### Team Sizing Recommendation

- **1 Person (Solo Dev)**: Sequential execution; MVP (Phase 1–6) = **1–2 days** (leveraging existing Blade infrastructure)
- **2 People**: 
  - Person A: Phase 1–2, Phase 3 (Backend)
  - Person B: Phase 4 (Blade views) + Phase 6 (Navigation)
  - Both: Phase 5 (Password modal) + Phase 8 (Testing)
  - Timeline: **1–1.5 days MVP**
- **3+ People**: Full parallelization across Phases 1–6 = **0.5–1 day**

**Key Advantage Over Previous Estimate**: Blade + Alpine.js reuses existing infrastructure (layouts, components, theme system) vs. building Vue SPA from scratch. Estimated effort reduced from 2–3 days to **1–2 days MVP**.

---

## Testing & Quality Gates

### Unit Test Coverage (Phase 3, 8)

- **ProfileService & PasswordChangeOtpService**: 100% method coverage (TDD-first approach)
- **Request form classes** (UpdateProfileRequest, etc.): 100% validation rule coverage
- **ProfileException custom exception**: Error handling paths covered
- Target: 80%+ overall code coverage for all new code in app/Modules/Auth/

### Feature Test Coverage (Phase 3, 5, 8)

- **Profile endpoints**: GET /api/profile (happy path + 401 unauthorized), PUT /api/profile (valid + invalid data), validation errors
- **Password change endpoints**: POST request-otp (happy + wrong password), POST verify-otp (valid + invalid + max attempts), POST reset (happy + error cases)
- **Session invalidation**: Verify other sessions logged out after password change
- **OTP flow**: Generation, validation, max attempts (3), 10-min expiration, concurrent token invalidation
- Target: 80% feature test coverage

### Blade View & Alpine.js Tests (Phase 4, 8)

- **Profile Blade view**: Renders profile data correctly, inline edit toggle works, form submission calls API
- **Auth navigation**: Form state persists to localStorage, navigation between screens works
- **Theme toggle**: localStorage key 'theme' updated, CSS dark class applied, persists on reload
- **Password modal**: Steps 1–3 display correctly, cooldown timer works, error messages display, OTP resend button disabled during cooldown

### Acceptance Tests (Phase 4–6, 8) — Blade + Browser Testing

- **US1**: User navigates login ↔ register without full page reload, form state persists (email field)
- **US2**: Theme toggle visible on all pages (top-right corner), works instantly (<100ms), persists across sessions ✓ **Already works**
- **US3**: Profile displays all user info, inline pencil icon toggles edit mode, save persists changes to DB
- **US4**: Password change modal: request OTP → verify OTP → reset password → success, OTHER sessions logged out
- **US5**: Preferences form (language/timezone/notifications) persists to DB, applies on reload

### Performance Benchmarks (Phase 8)

- **Profile page load**: <1.5s DOMContentLoaded (leverage server-side Blade rendering for fast initial paint)
- **Theme toggle response**: <100ms visual change (localStorage + CSS class application via Alpine.js)
- **OTP email delivery**: <30s (reuse existing Resend integration)
- **API response times**: <500ms for all endpoints (minimal DB queries, eager loading where needed)

---

## Implementation Notes & Best Practices

### Architecture: Blade Server-Side Templates + Alpine.js

- **Backend**: Laravel controllers + services return JSON APIs for AJAX calls
- **Frontend**: Blade templates render HTML server-side; Alpine.js adds interactivity without page reloads
- **State Management**: Alpine.js x-data for component-level state; localStorage for theme/form persistence
- **Styling**: Tailwind CSS utility classes + CSS variables from constitution.md (`--primary`, `--text-*`, `--bg-*`)
- **No Vue SPA**: This project uses traditional server-side rendering (Blade) with progressive enhancement (Alpine.js)

### Code Reuse Strategy

- **OTP System**: Reuse existing `EmailVerificationService` from Registration module; create bridge `PasswordChangeOtpService` to adapt it for password change workflow
- **Auth Controllers**: Extend existing `AuthController`, `PasswordController` rather than creating new ones
- **Blade Templates**: Update existing `resources/views/profile/*.blade.php`, `resources/views/auth/*.blade.php` with Alpine.js enhancements
- **CSS Variables**: Use existing theme variables (`--primary`, `--bg-secondary`, `--text-main`, etc.); no new CSS files unless absolutely necessary
- **Components**: Verify/enhance existing Blade components (`<x-input-error>`, `<x-form-label>`, `<x-button>`) instead of creating new ones

### TDD Workflow (Per Task)

1. Write failing test (Red) — test your feature/endpoint/validation first
2. Implement minimal code to pass test (Green)
3. Refactor for clarity, DRY, and consistency (Refactor)
4. Verify test still passes; add edge cases if discovered

**Example**:
```php
// Test first: tests/Feature/ProfileControllerTest.php
test('users can view their profile', function () {
    $user = User::factory()->create();
    $response = actingAs($user)->get('/api/profile');
    $response->assertStatus(200)
             ->assertJsonStructure(['id', 'name', 'email', 'company_id']);
});

// Then implement: ProfileController.php
public function show(): JsonResponse {
    return response()->json(auth()->user()); // minimal implementation
}

// Then refactor: ProfileController.php + ProfileService.php
// Use ProfileService to encapsulate business logic
```

### Alpine.js Patterns

**Toggle Edit Mode**:
```html
<div x-data="{ editing: false }">
    <div x-show="!editing">Display Name: {{ $user->name }}</div>
    <input x-show="editing" type="text" x-model="formData.name">
    <button @click="editing = !editing">{{ editing ? 'Cancel' : 'Edit' }}</button>
</div>
```

**Form State Persistence**:
```html
<form @submit.prevent="submitForm" x-data="{ formData: @json(old('email')) }">
    <input x-model="formData.email" @input="localStorage.setItem('formData', JSON.stringify(formData))">
</form>
```

**API Calls**:
```html
<button @click="updateProfile" :disabled="loading">
    <span x-show="loading">Guardando...</span>
    <span x-show="!loading">Guardar</span>
</button>

<script>
function profileHandler() {
    return {
        loading: false,
        updateProfile() {
            this.loading = true;
            fetch('/api/profile', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify(this.formData)
            })
            .then(r => r.json())
            .then(data => { this.loading = false; alert('Guardado!'); })
            .catch(e => { this.loading = false; alert('Error: ' + e.message); });
        }
    }
}
</script>
```

### Security Considerations

- **CSRF Protection**: Laravel token included in all form submissions; X-CSRF-TOKEN header in AJAX requests
- **Password Security**: Current password verified before OTP request; new password hashed before storage
- **OTP Security**: Tokens hashed before storage; max 3 verification attempts; 10-min expiration
- **Session Invalidation**: Log out OTHER devices only after password change (clarification A1); current device remains active
- **No Passwords in Logs**: Never log passwords or OTP codes
- **Email Validation**: Verify email before sending OTP (prevent spam)

### Database Safety

- **Migrations**: Add new fields WITHOUT altering existing functionality; backfill defaults for existing users
- **No Breaking Changes**: Existing schema intact; add-only approach ensures backward compatibility
- **Cascade Delete**: If user deleted, related OTP tokens, preferences cascade delete
- **Timestamps**: All tables have `created_at`, `updated_at`; use soft deletes if user deletion needs to preserve history

### Localization (Spanish-First)

- All user-facing strings in `lang/es/auth.php` (primary language)
- English translations in `lang/en/auth.php` (fallback)
- Form labels, validation messages, success/error messages all localized
- Timezone default: America/Guayaquil (Ecuador)
- Language default: Spanish (es)

---

## Sign-Off & Verification

### Phase Completion Checklist (Per Phase)

- [ ] All tasks in phase completed and tested
- [ ] No critical bugs blocking next phase
- [ ] Code review approved (at least one team member)
- [ ] Performance benchmarks met (if applicable)
- [ ] Localization complete (Spanish strings in place)
- [ ] Documentation updated

### MVP Sign-Off Criteria (Phase 1–6: US1 + US2 + US3)

**Functionality**:
- [ ] User navigates between login ↔ register screens without full page reload
- [ ] Form state (email field) persists to localStorage
- [ ] Theme toggle visible and functional on all pages (login, register, dashboard, profile)
- [ ] Theme preference persists across sessions via localStorage
- [ ] Theme applies instantly (<100ms) when toggled
- [ ] Profile page displays all user information (name, email, phone, company)
- [ ] Inline edit mode toggles with pencil icon (Alpine.js)
- [ ] Profile updates save to database via PUT /api/profile
- [ ] Validation errors display inline for all fields

**Quality**:
- [ ] All validations work; error messages clear in Spanish
- [ ] Mobile and desktop responsive (test on 320px, 768px, 1024px+ viewports)
- [ ] Accessibility compliant (WCAG 2.1 AA): keyboard navigation, color contrast, labels, error associations
- [ ] No console errors or warnings
- [ ] Performance: profile page load <1.5s, theme toggle <100ms
- [ ] 80%+ test coverage on new code (ProfileService, ProfileController, etc.)
- [ ] PHPStan level 9 pass, Laravel Pint formatting clean
- [ ] All Pest/PHPUnit tests passing

**Readiness**:
- [ ] No critical bugs reported
- [ ] All tests pass on main branch
- [ ] Documentation complete (README, IMPLEMENTATION_GUIDE)
- [ ] Team sign-off received

### Full Feature Sign-Off Criteria (All 5 User Stories + Phase 7)

- [ ] All US1–US5 acceptance criteria met
- [ ] **US1**: Navigation fully functional with form persistence
- [ ] **US2**: Theme toggle works on all pages, persists, applies instantly
- [ ] **US3**: Profile CRUD (view/edit) fully functional
- [ ] **US4**: Password change via OTP modal complete; session invalidation working (OTHER devices logged out)
- [ ] **US5**: Preferences (language/timezone/notifications) form created and persists to DB
- [ ] All new services, controllers, Blade views tested (80%+ coverage)
- [ ] OTP workflow secure (reuses existing system; tested)
- [ ] Session invalidation correct (OTHER devices only; current device active)
- [ ] Preferences apply on reload
- [ ] Performance SLAs met (all benchmarks passed)
- [ ] Security audit passed (no hardcoded secrets, CSRF protected, passwords secured)
- [ ] Documentation complete
- [ ] Ready for production deployment

---

**Last Updated**: 2026-06-25 | **Architecture**: Blade + Alpine.js + JSON API | **Estimated Duration**: 1–2 days MVP (solo dev) | **Next Review**: After Phase 1 code audit completion
