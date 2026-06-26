# Tasks: Enhanced Authentication UI & User Profile Management

**Feature**: 018-auth-ui-and-profile | **Branch**: `018-auth-ui-and-profile` | **Status**: Ready for Implementation

**Feature Scope**: Profile UI improvements, theme toggle accessibility, password change with OTP reuse, and preferences management.

**Test Approach**: TDD-First (Red-Green-Refactor); leverage existing Auth/Registration OTP infrastructure to avoid duplication.

**Key Constraint**: Auth module, Registration module, and OTP system already functional. Focus on **integration and UI enhancement**, not rebuilding existing features.

---

## Overview: Execution Strategy

### User Story Prioritization & Dependencies

| Story | Title | Priority | Dependencies | MVP Scope |
|-------|-------|----------|--------------|-----------|
| US1 | Navigation Between Auth Screens | P1 | Existing auth routes | ✓ Yes |
| US2 | Theme Toggle Always Accessible | P1 | Theme migration (optional) | ✓ Yes |
| US3 | View & Edit Personal Information | P1 | Profile controller enhancements | ✓ Yes |
| US4 | Change Password with OTP Verification | P2 | Reuse EmailVerificationService | Optional |
| US5 | Additional Profile Settings | P2 | User model extensions | Optional |

### Parallel Execution Opportunities

- **Phase 1 (Code Audit)**: T001–T003 (understand existing structure) — parallelizable
- **Phase 2 (Data Model Updates)**: T004–T008 (add missing fields) — parallelizable
- **Phase 3 (Backend Integration)**: T009–T020 (controllers, services, validation) — mostly sequential (Phase 2 → Phase 3)
- **Phase 4 (Frontend UI)**: T021–T035 (Vue components, stores, pages) — parallelizable after Phase 3
- **Phase 5 (OTP Integration)**: T036–T047 (password change modal, OTP reuse) — sequential (Phase 4 → Phase 5)
- **Phase 6 (Navigation & Polish)**: T048–T065 (wire components, testing, docs) — parallelizable

### Independent Test Criteria (Per User Story)

- **US1**: User navigates login ↔ registration without page reload; form state persists in localStorage
- **US2**: Theme toggle visible on all pages; persists to localStorage; applies instantly (<100ms)
- **US3**: Profile page displays all user info; inline edit with pencil icon; save/cancel inline
- **US4**: Password change requires OTP (reuse existing EmailVerificationService); session invalidated on OTHER devices
- **US5**: Language/timezone/notifications persist per user; apply on reload

### MVP Scope (Recommended Start)

**Phase 1–4 + Phase 6 = US1 + US2 + US3**
- Estimated effort: **2–3 days** (vs 7–10 days with duplication)
- All core profile and theme features working
- Deferred: Password change with OTP (US4), advanced preferences (US5)


## Phase 1: Code Audit & Existing Infrastructure Analysis

**Goal**: Understand existing Auth, Registration, and OTP implementations to identify what to reuse vs. what to build.

**Test Criteria**: Clear mapping of existing code; documented dependencies; identified gaps.

### T001–T003: Infrastructure Audit

- [ ] T001 [P] Audit `app/Modules/Auth/` structure: document existing Controllers (AuthController, PasswordController, etc.), Services, Requests, Models, Notifications; identify what can be reused for profile management
- [ ] T002 [P] Audit `app/Modules/Registration/` structure: document EmailVerificationService (OTP generation, validation, resend), EmailVerification model, verify it can be reused for password change OTP; check OTP field mappings (purpose, attempts, expires_at)
- [ ] T003 [P] Audit `resources/views/profile/` and `resources/views/auth/`: identify existing profile page, login/register Blade templates; document current state and required improvements

---

## Phase 2: Data Model Updates (Optional Extensions)

**Goal**: Add missing user preference fields if not already present; verify migrations are clean.

**Test Criteria**: Migrations run without errors; User model has new fields; no data loss on existing users.

### T004–T008: User Preference Fields

- [ ] T004 Check `app/Models/User.php` for existing fields: theme_preference, language, timezone, notifications_enabled; if missing, create migration
- [ ] T005 [P] If needed, create migration `database/migrations/2026_06_25_add_user_preferences.php` to add theme_preference (enum: light|dark|system), language, timezone, notifications_enabled; backfill defaults for existing users
- [ ] T006 Run migration: `php artisan migrate` and verify schema
- [ ] T007 Update `app/Models/User.php` to add fillable fields, $casts, and accessors for new preference fields
- [ ] T008 [P] Create test `tests/Unit/Models/UserPreferencesTest.php` to verify new fields are correctly cast and accessible

