# Research: Auth UI & Profile Management Design Decisions

**Phase**: 0 (Pre-Design Research) | **Date**: 2026-06-25

## Clarifications Resolved

### C-001: Session Invalidation After Password Change

**Question**: Should user be logged out after password change?

**Decision**: YES - User MUST be logged out and forced to re-login after password change.

**Rationale**:

- Security best practice: invalidates all active sessions to prevent token hijacking
- Aligns with OAuth 2.0 and OWASP password change guidelines
- Prevents unauthorized access if current session was compromised
- Ensures user awareness of password change completion

**Alternatives Considered**:

- **Soft invalidation** (keep session, refresh token): Less secure; compromised old token still valid until natural expiration
- **Grace period** (5 min to re-login): Unnecessary UX friction without security benefit

**Implementation**: After password hash persists, call `auth()->logout()` and `Auth::guard()->logoutOtherDevices($user)` via Laravel Sanctum/native auth, then redirect to login with success message "Contraseña actualizada. Por favor inicie sesión nuevamente."

---

### C-002: Priority User Preferences

**Question**: Which additional preferences (language, timezone, notifications) are highest priority?

**Decision**: Priority ranking (P1 → P2):

1. **P1**: Language preference (es/en, expandable)
2. **P2**: Timezone (user's location-based preference)
3. **P2**: Notification opt-ins (email notifications)
4. **P3**: Delete account (deferred to future sprint)

**Rationale**:

- Language is critical for product localization (Ecuador-focused app, but international users possible)
- Timezone ensures accurate timestamp display and scheduled operations
- Notification settings align with GDPR/data privacy (users can opt-out)
- Account deletion can follow in later phase after profile stabilizes

**Implementation Strategy**:

- Add `language` (enum: es|en, default: es) to `User` model
- Add `timezone` (string, default: America/Guayaquil) to `User` model
- Add `notifications_enabled` (boolean, default: true) to `User` model or separate `UserPreferences` table
- Frontend: Language dropdown in profile settings; timezone picker (Moment.js/Temporal API)

---

### C-003: OTP Delivery & Resend Configuration

**Question**: How should system handle OTP email failures or non-delivery?

**Decision**: Graceful degradation with user guidance:

1. **Primary**: Send OTP via Resend (configured in `config/services.php`)
2. **Retry on failure**: 2 automatic retries with exponential backoff (2s, 5s)
3. **User-facing**: If delivery fails, show error: "No pudimos enviar el código. Intenta nuevamente o contacta soporte."
4. **Resend OTP button**: Allow user to manually request new OTP (rate-limited to 3 attempts per 10 minutes)
5. **Monitoring**: Log all OTP failures to application logs for support investigation

**Rationale**:

- Resend is already integrated (composer.json shows `resend/resend-laravel`)
- Retries handle transient failures without user friction
- Rate-limiting prevents brute-force/spam
- Transparent error messaging improves UX

**Dependencies**:

- Resend API key configured in `.env`
- Queue for async email delivery (recommended via `queue.php` config)

---

### C-004: OTP Token Management Strategy

**Question**: How should concurrent OTP requests be handled?

**Decision**: Last-issued-token-wins strategy with explicit previous-token invalidation.

**Rationale**:

- If user requests new OTP while previous is still valid, invalidate the old token immediately
- Prevents confusion (user receives 2 OTPs, tries first one, finds it expired)
- Aligns with security best practice: only one active OTP per user per purpose at any time

**Implementation**:

- When new OTP is generated: `OtpToken::where('user_id', $userId)->where('purpose', 'password_change')->where('used_at', null)->update(['used_at' => now()])`
- Generate fresh token with new expiration (10 minutes from now)
- Notify user in UI: "Enviamos un nuevo código. El anterior ha sido desactivado."

---

### C-005: Form State Persistence Strategy

**Question**: How should form state be preserved when navigating between login/registration?

**Decision**: Browser-based with localStorage + session-aware validation.

**Rationale**:

- localStorage persists across browser close/reopen
- Encrypted at rest by browser; decrypted only on same origin
- Recovers user intent even after extended absence
- Simple, no server-side session complexity

**Implementation**:

- Store form data in `localStorage` with prefix: `flexdash_auth_login`, `flexdash_auth_register`
- Clear localStorage after successful login/registration
- On page load: check localStorage, populate form if data exists
- **Data to persist**: email, phone, language preference (NOT password, NOT OTP tokens)
- Client-side validation on form change to prevent stale data

**Data NOT persisted** (security):

- Passwords
- OTP codes
- Current password (password change flow)
- Sensitive company data

---

## Technology & Best Practices

### Frontend State Management

**Decision**: Vue 3 Composition API with Pinia (if global state needed; otherwise keep local)

**Rationale**:

- Spec requires theme toggle across all pages → theme state must be global
- Password change is local to profile page → local state sufficient
- Pinia (Vue 3 replacement for Vuex) lightweight and performant

**Implementation**:

- Create Pinia store: `stores/theme.ts` → manage light/dark/system modes
- Store in localStorage: `flexdash_theme`
- Dispatch on app mount: `useThemeStore().loadPreference()`

### Theme Toggle Implementation

**Decision**: Tailwind CSS dark mode with system preference fallback

**Rationale**:

- Tailwind v4 has native dark mode support
- System preference via `prefers-color-scheme` CSS media query
- No additional library required

**Implementation**:

- `tailwind.config.ts`: Set `darkMode: 'class'`
- Add `dark:` prefix to affected components
- JS: Toggle `document.documentElement.classList.toggle('dark')`
- Store user preference in localStorage + `User.theme_preference` column

### Password Validation

**Decision**: Enforce Laravel default password rules + custom Ecuador-specific rules

**Rationale**:

- Laravel's default: minimum 8 characters, confirmed, unformatted
- No complex requirements needed (POS system, not banking; priority is usability)

**Rules**:

```php
'password' => ['required', 'min:8', 'confirmed', Password::default()]
```

---

## Dependencies & Integration Points

| Component          | Library/Service             | Version                   | Status                |
| ------------------ | --------------------------- | ------------------------- | --------------------- |
| Email delivery     | Resend                      | ^1.4                      | ✓ Configured          |
| ORM                | Eloquent (Laravel)          | ^12.0                     | ✓ Included            |
| Authentication     | Laravel Auth/Sanctum        | ^12.0                     | ✓ Included            |
| Frontend styling   | Tailwind CSS                | ^4.3.0                    | ✓ Configured          |
| Frontend framework | Vue 3 (assumed)             | TBD                       | Check resources/views |
| State management   | Pinia                       | TBD                       | Install if needed     |
| Timestamps         | Carbon                      | ^3.0 (Laravel dependency) | ✓ Included            |
| OTP generation     | `random_bytes()` PHP native | N/A                       | ✓ Native              |

---

## Constraint Validations

✓ **OTP expiration**: 10 minutes (achievable via `expires_at` timestamp)
✓ **OTP max attempts**: 3 (enforced via `attempts` counter + validation)
✓ **Password reset session invalidation**: Supported by Laravel Auth
✓ **Theme respects system preference**: Tailwind dark mode + CSS media queries
✓ **Atomic profile edits**: Laravel Eloquent transactions support
✓ **Email delivery SLA**: Resend 95th percentile ~500ms (within 30s requirement)

---

## Next Steps (Phase 1)

- Design data model with `User`, `OtpToken`, and migration strategy
- Define API contracts for profile endpoints
- Create quickstart implementation guide
- Generate tasks for test-first implementation
