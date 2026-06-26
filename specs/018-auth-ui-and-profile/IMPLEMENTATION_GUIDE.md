# Feature 018: User Authentication UI & Profile with Preferences

## 📋 Overview

Feature 018 implements a complete user authentication UI system with profile management and user preferences for the FlexDash Laravel application. This feature enables users to manage their account information, change passwords securely with OTP verification, and configure personalized preferences including language, timezone, theme, and email notifications.

## ✨ Key Features

### 1. **Profile Management**

- View and edit user profile information
- Update name, email, phone number
- Manage user status and role
- Delete account permanently

### 2. **Secure Password Change**

- 3-step OTP-based password change workflow
- Email verification via Resend
- Cooldown enforcement (30 seconds between OTP requests)
- Maximum 3 attempts per OTP code
- Automatic token revocation on password change

### 3. **User Preferences**

- **Language Selection**: Spanish (es) or English (en)
- **Timezone Configuration**: 15+ timezones across Americas, Europe, and Asia
- **Theme Preference**: Light, Dark, or System (with localStorage persistence)
- **Notifications**: Email notification toggles (general, sales, security)

### 4. **Alpine.js Integration**

- Real-time form submission without page reload
- Multi-step modal workflow
- Error handling and validation display
- Loading states and success messages
- Theme toggle with immediate UI updates

## 🏗️ Architecture

```
app/Modules/
├── Auth/
│   ├── Controllers/
│   │   └── PasswordChangeController.php
│   ├── Requests/
│   │   ├── RequestPasswordOtpRequest.php
│   │   ├── VerifyPasswordOtpRequest.php
│   │   └── ResetPasswordRequest.php
│   └── Services/
│       └── PasswordChangeOtpService.php
└── Profile/
    ├── Controllers/
    │   ├── ProfileController.php
    │   └── PreferencesController.php
    └── Models/
        └── User.php (extended)

database/
├── migrations/
│   └── 2026_06_25_add_user_preferences_to_users_table.php
└── factories/
    └── UserFactory.php

resources/
├── views/
│   ├── profile/
│   │   ├── edit.blade.php
│   │   └── partials/
│   │       ├── update-profile-information-form.blade.php
│   │       └── update-password-form.blade.php
│   └── preferences/
│       └── index.blade.php
└── js/
    ├── profile-form.js
    ├── theme-preferences.js
    └── password-change-otp.js

routes/
├── web.php (profile, preferences routes)
└── api.php (6 JSON endpoints)

tests/
├── Feature/
│   ├── ProfileApiTest.php
│   ├── PasswordChangeOtpApiTest.php
│   ├── PasswordChangeOtpModalTest.php
│   ├── UserPreferencesTest.php
│   └── AuthUserPreferencesIntegrationTest.php
└── Unit/
    └── PasswordChangeOtpServiceTest.php
```

## 🔌 API Endpoints

### Profile Endpoints

- `GET /api/profile` - Get current user profile
- `PATCH /api/profile` - Update profile (name, email, phone, language, timezone, notifications_enabled)
- `DELETE /api/profile` - Delete account (requires password)

### Password Change Endpoints

- `POST /api/password/request-otp` - Request OTP for password change
- `POST /api/password/verify-otp` - Verify OTP code
- `PUT /api/password/reset` - Reset password with new password

### Web Routes

- `GET /profile` - Show profile edit page
- `PATCH /profile` - Update profile (form submission)
- `DELETE /profile` - Delete account (form submission)
- `GET /preferences` - Show user preferences page

## 🗄️ Database Changes

### User Table Additions

```sql
ALTER TABLE users ADD COLUMN theme_preference VARCHAR(50) DEFAULT 'system';
ALTER TABLE users ADD COLUMN language VARCHAR(10) DEFAULT 'es';
ALTER TABLE users ADD COLUMN timezone VARCHAR(100) DEFAULT 'America/Guayaquil';
ALTER TABLE users ADD COLUMN notifications_enabled BOOLEAN DEFAULT true;
```

### EmailVerification Table Enhancement

```sql
ALTER TABLE email_verifications ADD COLUMN purpose VARCHAR(50) DEFAULT 'email_verification';
```

## 🧪 Testing

### Test Coverage: 32+ Tests (80%+)

#### Feature Tests (28 tests)

- `ProfileApiTest.php` - Profile CRUD operations
- `PasswordChangeOtpApiTest.php` - OTP workflow
- `PasswordChangeOtpModalTest.php` - Modal interactions
- `UserPreferencesTest.php` - Preference updates
- `AuthUserPreferencesIntegrationTest.php` - End-to-end workflows

#### Unit Tests (6 tests)

- `PasswordChangeOtpServiceTest.php` - Service logic validation

### Running Tests

```bash
# All tests
php artisan test

# Feature tests only
php artisan test tests/Feature/

# Unit tests only
php artisan test tests/Unit/

# Specific test file
php artisan test tests/Feature/ProfileApiTest.php

# With coverage
php artisan test --coverage
```

## 🎨 Frontend Components

### Alpine.js Handlers

#### `profileFormHandler()`

- Manages profile form AJAX submission
- Loads user data via API
- Handles validation errors
- Shows success/error messages

#### `themePreferencesHandler()`

- Toggles light/dark/system themes
- Persists theme to localStorage
- Syncs with server
- Updates CSS variables

#### `passwordChangeOtpHandler()`