---

## Phase 3: Backend Integration (Controllers & Services)

**Goal**: Extend existing Auth controllers with profile endpoints and password change logic.

**Test Criteria**: All endpoints respond with correct data; validation works; OTP integration verified.

### T009–T020: Profile & Password Controllers

- [ ] T009 [P] Extend or create `app/Modules/Auth/Controllers/ProfileController.php` with methods: show() [GET /api/profile], update() [PUT /api/profile] (reuse validation from existing requests if available)
- [ ] T010 [P] Extend or create `app/Modules/Auth/Controllers/PasswordController.php` with methods: requestOtp() [POST /api/profile/password/request-otp], verifyOtp() [POST /api/profile/password/verify-otp], reset() [POST /api/profile/password/reset]
- [ ] T011 Create or update `app/Modules/Auth/Requests/UpdateProfileRequest.php` with validation for name, email, phone (reuse existing rules if available)
- [ ] T012 Create `app/Modules/Auth/Requests/RequestPasswordOtpRequest.php` with validation for current_password
- [ ] T013 Create `app/Modules/Auth/Requests/VerifyPasswordOtpRequest.php` with validation for otp_code
- [ ] T014 Create `app/Modules/Auth/Requests/ResetPasswordRequest.php` with validation for new_password (min 12 chars, complexity rules)
- [ ] T015 [P] Create or extend `app/Modules/Auth/Services/ProfileService.php` with methods: getProfile(), updateProfile(); add method updatePassword() if not in existing PasswordService
- [ ] T016 [P] Create `app/Modules/Auth/Services/PasswordChangeOtpService.php` to bridge existing EmailVerificationService (from Registration) with password change flow; reuse OTP generation/validation logic
- [ ] T017 Create test `tests/Feature/ProfileControllerTest.php` for GET/PUT /api/profile endpoints with happy path + validation errors
- [ ] T018 Create test `tests/Feature/PasswordChangeControllerTest.php` for OTP request/verify/reset endpoints
- [ ] T019 Register routes in `routes/api.php`: GET /api/profile, PUT /api/profile, POST /api/profile/password/request-otp, POST /api/profile/password/verify-otp, POST /api/profile/password/reset (all behind auth middleware)
- [ ] T020 [P] Create test `tests/Integration/ProfilePasswordChangeWorkflowTest.php` covering full flow: request OTP → verify → change password → session invalidated

---

## Phase 4: Frontend UI Components (Vue + Pinia)

**Goal**: Create theme toggle, profile page, and necessary composables/stores.

**Test Criteria**: Components render correctly; Pinia store updates properly; localStorage persistence works.

### T021–T035: Vue Components & Stores

- [ ] T021 [P] Create Pinia store `resources/js/stores/theme.ts` with state: currentTheme (light|dark|system), actions: setTheme(), loadPreference(), applyTheme(), getSystemPreference(); persist to localStorage
- [ ] T022 [P] Create Vue component `resources/js/components/Theme/ThemeToggle.vue`: icon button in top-right, toggle between light/dark/system, emit theme-change event
- [ ] T023 [P] Create utility file `resources/js/utils/theme.ts`: detectSystemTheme(), applyThemeToDOM() (add/remove dark class), saveThemeToStorage(), loadThemeFromStorage()
- [ ] T024 Update or create main app layout `resources/js/layouts/AppLayout.vue` to include ThemeToggle in top-right corner; ensure visible on all authenticated pages
- [ ] T025 Update or create auth layout to include ThemeToggle; ensure visible on login/register pages
- [ ] T026 [P] Create Vue page component `resources/js/pages/Profile/ProfilePage.vue`: display profile info + inline edit form + preferences section
- [ ] T027 [P] Create Vue component `resources/js/components/Profile/ProfileHeader.vue`: user avatar, name, company; button to enter edit mode
- [ ] T028 [P] Create Vue component `resources/js/components/Profile/ProfileEditForm.vue`: inline form fields (name, email, phone); save/cancel buttons; validation feedback
- [ ] T029 [P] Create Vue composable `resources/js/composables/useProfileForm.ts`: form state, validation logic, API calls (getProfile, updateProfile)
- [ ] T030 Create API client `resources/js/api/profileApi.ts` with methods: getProfile(), updateProfile(); error handling
- [ ] T031 Create Vue component `resources/js/components/Navigation/UserMenu.vue`: dropdown from user button (name shown in bottom-left); links to profile, preferences, logout
- [ ] T032 Update app shell/layout to include UserMenu button in bottom-left corner; click opens dropdown
- [ ] T033 [P] Create test file `tests/Unit/stores/ThemeStoreTest.ts` (Vitest) to verify Pinia store actions and persistence
- [ ] T034 Create Cypress e2e test `tests/e2e/theme-toggle.spec.ts`: toggle theme, reload, verify persistence
- [ ] T035 Create Cypress e2e test `tests/e2e/profile-view-edit.spec.ts`: load profile, edit field inline, save, verify change

