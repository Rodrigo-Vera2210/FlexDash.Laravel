# Implementation Plan: Enhanced Authentication UI & User Profile Management

**Branch**: `018-auth-ui-and-profile` | **Date**: 2026-06-25 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from feature branch; Design artifacts generated via TDD workflow.

---

## Summary

Users need an enhanced authentication and profile management experience with three core improvements:

1. **Seamless Authentication Flow**: Navigation between login/registration screens with form state persistence and back button
2. **Persistent Theme Preference**: Light/dark/system theme toggle accessible on all pages, with user preference storage
3. **User Profile Management**: Dedicated profile section for viewing/editing personal information, managing preferences, and securely changing passwords with OTP verification

This feature improves user experience (P1 priority), addresses accessibility needs (theme toggle), and enhances security (OTP-based password reset).

---

## Technical Context

**Language/Version**: PHP 8.2+ (Laravel 12.x)

**Primary Dependencies**:
- Laravel Framework 12.x
- Tailwind CSS 4.3.0 (frontend styling)
- Resend ^1.4 (email delivery)
- Vue 3 (assumed frontend framework)
- Spatie Laravel Permission 6.25+ (RBAC)

**Storage**: SQLite (primary); PostgreSQL (production)

**Testing**: PHPUnit 11.5+ with Pest; TDD-first approach mandatory

**Target Platform**: Web application (responsive, mobile-first)

**Project Type**: Laravel monolithic POS system with multi-tenant architecture

**Performance Goals**:
- Theme toggle response: <100ms (CSS class manipulation)
- Profile page load: <1.5s (authenticated users)
- OTP delivery: <30s (Resend SLA)
- Profile edits persist: <2s after "Save"
- OTP expiration window: 10 minutes

**Constraints**:
- OTP: 3 attempts max; 10-minute TTL
- Password reset: Invalidates all active sessions
- Theme preference: Must respect system preference when set to 'system'
- Form state: Cannot persist sensitive data (passwords, OTPs)
- Email: Must use Resend (configured in project)

**Scale/Scope**:
- Initial: Single user profile per session
- Future: Bulk user management (admin), audit logs, 2FA integration
- Multi-tenant: Profile isolated to authenticated user; company context optional

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Principle I: Test-Driven Development ✓
All service logic written via Red-Green-Refactor. Unit, feature, and acceptance tests cover validation, OTP generation, API endpoints, and user journeys.

### Principle II: Layered Architecture ✓
Controllers → Services → Models with strict separation. No business logic in controllers; no direct DB queries in services.

### Principle III: Module-Based Backend ✓
Feature organized under `app/Modules/Auth/` with self-contained Controllers, Services, Tests.

### Principle IV: Clean Code ✓
Self-documenting names, max 30-line methods, single responsibility, DRY principles enforced.

### Principle V: Technology Stack ✓
Laravel 12.x, Tailwind CSS, SQLite, PHPUnit, Git branches, Markdown docs.

### Principle VI: JWT Authentication ✓
All profile/password endpoints require JWT Bearer token validation before reaching services.

### Principle VII: Ecuador Localization ✓
Spanish-first UI, timezone default America/Guayaquil, localized error messages.

**Status**: ✓ **ALL PRINCIPLES COMPLIANT**

---

## Project Structure

### Documentation (this feature)

```text
specs/018-auth-ui-and-profile/
├── plan.md              # This file (implementation plan)
├── research.md          # Phase 0: Clarifications & design decisions ✓
├── data-model.md        # Phase 1: Entity definitions & migrations ✓
├── quickstart.md        # Phase 1: Step-by-step implementation ✓
├── contracts/
│   └── api.md           # Phase 1: API endpoint specifications ✓
└── tasks.md             # Phase 2: Task breakdown (not yet generated)
```

### Source Code (repository root)

```text
app/Modules/Auth/
├── Controllers/
│   ├── ProfileController.php
│   └── PasswordChangeController.php
├── Services/
│   ├── ProfileService.php
│   └── OtpService.php
├── Requests/
│   ├── UpdateProfileRequest.php
│   ├── RequestPasswordOtpRequest.php
│   ├── VerifyOtpRequest.php
│   └── ResetPasswordRequest.php
├── Resources/
│   ├── UserResource.php
│   └── OtpTokenResource.php
└── Tests/
    ├── Feature/
    │   ├── ProfileControllerTest.php
    │   └── PasswordChangeControllerTest.php
    ├── Unit/
    │   ├── ProfileServiceTest.php
    │   └── OtpServiceTest.php
    └── Acceptance/
        ├── UserCanViewProfileTest.php
        └── UserCanChangePasswordTest.php

app/Models/
├── User.php (updated with relationships + casts)
└── OtpToken.php (new)

database/migrations/
├── 2026_06_25_add_user_preferences.php
└── 2026_06_25_create_otp_tokens_table.php

resources/js/
├── components/
│   └── ThemeToggle.vue
├── pages/
│   ├── Profile.vue
│   └── PasswordChange.vue
└── stores/
    └── theme.ts (Pinia store)

routes/
└── api.php (7 new profile & password endpoints)

lang/
├── es/ & en/
└── messages.php (auth localization)
```

**Structure Decision**: Single monolithic Laravel application; Auth module feature-based and self-contained. Centralized authentication with modular structure for future extractions.

---

## Complexity Tracking

*No violations requiring justification.*

All architectural decisions align with Constitution principles. Design is compliant and ready for implementation.

---

## Design Artifacts

### Phase 0: Research ✓

File: [research.md](research.md)

**5 Clarifications Resolved**:
1. Session invalidation after password change → YES
2. Priority preferences → Language (P1), Timezone (P2), Notifications (P2)
3. OTP delivery failures → Resend + retries + user guidance
4. Concurrent OTP requests → Last-issued-token-wins
5. Form state persistence → localStorage (non-sensitive data only)

---

### Phase 1: Data Model ✓

File: [data-model.md](data-model.md)

**Entities**:
- User (updated): +theme_preference, language, timezone, notifications_enabled
- OtpToken (new): Manages 10-min TTL OTP tokens with 3-attempt limit

**Migrations**: 2 migrations prepared and ready to run

**Relationships**: User ↔ OtpToken (1:many)

---

### Phase 1: Contracts ✓

File: [contracts/api.md](contracts/api.md)

**7 API Endpoints** fully specified with request/response format, validation rules, error cases, and security constraints.

**3 Component Contracts** defined for ProfilePage, ThemeToggle, Navigation.

---

### Phase 1: Quickstart ✓

File: [quickstart.md](quickstart.md)

**Implementation Path**: 14.5-hour roadmap with:
- Database setup & models (45 min)
- ProfileService TDD (2h)
- OtpService TDD (2h)
- Controllers & routes (1h)
- Frontend components (2.5h)
- Testing & QA (2h)

---

## Execution Gates

### Pre-Phase 1: Constitution Compliance

✓ **PASSED**: All 7 principles verified compliant. No exceptions or workarounds.

### Post-Phase 1: Design Review

✓ **PASSED**: All artifacts complete and validated.

**Ready for**: Phase 2 task generation via `/speckit.tasks` command

---

## References

- [Original Specification](spec.md)
- [Research & Design](research.md)
- [Data Model](data-model.md)
- [API Contracts](contracts/api.md)
- [Implementation Guide](quickstart.md)
- [Constitution](../../.specify/memory/constitution.md)