- 3-step modal workflow
- OTP request with cooldown
- OTP verification with attempt tracking
- Password reset with confirmation

#### `preferencesHandler()`

- Manages language and timezone preferences
- Auto-saves via API
- Provides user feedback

#### `notificationPreferencesHandler()`

- Toggles notification preferences
- Saves to localStorage and server
- Conditional field disabling

### Blade Views

#### `profile/edit.blade.php`

- Profile information section with Alpine.js form
- Password change section with OTP modal
- Delete account section with confirmation

#### `preferences/index.blade.php`

- Regional settings (language, timezone)
- Theme selector with visual indicators
- Notification preference toggles

## 🔐 Security Features

1. **Password Security**
    - OTP verification before password change
    - Hashed OTP storage in database
    - Cooldown enforcement (30 seconds)
    - Max 3 attempts per OTP code
    - OTP expiration (10 minutes default)

2. **Session Management**
    - Session-based OTP verification token
    - Token TTL (5 minutes)
    - Automatic cleanup after verification
    - Token validation before password reset

3. **CSRF Protection**
    - All forms include CSRF tokens
    - API requests validate X-CSRF-TOKEN header

4. **Input Validation**
    - Form request validation classes
    - Timezone validation against PHP's timezone list
    - Email uniqueness validation
    - Password confirmation matching

## 🌍 Localization

### Supported Languages

- Spanish (es) - Default
- English (en) - Fallback

### Localized Strings

All Blade templates use Blade's `{{ __('...') }}` translation syntax. Strings are automatically translated based on user's language preference (stored in `users.language`).

Example localization keys:

- `profile.edit` - Profile edit page title
- `profile.update` - Profile update button
- `password.change` - Password change button
- `preferences.language` - Language preference label

## 📦 Dependencies

- **Framework**: Laravel 11+
- **Frontend**: Blade + Alpine.js 3.x
- **Styling**: Tailwind CSS + CSS Variables
- **Email**: Resend API (via EmailVerificationService)
- **Testing**: Pest + PHPUnit
- **Icons**: Font Awesome 6.4+

## 🚀 Installation & Setup

### 1. Run Migrations

```bash
php artisan migrate --step
```

### 2. Add Routes

Routes are automatically registered via `routes/web.php` and `routes/api.php`

### 3. Configure Email

Ensure Resend is configured in `.env`:

```env
MAIL_DRIVER=resend
RESEND_API_KEY=your_resend_key
```

### 4. Link Public Storage (if storing preferences files)

```bash
php artisan storage:link
```

## 📖 Usage Examples

### Profile Update via API

```javascript
const response = await fetch("/api/profile", {
    method: "PATCH",
    headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrfToken,
    },
    body: JSON.stringify({
        name: "John Doe",
        email: "john@example.com",
        language: "en",
        timezone: "America/New_York",
        notifications_enabled: true,
    }),
});
```

### Password Change Workflow

```javascript
// Step 1: Request OTP
POST /api/password/request-otp
{ "current_password": "secret" }

// Step 2: Verify OTP
POST /api/password/verify-otp
{ "otp": "123456" }

// Step 3: Reset Password
PUT /api/password/reset
{
    "new_password": "newsecret",
    "new_password_confirmation": "newsecret"
}
```

## 🐛 Troubleshooting

### OTP Not Being Sent

- Check Resend API key in `.env`
- Verify `EmailVerificationService` is properly injected
- Check Laravel logs: `storage/logs/laravel.log`

### Theme Not Persisting

- Verify `localStorage` is enabled in browser
- Check browser console for JavaScript errors
- Ensure Alpine.js is properly loaded

### Profile Update Returns 422

- Validate form inputs match validation rules
- Check that timezone is in PHP's supported timezone list
- Ensure email is unique (unless updating current user)

### API Routes Returning 404

- Verify middleware is applied correctly
- Check auth guard is configured (auth:sanctum,api)
- Ensure routes are properly registered in `routes/api.php`

## 📊 Future Enhancements

1. **Multi-language Support**
    - Add more languages (Portuguese, French, German)
    - Implement language switcher

2. **Advanced Preferences**
    - Two-factor authentication (2FA)
    - OAuth provider connections
    - Session management (list active sessions, revoke)

3. **Audit Logging**
    - Log all profile changes
    - Log password change attempts
    - Display activity history

4. **Internationalization**
    - Date/time formatting per timezone
    - Number formatting per locale
    - Currency formatting

## 📝 Notes

- All timestamps use user's timezone for display
- Theme preference syncs to server but uses localStorage for immediate responsiveness
- OTP codes are 6-digit numeric strings
- Password must be at least 8 characters
- Email verification is independent of password change OTP

## ✅ Verification Checklist

- [x] All unit tests pass
- [x] All feature tests pass
- [x] Profile CRUD works via Blade and API
- [x] Password change workflow complete with OTP
- [x] Preferences page accessible and functional
- [x] Theme toggle works with localStorage
- [x] Language/timezone preferences persist
- [x] Notification preferences functional
- [x] Error messages display correctly
- [x] CSRF protection in place
- [x] Form validation working
- [x] Spanish localization complete
- [x] English localization fallback working

## 📞 Support

For issues or questions:

1. Check test files for usage examples
2. Review API contracts in contracts/ folder
3. Check Laravel logs for detailed errors
4. Review Blade templates for HTML structure