---

## Phase 5: Password Change with OTP Modal

**Goal**: Create password change modal that reuses existing OTP infrastructure.

**Test Criteria**: Modal displays correctly; OTP steps work; password change completes; session invalidated on OTHER devices.

### T036–T047: Password Change Modal

- [ ] T036 [P] Create Vue component `resources/js/components/Profile/PasswordChangeModal.vue`: 3-step modal (current password → OTP verification → new password)
- [ ] T037 Create Vue component `resources/js/components/Profile/PasswordStepCurrentPassword.vue`: form for current password with "Request OTP" button
- [ ] T038 Create Vue component `resources/js/components/Profile/PasswordStepOtpVerification.vue`: OTP input field, timer for 30s cooldown, "Resend" button (disabled during cooldown), error messages
- [ ] T039 Create Vue component `resources/js/components/Profile/PasswordStepNewPassword.vue`: new password input, confirm password, password strength indicator; save button
- [ ] T040 Create Vue composable `resources/js/composables/usePasswordChange.ts`: handle 3-step flow, manage OTP resend cooldown (30s), API calls
- [ ] T041 Create API client `resources/js/api/passwordApi.ts` with methods: requestPasswordOtp(current_password), verifyPasswordOtp(otp_code), resetPassword(new_password); handle errors and cooldown
- [ ] T042 Add "Change Password" button/link to ProfilePage or ProfileHeader; click opens PasswordChangeModal
- [ ] T043 Backend: Implement PasswordController.requestOtp() to validate current password, call PasswordChangeOtpService.generateOtp(), return success (OTP sent, no code in response)
- [ ] T044 Backend: Implement PasswordController.verifyOtp() to validate OTP via PasswordChangeOtpService.validateOtp(), return token/confirmation
- [ ] T045 Backend: Implement PasswordController.reset() to update password, call logoutOtherDevices() (invalidate all sessions EXCEPT current), return success
- [ ] T046 Create test `tests/Feature/PasswordChangeOtpIntegrationTest.php`: full flow (request → verify → reset) with session check
- [ ] T047 Create Cypress e2e test `tests/e2e/password-change-otp.spec.ts`: request OTP, enter OTP, change password, verify current session persists, OTHER session logged out

---

## Phase 6: Navigation & User Menu Integration

**Goal**: Wire user button to profile, implement back navigation between auth screens.

**Test Criteria**: User menu displays; clicking profile navigates to profile page; back buttons work; form state persists.

### T048–T055: Navigation & Wiring

- [ ] T048 Update login/register Blade pages or Vue pages to include "Volver al Login" button (if using Blade); ensure navigation works
- [ ] T049 Create Vue composable `resources/js/composables/useAuthNavigation.ts` to handle login ↔ register navigation; store/load form state in localStorage
- [ ] T050 Update auth pages (Login.vue, Register.vue if Vue-based) to include back button; use useAuthNavigation to persist form state
- [ ] T051 Implement UserMenu button in app layout: click opens dropdown with profile, preferences, settings, logout options
- [ ] T052 Implement navigation from UserMenu → click "Perfil" → navigates to /profile or shows ProfilePage
- [ ] T053 Backend: Add route `GET /api/auth/user` to return current authenticated user (for frontend state initialization)
- [ ] T054 Frontend: On app mount, fetch current user and initialize theme preference from User.theme_preference (fallback to localStorage)
- [ ] T055 Create test `tests/Feature/UserMenuNavigationTest.php` to verify navigation endpoints return correct user data

