# Data Model: Auth UI & Profile Management

**Phase**: 1 (Design) | **Date**: 2026-06-25 | **Spec**: [spec.md](spec.md) | **Research**: [research.md](research.md)

## Entity Definitions

### User

**Purpose**: Central identity entity; stores user authentication, profile, and preferences.

**Fields**:

| Field                   | Type        | Constraints                       | Notes                                    |
| ----------------------- | ----------- | --------------------------------- | ---------------------------------------- |
| `id`                    | UUID        | PK, immutable                     | Use `Illuminate\Support\Str::uuid()`     |
| `name`                  | string(255) | NOT NULL, indexed                 | User's full name                         |
| `email`                 | string(255) | UNIQUE, NOT NULL, indexed         | Used for authentication and OTP delivery |
| `phone`                 | string(20)  | NULLABLE                          | Format: +593 or local Ecuador format     |
| `company_id`            | UUID        | FK (companies.id), NULLABLE       | Multi-tenant support                     |
| `theme_preference`      | enum        | Values: 'light', 'dark', 'system' | Default: 'system'                        |
| `language`              | enum        | Values: 'es', 'en'                | Default: 'es'                            |
| `timezone`              | string(50)  | NOT NULL                          | Default: 'America/Guayaquil'             |
| `notifications_enabled` | boolean     | NOT NULL                          | Default: true                            |
| `password_hash`         | string(255) | NOT NULL                          | Hashed via bcrypt                        |
| `email_verified_at`     | timestamp   | NULLABLE                          | For email verification (if applicable)   |
| `created_at`            | timestamp   | NOT NULL, indexed                 |                                          |
| `updated_at`            | timestamp   | NOT NULL                          |                                          |
| `deleted_at`            | timestamp   | NULLABLE                          | Soft delete support                      |

**Indexes**:

- PK: `id`
- UNIQUE: `email` (with `deleted_at` index for soft deletes: `UNIQUE(email) WHERE deleted_at IS NULL`)
- Foreign: `company_id` → `companies(id)` (ON DELETE SET NULL)

**Relationships**:

- `HasMany` → `OtpToken` (user can have multiple OTP tokens across time)
- `BelongsTo` → `Company` (optional multi-tenant)

**Validation Rules** (application layer):

- `name`: required, string, max:255
- `email`: required, unique, email, max:255
- `phone`: nullable, regex:/^\+?593|^[0-9]{10}$/
- `timezone`: in:America/Guayaquil,America/New_York,etc. (IANA list)
- `language`: in:es,en
- `theme_preference`: in:light,dark,system

**Migration Path**:

- If `theme_preference` column doesn't exist: add migration `add_theme_preference_to_users`
- If `language`, `timezone`, `notifications_enabled` don't exist: add migration
- Backfill existing users: `language='es'`, `timezone='America/Guayaquil'`, `theme_preference='system'`, `notifications_enabled=true`

---

### OtpToken

**Purpose**: Temporary token for password reset workflow; enforces OTP-based verification.

**Fields**:

| Field            | Type        | Constraints                                     | Notes                                                                  |
| ---------------- | ----------- | ----------------------------------------------- | ---------------------------------------------------------------------- |
| `id`             | UUID        | PK, immutable                                   | Use `Illuminate\Support\Str::uuid()`                                   |
| `user_id`        | UUID        | FK (users.id), NOT NULL, indexed                | Required; ON DELETE CASCADE                                            |
| `token`          | string(255) | NOT NULL, indexed                               | Hashed token (store hash, not plaintext)                               |
| `token_plain`    | string(255) | EPHEMERAL (memory only)                         | Used for email delivery only; never stored                             |
| `purpose`        | enum        | Values: 'password_change', 'email_verification' | Default: 'password_change'                                             |
| `attempts`       | int         | NOT NULL                                        | Default: 0; max: 3 before expiry                                       |
| `expires_at`     | timestamp   | NOT NULL                                        | TTL: 10 minutes from creation                                          |
| `used_at`        | timestamp   | NULLABLE                                        | Set when token is successfully verified                                |
| `invalidated_at` | timestamp   | NULLABLE                                        | Set when new OTP requested while old is active (explicit invalidation) |
| `created_at`     | timestamp   | NOT NULL                                        |                                                                        |

**Indexes**:

- PK: `id`
- FK: `user_id` → `users(id)`
- Composite: `(user_id, purpose)` for efficient lookup
- Query optimization: `expires_at` (for cleanup queries)

**Relationships**:

- `BelongsTo` → `User` (many-to-one)

**Constraints & Validation**:

- Maximum 3 verification attempts before requiring new OTP
- OTP must expire 10 minutes after creation
- Only one active (non-used, non-invalidated) OTP per user per purpose at any time
- Token must be cryptographically secure: `bin2hex(random_bytes(32))`

**Lifecycle**:

1. **Created**: User requests password change; token generated with 10-min expiry
2. **Verified**: User enters correct OTP; `used_at` set; token becomes inactive
3. **Expired**: System cleanup removes tokens where `expires_at < now()` (optional; TTL can handle)
4. **Invalidated**: New OTP requested while previous active; old token's `invalidated_at` set
5. **Max attempts exceeded**: After 3 failed verification attempts, token unusable; user requests new OTP

**Cleanup Strategy**:

- Automatic: Database TTL or scheduled job that purges `WHERE expires_at < now() AND used_at IS NULL AND invalidated_at IS NULL`
- Manual: Artisan command `php artisan otp:cleanup` (optional)

**Migration Path**:

