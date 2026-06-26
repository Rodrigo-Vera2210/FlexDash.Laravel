# Tasks: Enhanced Authentication UI & User Profile Management (Final - 38 Tasks)

**Feature**: 018-auth-ui-and-profile | **Status**: Ready for Implementation | **Total Tasks**: 38 (reduced from 71)

**Architecture**: Blade + Alpine.js + JSON API (NOT Vue SPA)

## Reality Check: What Exists vs. What's Missing

### ✅ ALREADY BUILT (Complete)
- 10 Auth controllers (login, logout, email OTP, password reset, etc.)
- ProfileController with 3 routes: GET/PATCH/DELETE /profile
- 9 Blade templates (auth screens + profile edit form with 3 partials)
- EmailVerificationService (6-digit OTP, hashed, 24h TTL, 5 attempts)
- Theme toggle + CSS variable system (functional)
- Multi-tenant registration service
- Email notification system

### ❌ MISSING (38 Tasks to Fix)
1. **Migration**: Add 4 user preference fields (theme_preference, language, timezone, notifications_enabled)
2. **API Endpoints**: No /api/profile or /api/password JSON endpoints
3. **Alpine.js AJAX**: Current Blade forms do full page reloads; need AJAX interactivity
4. **Password OTP Modal**: Must adapt existing EmailVerificationService for password change workflow
5. **Better Error Messages**: Improve validation UI
6. **Testing**: TDD-first tests for new code

---

## PHASE 1: Code Audit & Verification (3 Tasks)

**Goal**: Verify existing infrastructure is correct and ready for enhancement.

### T001: Audit Blade Structure & Theme System
- **What**: Review `layouts/app.blade.php`, theme toggle script, CSS variable definitions
- **Check**: localStorage 'theme' key, dark mode class application, CSS variables (--primary, --text-main, etc.)
- **Test**: Theme toggle works instantly, persists on reload, system preference fallback works
- **File**: `resources/views/layouts/app.blade.php`
- **Expected**: ✓ Confirmed working; no changes needed

### T002: Audit Existing Auth Infrastructure
- **What**: Verify all 10 Auth controllers exist and core methods are implemented
- **Check**: LoginController, LogoutController, PasswordResetController, EmailVerificationController, etc.
- **Verify**: All routes in `routes/auth.php` map to existing controllers
- **Files**: `app/Modules/Auth/Controllers/`, `routes/auth.php`
- **Expected**: ✓ All 10 controllers exist; no changes needed

### T003: Audit Existing ProfileController & Blade Views
- **What**: Verify ProfileController has edit(), update(), destroy() methods
- **Check**: Profile Blade form has name, email, phone fields; partials for password + delete modals exist
- **Verify**: Form validation, error messages display, Blade components used correctly
- **Files**: `app/Modules/Profile/Controllers/ProfileController.php`, `resources/views/profile/`
- **Expected**: ✓ ProfileController & 3 partials exist; no changes needed

---

## PHASE 2: Data Model Updates (3 Tasks) — CONDITIONAL

**Goal**: Add missing user preference fields to database (ONLY if not present).

### T004: Check User Model for Missing Fields
- **What**: Inspect `app/Models/User.php` fillable + casts
- **Check**: Does User model have theme_preference, language, timezone, notifications_enabled?
- **If Missing**: Create migration; if present, skip to Phase 3
- **Files**: `app/Models/User.php`
- **Decision**: If all 4 fields exist → **Skip Phase 2 entirely** (go to Phase 3)

### T005: Create Migration for User Preferences (If Needed)
- **What**: Generate migration adding 4 nullable columns to users table
- **Fields**:
  - `theme_preference` (enum: 'light'|'dark'|'system', default='system')
  - `language` (enum: 'es'|'en', default='es')
  - `timezone` (string, default='America/Guayaquil')
  - `notifications_enabled` (bool, default=true)
- **Test**: Run migration; verify fields in `php artisan tinker`; backfill with defaults for existing users
- **File**: `database/migrations/YYYY_MM_DD_add_user_preferences_to_users_table.php`

### T006: Update User Model with New Fields
- **What**: Add new fields to User model fillable + casts
- **Add**: Casts for enum fields (theme_preference, language) if Laravel 11+
- **File**: `app/Models/User.php`
- **Test**: Can mass-assign new fields; casts work correctly