---

## Phase 7: Preferences & Advanced Features (Optional)

**Goal**: Add language, timezone, notification settings.

**Test Criteria**: Preferences persist; apply on reload; UI reflects user choices.

### T056–T065: Preferences Management

- [ ] T056 [P] Create `app/Enums/Language.php` with values: es, en
- [ ] T057 [P] Create `app/Enums/Timezone.php` with IANA timezone list (America/Guayaquil as default for Ecuador)
- [ ] T058 Create `app/Modules/Auth/Requests/UpdatePreferencesRequest.php` with validation for language, timezone, notifications_enabled
- [ ] T059 Extend `app/Modules/Auth/Controllers/ProfileController.php` with method updatePreferences() [PUT /api/profile/preferences]
- [ ] T060 Extend `app/Modules/Auth/Services/ProfileService.php` with method updatePreferences()
- [ ] T061 Register route `PUT /api/profile/preferences` in `routes/api.php`
- [ ] T062 [P] Create Vue component `resources/js/components/Profile/PreferencesForm.vue`: language dropdown, timezone select, notifications toggle
- [ ] T063 Add PreferencesForm section to ProfilePage (tab or accordion)
- [ ] T064 Create API client method in `resources/js/api/profileApi.ts`: updatePreferences()
- [ ] T065 Create Cypress e2e test `tests/e2e/user-preferences.spec.ts`: change language, timezone, notifications, reload, verify persistence

---

## Phase 8: Testing, Documentation & Cleanup

**Goal**: Comprehensive testing, error handling, logging, documentation.

**Test Criteria**: All user stories covered; performance meets SLAs; docs complete; no critical bugs.

### T066–T080: QA & Documentation

- [ ] T066 [P] Add comprehensive error handling to all profile/password controllers; return localized error messages (Spanish/English)
- [ ] T067 [P] Create error handler `app/Modules/Auth/Exceptions/ProfileException.php` for profile-related errors
- [ ] T068 [P] Add logging to profile updates, password changes, OTP requests via `Log::info()` in services
- [ ] T069 Create test `tests/Integration/FullAuthFlowTest.php`: login → view profile → edit profile → change password → logout
- [ ] T070 [P] Create performance test `tests/Performance/ProfilePageLoadTest.php` verifying <1.5s load time
- [ ] T071 [P] Create performance test `tests/Performance/ThemeToggleTest.php` verifying <100ms toggle response
- [ ] T072 Update `lang/es/auth.php` with profile labels, validation messages, success messages (profile updated, password changed, etc.)
- [ ] T073 Update `lang/en/auth.php` with English translations
- [ ] T074 Create README in `specs/018-auth-ui-and-profile/` with feature summary, user stories, implementation notes
- [ ] T075 [P] Run PHPStan level 9: `vendor/bin/phpstan analyse app/Modules/Auth --level=9`
- [ ] T076 [P] Run Laravel Pint: `./vendor/bin/pint app/Modules/Auth`
- [ ] T077 [P] Run Pest test suite: `./vendor/bin/pest tests/Feature/Profile* tests/Feature/PasswordChange*` with coverage
- [ ] T078 Create Cypress test suite: `npm run test:e2e -- tests/e2e/profile-* tests/e2e/theme-* tests/e2e/password-*`
- [ ] T079 Create ACCEPTANCE_CHECKLIST.md: all user stories pass acceptance criteria, responsive on mobile/desktop, accessibility compliant (WCAG 2.1 AA)
- [ ] T080 Final code review: check for duplication, security issues, performance bottlenecks; sign-off on MVP scope

---

## Task Execution Flow & Dependencies

### Critical Path (Minimum to Complete MVP)

```
Phase 1 (Audit)
    ↓
Phase 2 (Data Model) [optional, if fields missing]
    ↓
Phase 3 (Backend) [MUST complete]
    ↓
Phase 4 (Frontend Components) [parallelizable]
    ↓
Phase 5 (Password Change Modal) [depends on Phase 4]
    ↓
Phase 6 (Navigation & Wiring) [parallelizable with Phase 5]
    ↓
Phase 8 (QA & Docs)
```

### Parallel Opportunities

**Within Phase 1**: T001–T003 all parallelizable (independent audits)