- Create migration: `create_otp_tokens_table`
- Fields as specified above

---

## State Transitions & Workflows

### Password Change Workflow

```
User initiates
    ↓
Verify current password
    ↓
Generate OTP token (expires in 10 min)
    ↓
Send OTP to email (async via queue)
    ↓
User submits OTP
    ├─→ Valid OTP? ✓ Continue
    ├─→ Invalid? Increment attempts; show error; allow retry
    ├─→ 3 attempts exceeded? Show "Request new OTP"
    └─→ Expired? Show "OTP expired, request new one"
    ↓
User enters new password
    ↓
Update User.password_hash
    ↓
Logout user (invalidate session)
    ↓
Redirect to login with success message
```

### Form State Persistence Workflow

```
User on login page
    ↓
User enters email → saved to localStorage (flexdash_auth_login)
    ↓
User clicks "No tengo cuenta" → navigates to registration
    ↓
Page load → restore localStorage (flexdash_auth_register) if exists
    ↓
User clicks "Volver al Login" → navigates to login
    ↓
Page load → restore localStorage (flexdash_auth_login) if exists
    ↓
User successfully logs in → clear localStorage (flexdash_auth_login)
```

### Theme Toggle Workflow

```
App initializes
    ↓
Check localStorage (flexdash_theme) for saved preference
    ├─→ Found? Apply it
    └─→ Not found? Check User.theme_preference if authenticated
        ├─→ 'dark' or 'light'? Apply it
        ├─→ 'system'? Check CSS media query (prefers-color-scheme)
        └─→ Default: apply system preference
    ↓
Toggle button clicked
    ↓
Flip theme (light → dark or dark → light)
    ↓
Update localStorage (flexdash_theme)
    ↓
Update User.theme_preference (if authenticated) via API call
    ↓
Re-render UI with new theme classes
```

---

## Database Migrations

### Migration 1: Add User Preferences

**File**: `database/migrations/2026_06_25_add_user_preferences.php`

```php
// Up: Add columns to users table
Schema::table('users', function (Blueprint $table) {
    $table->enum('theme_preference', ['light', 'dark', 'system'])
        ->default('system')
        ->after('email_verified_at');
    $table->enum('language', ['es', 'en'])
        ->default('es')
        ->after('theme_preference');
    $table->string('timezone', 50)
        ->default('America/Guayaquil')
        ->after('language');
    $table->boolean('notifications_enabled')
        ->default(true)
        ->after('timezone');
});

// Down: Drop columns
Schema::table('users', function (Blueprint $table) {
    $table->dropColumn(['theme_preference', 'language', 'timezone', 'notifications_enabled']);
});
```

### Migration 2: Create OtpTokens Table

**File**: `database/migrations/2026_06_25_create_otp_tokens_table.php`

```php
Schema::create('otp_tokens', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id')->index();
    $table->string('token', 255)->index();
    $table->enum('purpose', ['password_change', 'email_verification'])
        ->default('password_change');
    $table->integer('attempts')->default(0);
    $table->timestamp('expires_at')->index();
    $table->timestamp('used_at')->nullable();
    $table->timestamp('invalidated_at')->nullable();
    $table->timestamp('created_at');

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');

    $table->index(['user_id', 'purpose']);
});
```

---

## Entity Relationships (ER Diagram)

```
┌─────────────────┐
│     users       │
├─────────────────┤
│ id (UUID) PK    │
│ name            │
│ email (UNIQUE)  │ ──┐ 1:many relationship
│ phone           │   │
│ company_id (FK) │   │
│ theme_pref      │   │
│ language        │   │
│ timezone        │   │
│ notif_enabled   │   │
│ password_hash   │   │
│ created_at      │   │
│ updated_at      │   │
└─────────────────┘   │
                      │
                      │
                  ┌───┴───────────────┐
                  │   otp_tokens      │
                  ├───────────────────┤
                  │ id (UUID) PK      │
                  │ user_id (FK) ────────┘
                  │ token             │
                  │ purpose           │
                  │ attempts          │
                  │ expires_at        │
                  │ used_at           │
                  │ invalidated_at    │
                  │ created_at        │
                  └───────────────────┘
```

---

## Validation & Constraints Summary

| Rule                   | Implementation                                               | Enforcement                   |
| ---------------------- | ------------------------------------------------------------ | ----------------------------- |
| Email uniqueness       | UNIQUE constraint + Eloquent validator                       | Database + Application        |
| OTP max 3 attempts     | `OtpToken.attempts < 3` check                                | Application logic             |
| OTP 10-min expiry      | `OtpToken.expires_at < now()` check                          | Application + optional DB TTL |
| OTP single-active      | Query `where('used_at', null).where('invalidated_at', null)` | Application                   |
| Password hash security | bcrypt via Laravel Auth                                      | Application                   |
| Atomic profile updates | Eloquent transactions                                        | Application                   |
| Theme persistence      | localStorage + DB column                                     | Browser + Application         |

---

## Next Steps (Phase 1 → Phase 2)

- [ ] Define API contracts in `/contracts/` directory
- [ ] Create quickstart.md with implementation flow
- [ ] Generate PHPUnit/Pest test cases (TDD: write tests first)
- [ ] Implement Models: `User::class`, `OtpToken::class`
- [ ] Implement migrations
- [ ] Implement Services: `ProfileService`, `PasswordChangeService`, `OtpService`
- [ ] Implement Controllers: `ProfileController`, `PasswordChangeController`
- [ ] Implement frontend: Profile page, password change modal, theme toggle
- [ ] Integration testing with Resend email delivery