---

## PHASE 3: Backend API Endpoints (8 Tasks)

**Goal**: Create JSON API endpoints for profile & password management.

### T007: Create/Enhance ProfileController JSON Methods
- **What**: Add `show()` method to return current user as JSON; enhance `update()` to handle JSON requests
- **Methods**:
  - `GET /api/profile` → return auth()->user() as JSON
  - `PATCH /api/profile` → validate + update user fields (name, email, phone, language, timezone, notifications_enabled)
- **File**: `app/Modules/Profile/Controllers/ProfileController.php`
- **Test**: GET returns 200 + user JSON; PATCH with valid data returns 200; PATCH with invalid data returns 422

### T008: Create PasswordChangeController (New)
- **What**: Create new controller with 3 methods for OTP-based password change
- **Methods**:
  - `POST /api/password/request-otp` → verify current_password, call PasswordChangeOtpService, return { message, cooldown_seconds }
  - `POST /api/password/verify-otp` → validate OTP token, return { token_valid: bool }
  - `PUT /api/password/reset` → verify OTP token valid, hash new password, update user, invalidate OTHER sessions
- **File**: `app/Modules/Auth/Controllers/PasswordChangeController.php`
- **Test**: Each method returns correct HTTP status + JSON response

### T009: Create UpdateProfileRequest Form Validation
- **What**: Form request class validating profile update fields
- **Rules**: name (required, string, max 255), email (required, email, unique except current), phone (nullable, phone), language (in:es,en), timezone (in:list), notifications_enabled (bool)
- **File**: `app/Modules/Profile/Requests/UpdateProfileRequest.php`
- **Test**: Valid + invalid data; unique email validation works

### T010: Create PasswordChangeOtpService (Bridge Service)
- **What**: New service adapting existing EmailVerificationService for password change workflow
- **Methods**:
  - `requestOtp(User $user)` → generate OTP via EmailVerificationService; set purpose='password_change'; send email; return cooldown_seconds
  - `verifyOtp(User $user, string $otp)` → validate OTP; mark token used; return bool
  - `resetPassword(User $user, string $newPassword, string $otpToken)` → verify OTP token used; hash + update password; logout OTHER sessions
- **File**: `app/Modules/Auth/Services/PasswordChangeOtpService.php`
- **Reuse**: Adapt existing `EmailVerificationService` generation/validation logic
- **Test**: OTP flow works end-to-end; session invalidation tested

### T011: Create OTP Request Form Classes (3 Forms)
- **What**: Form request classes for each OTP endpoint
- **Files**:
  - `RequestPasswordOtpRequest.php` → current_password required
  - `VerifyPasswordOtpRequest.php` → otp required
  - `ResetPasswordRequest.php` → new_password + new_password_confirmation required + matching
- **Test**: All validation rules enforced

### T012: Register API Routes for Profile & Password
- **What**: Add routes to `routes/api.php`
- **Routes** (all with `auth:sanctum` middleware):
  - `GET /profile`
  - `PATCH /profile`
  - `DELETE /profile` (account deletion via JSON)
  - `POST /password/request-otp`
  - `POST /password/verify-otp`
  - `PUT /password/reset`
- **File**: `routes/api.php`
- **Test**: Routes return 401 without token; 200 with valid token

### T013: Write Feature Tests for Profile API
- **What**: Pest feature tests for all profile endpoints
- **Tests**:
  - GET /api/profile returns 200 + current user JSON
  - PATCH /api/profile with valid data returns 200
  - PATCH /api/profile with invalid data returns 422 + error messages
  - DELETE /api/profile with password confirmation works
  - Unauthorized requests return 401
- **File**: `tests/Feature/ProfileApiTest.php`
- **Coverage**: 100% of ProfileController JSON methods

### T014: Write Feature Tests for Password OTP API
- **What**: Pest feature tests for password change OTP flow
- **Tests**:
  - POST /api/password/request-otp with correct password returns 200
  - POST /api/password/request-otp with wrong password returns 403
  - POST /api/password/verify-otp with valid OTP returns 200
  - POST /api/password/verify-otp with invalid OTP returns 422
  - PUT /api/password/reset with valid OTP + matching passwords resets password
  - After password change, OTHER sessions logged out (verify via multiple auth tokens)
  - Max 3 OTP attempts enforced; cooldown timer works