**Within Phase 3**: 
- T009–T014 (controllers + requests) can run in parallel
- T015–T016 (services) can run in parallel with T009–T014
- T017–T018 (tests) can run in parallel

**Within Phase 4**: 
- T021–T023 (Pinia store + utilities) parallelizable
- T024–T025 (layouts) parallelizable with T021–T023
- T026–T032 (components + composables) parallelizable
- T033–T035 (tests) run after components ready

**Between Phases 4–6**: PreferencesForm (T056–T065) can start once Phase 4 component patterns are established

**Phase 8 (Testing & Docs)**: Can start in parallel with Phase 5–6 for documentation

### Team Sizing Recommendation

- **1 Person (Solo Dev)**: Execute phases sequentially; MVP (Phase 1–6) = **2–3 days**
- **2 People**: One on backend (Phase 3), one on frontend (Phase 4); then both on integration (Phase 5–6) = **1.5–2 days**
- **3+ People**: Full parallelization (Audit + Backend + Frontend + Preferences concurrent) = **1 day**

---

## Testing & Quality Gates

### Unit Test Coverage (Phase 3–8)

- ProfileService, PasswordChangeOtpService methods (100% coverage)
- Pinia store actions (100% coverage)
- Validation request classes (100% coverage)
- Target: 80%+ overall code coverage

### Feature Test Coverage (Phase 3–8)

- Profile endpoints: GET, PUT (happy path + validation errors)
- Password change flow: request OTP, verify, reset (with session check)
- Theme persistence via localStorage
- Target: 80% feature test coverage

### Acceptance Test Coverage (Phase 4–8)

- US1: Navigation between auth screens + form state persistence
- US2: Theme toggle on all pages, persists across sessions
- US3: Profile displays all info, inline edit works, changes persist
- US4: Password change with OTP, session invalidated on OTHER devices
- US5: Preferences persist and apply on reload

### Performance Benchmarks (Phase 8)

- Profile page load: <1.5s ✓
- Theme toggle response: <100ms ✓
- OTP email delivery: <30s (reuse existing) ✓

---

## Implementation Notes & Best Practices

### Code Reuse Strategy
- **OTP System**: Reuse EmailVerificationService from Registration module; create bridge service (PasswordChangeOtpService) to adapt it for password change
- **Auth Controllers**: Extend existing AuthController/PasswordController rather than creating new ones
- **Validation**: Reuse existing request form classes where possible; extend only when needed
- **Models**: Extend User model with new preference fields; no new models needed

### TDD Workflow (Per Task)
1. Write failing test (Red) — test data/endpoint first
2. Implement minimal code to pass test (Green)
3. Refactor for clarity, DRY, security (Refactor)
4. Verify test still passes

### Security Considerations
- Current password verified before OTP request
- OTP tokens hashed (existing system)
- Session invalidation: log out OTHER devices only (current device stays active)
- No passwords in logs or error messages
- Email validation before OTP delivery

### Database Safety
- Migrations add new fields WITHOUT altering existing functionality
- Backfill defaults for existing users
- No dropping columns or breaking changes

---

## Sign-Off & Verification

### Phase Completion Checklist

- [ ] All tasks in phase completed and tested
- [ ] No critical bugs blocking next phase
- [ ] Code review approved
- [ ] Performance benchmarks met
- [ ] Documentation updated

### MVP Sign-Off Criteria (US1 + US2 + US3)

- [ ] User navigates login ↔ registration with form state persistence ✓
- [ ] Theme toggle visible on all pages; persists across sessions ✓
- [ ] Profile displays all user info; inline edit works ✓
- [ ] All validations work; error messages clear ✓
- [ ] Mobile and desktop responsive ✓
- [ ] Accessibility compliant (WCAG 2.1 AA) ✓
- [ ] 80%+ test coverage ✓
- [ ] No critical bugs ✓

### Full Feature Sign-Off (All 5 User Stories)

- [ ] All US1–US5 acceptance criteria met
- [ ] OTP workflow secure (reuses existing system)
- [ ] Session invalidation correct (OTHER devices only)
- [ ] Preferences persist and apply
- [ ] Performance SLAs met
- [ ] Documentation complete
- [ ] Ready for production

---

**Last Updated**: 2026-06-25 | **Estimated Duration**: 2–3 days (solo dev) | **Next Review**: After Phase 3 completion