- **File**: `tests/Feature/PasswordChangeOtpApiTest.php`
- **Coverage**: 100% of PasswordChangeController methods

---

## PHASE 4: Blade View Enhancements with Alpine.js (8 Tasks)

**Goal**: Update existing Blade views with Alpine.js AJAX interactivity (replace form reloads).

### T015: Create Alpine.js Profile Form Handler (resources/js/profile-form.js)
- **What**: Alpine.js module managing profile form state, AJAX calls, error handling
- **Features**:
  - x-data binding for form fields (name, email, phone, language, timezone, notifications_enabled)
  - @submit.prevent handling → PATCH /api/profile via fetch
  - Error message display (validation errors + general errors)
  - Loading state (disable submit button during request)
  - Success message + toast notification
  - Debounced field updates (optional for real-time validation)
- **File**: `resources/js/profile-form.js`
- **Export**: `function profileFormHandler() { return { ... } }`

### T016: Create Alpine.js Theme Preferences Handler (resources/js/theme-preferences.js)
- **What**: Alpine.js module for theme + language + timezone + notifications forms
- **Features**:
  - Save on blur (not on submit) for quicker UX
  - PATCH /api/profile for each field change
  - Theme: Apply CSS class to documentElement immediately (no page reload)
  - Language: Store in localStorage; apply on reload (full page reload needed for translations)
  - Timezone: Just save to DB (no immediate UI change)
  - Notifications: Toggle switch with save
- **File**: `resources/js/theme-preferences.js`
- **Export**: `function themePreferencesHandler() { return { ... } }`

### T017: Update Profile Blade View (resources/views/profile/edit.blade.php)
- **What**: Enhance existing profile view with Alpine.js interactivity + new preference fields
- **Changes**:
  - Replace <form> with <form x-data="profileFormHandler()" @submit.prevent>
  - Add loading spinner + success message (Alpine x-show)
  - Wrap theme/language/timezone/notifications fields in new Blade partial
  - Update PATCH /profile form action to use Alpine (or keep as fallback)
  - Add error message displays for each field
- **File**: `resources/views/profile/edit.blade.php`
- **Test**: Form submits via AJAX; errors display; theme changes instantly

### T018: Create Blade Partial for Theme/Language/Timezone Preferences (resources/views/profile/partials/preferences-form.blade.php)
- **What**: New Blade partial for theme + language + timezone + notifications form
- **Fields**:
  - Theme selector (radio buttons: Light / Dark / System)
  - Language selector (radio/select: Español / English)
  - Timezone input (Alpine async select with options)
  - Notifications toggle (checkbox)
- **Alpine**: x-data="themePreferencesHandler()", @change handlers
- **File**: `resources/views/profile/partials/preferences-form.blade.php`

### T019: Update Auth Login Form with Alpine.js (resources/views/auth/login.blade.php)
- **What**: Add localStorage form state persistence (email field only)
- **Features**:
  - On page load: restore email from localStorage.getItem('login_form_state')
  - On input: localStorage.setItem('login_form_state', JSON.stringify({email}))
  - Clear localStorage on successful login (via SessionCreated event or form redirect)
- **File**: `resources/views/auth/login.blade.php`
- **Alpine**: Simple x-data="{ email: localStorage.getItem('login_form_state')?.email || '' }" and @input handlers

### T020: Update Auth Register Form with Alpine.js (resources/views/auth/register.blade.php)
- **What**: Add localStorage form state persistence for multi-step wizard
- **Features**: Persist company_name, email, phone between steps; restore on page reload
- **File**: `resources/views/auth/register.blade.php`
- **Alpine**: x-data with localStorage getItem/setItem on each step

### T021: Update Auth Reset Password Form (resources/views/auth/reset-password.blade.php)
- **What**: Add better error message display for validation errors
- **Changes**: Ensure error bag displays clearly; show strength meter for new password if Alpine adds it
- **File**: `resources/views/auth/reset-password.blade.php`

### T022: Verify Blade Components Are Using CSS Variables Correctly
- **What**: Audit all Blade components used in auth/profile screens for proper CSS variable usage
- **Check**: <x-input-error>, <x-form-label>, <x-button> use var(--primary), var(--text-main), var(--danger), etc.
- **Files**: `resources/views/components/` (all component files)
- **Test**: Render a page; inspect styles in DevTools; colors match constitution.md variables

---

## PHASE 5: Password Change OTP Modal (6 Tasks)

**Goal**: Create 3-step password change modal using existing OTP infrastructure.

### T023: Create Password Change Modal Blade Partial (resources/views/profile/partials/password-change-modal.blade.php)
- **What**: New Blade partial with 3-step modal (request OTP → verify OTP → reset password)
- **Structure**:
  - Step 1: Enter current password + confirm button → POST /api/password/request-otp
  - Step 2: Enter OTP from email + verify button → POST /api/password/verify-otp
    - Display countdown timer (10 minutes)
    - "Resend OTP" button (disabled until cooldown expires)
  - Step 3: Enter new password + confirm password + confirm button → PUT /api/password/reset
  - Error messages per step
  - Success message + redirect to dashboard
- **File**: `resources/views/profile/partials/password-change-modal.blade.php`
- **Alpine**: x-show conditions for step visibility

### T024: Create Alpine.js Password Change Modal Handler (resources/js/password-change-otp.js)
- **What**: Alpine.js module managing 3-step modal state, OTP countdown timer, API calls
- **Features**:
  - Step navigation (next, back buttons)
  - OTP countdown timer (10 min → 0 min)
  - "Resend OTP" button disabled during cooldown (5 min after request)
  - Error state per step
  - API calls: request-otp, verify-otp, reset-password
  - Success: show "Password changed!" message; redirect after 2 seconds
  - Handle max attempts exceeded (3 OTP attempts) → show error; require request new OTP
- **File**: `resources/js/password-change-otp.js`
- **Export**: `function passwordChangeModalHandler() { return { ... } }`

### T025: Wire Password Change Modal Button to Profile View
- **What**: Add "Change Password" button to profile view; x-show modal on click
- **Changes**: Add button in profile edit.blade.php; initialize modal component
- **File**: `resources/views/profile/edit.blade.php`
- **Test**: Button visible; clicking opens modal; modal steps work

### T026: Test OTP Email Delivery (Integration Test)
- **What**: Pest feature test confirming OTP email sent during password change
- **Test**: POST /api/password/request-otp triggers email (mock Resend); email contains 6-digit OTP code
- **File**: `tests/Feature/PasswordChangeOtpEmailTest.php`
- **Mock**: Use `Mail::fake()` or `Resend` mock to verify email sent

### T027: Test Session Invalidation After Password Change
- **What**: Pest feature test confirming OTHER devices logged out after password change
- **Flow**: Create 2 auth tokens for same user → change password with token1 → verify token1 still works; token2 now invalid
- **File**: `tests/Feature/PasswordChangeSessionInvalidationTest.php`
- **Verify**: Current session stays active; OTHER sessions logged out

### T028: Write e2e Test for Password Change Modal (Browser Test)
- **What**: Test full 3-step password change via Dusk or Playwright
- **Flow**: Open modal → enter current password → copy OTP from email → enter OTP → enter new password → confirm → success
- **File**: `tests/Browser/PasswordChangeOtpTest.php` (if using Dusk)
- **Note**: Optional if budget limited; can defer to manual testing

---

## PHASE 6: Preferences Management UI (4 Tasks) — OPTIONAL

**Goal**: Create UI for user preferences (language, timezone, notifications).

### T029: Create Preferences Form in Profile (Already Done in T018)
- **What**: Update profile/edit.blade.php to include preferences partial
- **Status**: ✓ Completed in T018

### T030: Write Feature Tests for Preferences API
- **What**: Pest feature tests for each preference field
- **Tests**:
  - PATCH /api/profile with theme_preference='dark' → persists to DB
  - PATCH /api/profile with language='en' → persists to DB
  - PATCH /api/profile with timezone='America/New_York' → persists to DB
  - PATCH /api/profile with notifications_enabled=false → persists to DB
  - User model casts work correctly (enum, bool, etc.)
- **File**: `tests/Feature/UserPreferencesApiTest.php`

### T031: Test Preferences Persist on Page Reload
- **What**: Pest feature test confirming preferences apply on reload
- **Flow**: Set language='en' → reload page → verify language persists in UI; locale translations apply
- **File**: `tests/Feature/UserPreferencesPersistenceTest.php`

### T032: Add Timezone Validation Rule
- **What**: Create or use Laravel's timezone validation rule
- **Where**: UpdateProfileRequest validation for timezone field
- **Test**: Invalid timezone rejected; valid timezones accepted

---

## PHASE 7: Final Testing & Documentation (6 Tasks)

**Goal**: Comprehensive testing, error handling, localization, documentation.

### T033: Add Comprehensive Error Handling
- **What**: Catch + handle edge cases across all new endpoints/views
- **Cases**:
  - User tries to change password with wrong current password → 403 error
  - OTP expired before verification → 422 error + resend option
  - Max 3 OTP attempts exceeded → 422 error + must request new OTP
  - Session invalidation race condition (multiple password change requests) → handled gracefully
  - Database failures → 500 error with user-friendly message
- **File**: Update controllers + services with try-catch
- **Test**: All error paths covered

### T034: Add Spanish/English Localization for New Features
- **What**: Add all new error messages, success messages, form labels to lang files
- **Files**:
  - `lang/es/profile.php` → Spanish messages (default)
  - `lang/en/profile.php` → English messages (fallback)
- **Content**: All modal steps, error messages, button labels, placeholder texts
- **Test**: Change language preference → UI updates with correct locale

### T035: Write Unit Tests for Services (ProfileService, PasswordChangeOtpService)
- **What**: Pest unit tests for service business logic (no DB)
- **Tests**:
  - ProfileService::getProfile() returns correct user data
  - ProfileService::updateProfile() validates input before update
  - PasswordChangeOtpService::requestOtp() generates token + sends email
  - PasswordChangeOtpService::resetPassword() hashes password correctly
- **File**: `tests/Unit/ProfileServiceTest.php`, `tests/Unit/PasswordChangeOtpServiceTest.php`
- **Coverage**: 100%

### T036: Run Full Test Suite + Check Coverage
- **What**: Execute entire test suite; verify 80%+ code coverage on new code
- **Command**: `php artisan test --coverage`
- **Target**: 80%+ coverage on ProfileController, PasswordChangeController, services
- **Report**: Document coverage in implementation guide

### T037: Run Code Quality Checks
- **What**: Run PHPStan (level 9) + Laravel Pint formatter
- **Commands**:
  - `./vendor/bin/phpstan analyse --level 9 app/Modules/Profile app/Modules/Auth`
  - `./vendor/bin/pint app/Modules/Profile app/Modules/Auth`
- **Fix**: All errors resolved; no warnings

### T038: Write Implementation Guide + API Documentation
- **What**: Create final documentation
- **Content**:
  - **IMPLEMENTATION_GUIDE.md**: Step-by-step what was built (8 phases, 38 tasks, what exists vs. what was added)
  - **API.md**: JSON endpoints + request/response examples for /api/profile, /api/password/*
  - **ARCHITECTURE.md**: Blade + Alpine.js patterns used (form handlers, x-data, fetch patterns, etc.)
  - **TROUBLESHOOTING.md**: Common issues + fixes (OTP not sending, password reset fails, etc.)
- **Files**: `specs/018-auth-ui-and-profile/IMPLEMENTATION_GUIDE.md`, etc.

---

## Task Execution Flow & Dependencies

### Critical Path (Minimum to Complete MVP)

```
Phase 1 (Code Audit: 3 tasks, verify existing)
    ↓
Phase 2 (Migrations: 3 tasks, CONDITIONAL — skip if fields exist)
    ↓
Phase 3 (Backend APIs: 8 tasks, JSON endpoints)
    ↓
Phase 4 (Blade Enhancements: 8 tasks, Alpine.js AJAX)
    ↓
Phase 5 (Password OTP Modal: 6 tasks, 3-step modal)
    ↓
Phase 7 (Testing & Docs: 6 tasks, final QA)
```

### Parallel Opportunities

- **Phase 1**: T001–T003 all parallelizable
- **Phase 2** (if needed): T004–T006 parallelizable
- **Phase 3**: T007–T008 parallel (controllers); T009–T011 parallel (form requests); T013–T014 parallel (tests)
- **Phase 4**: T015–T016 parallel (Alpine handlers); T017–T020 parallel (Blade view updates)
- **Phase 5**: T024–T025 parallel; T026–T027 parallel
- **Phase 7**: T033–T037 parallel

### Team Sizing

- **1 Person (Solo)**: Sequential execution of 7 phases = **1–2 days MVP** (Phase 1–5 only; Phase 6 post-MVP)
- **2 People**:
  - Person A: Phase 1–2, Phase 3 (Backend)
  - Person B: Phase 4 (Blade) + wiring Phase 5
  - Both: Phase 5 + Phase 7
  - Timeline: **1–1.5 days MVP**
- **3+ People**: Full parallelization = **0.5–1 day MVP**

---

## Testing & Quality Gates

### Unit Test Coverage (Phase 3, 7)
- ProfileService, PasswordChangeOtpService: 100% method coverage (TDD)
- Form request validation classes: 100% rule coverage
- Target: 80%+ overall code coverage

### Feature Test Coverage (Phase 3, 5, 7)
- Profile endpoints: show (GET), update (PATCH), delete (DELETE) — happy path + error cases
- Password change OTP: request (POST), verify (POST), reset (PUT) — all 3 steps + error cases
- Session invalidation: OTHER devices logged out; current device stays active
- Email delivery: OTP sent, contains correct code, respects cooldown

### Acceptance Tests (Phase 4–5, 7) — Blade + Browser
- US1: Navigate login ↔ without reload; form state persists
- US2: Theme toggle works; persists across sessions ✓ **Already works**
- US3: Profile displays + inline edit works + changes persist
- US4: Password change 3-step modal: request → verify → reset → session invalidate OTHER devices

### Performance Benchmarks (Phase 7)
- Profile page load: <1.5s (server-side Blade rendering)
- Theme toggle: <100ms (localStorage + CSS class)
- OTP email delivery: <30s (existing Resend integration)
- API response times: <500ms (profile, password endpoints)

---

## Implementation Notes & Best Practices

### Architecture Reminder
- **Backend**: Laravel API endpoints return JSON
- **Frontend**: Blade templates + Alpine.js (NOT Vue)
- **State**: Alpine.js x-data for component state; localStorage for persistence
- **Styling**: Tailwind + CSS variables from constitution.md
- **Reuse**: Adapt existing EmailVerificationService; use existing Blade components

### TDD Workflow (Per Task)
1. Write failing test (Red)
2. Implement minimal code (Green)
3. Refactor for clarity (Refactor)
4. Verify test still passes

### Alpine.js Patterns (Used Throughout)
```javascript
// Form submission via AJAX
<form @submit.prevent="submitForm">

// Conditional rendering
<div x-show="editing">...</div>
<div x-show="!editing">...</div>

// API calls
fetch('/api/profile', { method: 'PATCH', body: JSON.stringify(data) })

// localStorage
localStorage.setItem('key', JSON.stringify(value))
localStorage.getItem('key')

// Loading states
<button :disabled="loading">{{ loading ? 'Guardando...' : 'Guardar' }}</button>
```

### Security Considerations
- CSRF protection: X-CSRF-TOKEN header in AJAX requests
- Current password verified before OTP request
- OTP tokens hashed before storage
- Max 3 verification attempts; 10-min TTL
- Session invalidation: OTHER devices only (not current)
- No passwords/OTPs in logs

### Database Safety
- Migrations: Add-only (no column drops)
- Backfill defaults for existing users
- No breaking changes to existing schema
- Cascade deletes for related OTP tokens

---

## Sign-Off Criteria

### MVP (Phase 1–5: US1 + US2 + US3 + US4)
- [ ] All existing infrastructure verified ✓
- [ ] Migrations conditionally applied (if fields missing)
- [ ] All 6 API endpoints functional + tested
- [ ] All Blade views updated with Alpine.js + working
- [ ] Password OTP modal complete + 3-step flow works
- [ ] Session invalidation working (OTHER devices only)
- [ ] 80%+ test coverage
- [ ] No critical bugs
- [ ] All tests passing
- [ ] Deployment ready

### Post-MVP Optional (Phase 6: US5)
- [ ] User preferences UI complete
- [ ] Preferences persist + apply on reload
- [ ] Localization complete (ES/EN)

---

**Last Updated**: 2026-06-25 | **Total Tasks**: 38 (reduced from 71) | **Estimated MVP**: 1–2 days solo dev | **Architecture**: Blade + Alpine.js + JSON API
