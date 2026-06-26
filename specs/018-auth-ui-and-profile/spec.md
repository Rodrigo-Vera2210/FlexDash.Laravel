# Feature Specification: Enhanced Authentication UI & User Profile Management

**Feature Branch**: `018-auth-ui-and-profile`

**Created**: 2026-06-25

**Status**: Draft

**Input**: User description: Cambios de flujo en login/registro con botón para volver, botón de tema siempre visible, y sección de perfil de usuario con edición de datos personales, cambio de contraseña con OTP, etc.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Navigation Between Auth Screens (Priority: P1)

Users currently reach a dead-end when viewing the registration form on mobile or desktop. They need an easy way to return to the login screen if they change their mind or already have an account.

**Why this priority**: Critical usability issue—users cannot navigate backward without using browser controls. Improves conversion by enabling account recovery paths.

**Independent Test**: User can click a "Back to Login" button on the registration page and is redirected to the login screen without losing any previously entered data on the login form.

**Acceptance Scenarios**:

1. **Given** I am on the registration page (any step), **When** I click "Volver al Login" button, **Then** I am redirected to the login screen
2. **Given** I am on the login page, **When** I click "No tengo cuenta" or similar link, **Then** I am redirected to the registration welcome (step 1)
3. **Given** I navigate between login ↔ registration, **When** I return, **Then** form fields are preserved for the current screen (session/localStorage)

---

### User Story 2 - Theme Toggle Button Always Accessible (Priority: P1)

Users want to switch between light and dark themes at any time, from any screen (login, registration, dashboard, profile). Currently, theme switching may only be available in specific locations or not at all in auth screens.

**Why this priority**: Essential accessibility feature; improves usability for users with vision comfort needs. High adoption in modern SaaS apps.

**Independent Test**: A theme toggle button appears in the top-right corner of every page (login, registration, dashboard, profile). Clicking it toggles between light/dark themes and persists the preference.

**Acceptance Scenarios**:

1. **Given** I am on the login screen, **When** I click the theme toggle in the top-right corner, **Then** the page switches to dark/light theme immediately
2. **Given** I toggle the theme to dark, **When** I refresh the page, **Then** dark theme is retained
3. **Given** I toggle the theme, **When** I navigate to another page, **Then** the chosen theme persists across all pages
4. **Given** I am on a mobile device, **When** I look at the top-right corner, **Then** the theme toggle button is visible and accessible

---

### User Story 3 - View & Edit Personal Information (Priority: P1)

Authenticated users need a dedicated profile section where they can view and update their personal information (name, email, phone, company details, etc.) after registration.

**Why this priority**: Core user profile management feature; essential for users to maintain accurate account information and improve data quality.

**Independent Test**: User navigates to their profile page, sees all their personal information, edits at least one field, saves changes, and the updated information is persisted and displayed on subsequent page loads.

**Acceptance Scenarios**:

1. **Given** I am authenticated, **When** I navigate to "Mi Perfil" or "Configuración", **Then** I see my personal information (name, email, phone, company, etc.)
2. **Given** I am viewing my profile, **When** I click "Editar" on a field, **Then** it becomes editable (or a modal/form opens)
3. **Given** I update a field and click "Guardar", **Then** the system validates the input and persists it, and I see a success message
4. **Given** I update invalid data (e.g., malformed email), **When** I try to save, **Then** I see clear error messages and can correct the data

---

### User Story 4 - Change Password with OTP Verification (Priority: P2)

Users need a secure way to change their password. To prevent unauthorized changes, the system must require OTP verification (sent to their registered email) before allowing the password change.

**Why this priority**: Security feature; required for compliance and user account protection. Slightly lower than P1 because it's a secondary action (not day-1 critical).

**Independent Test**: User initiates a password change, receives an OTP via email, enters the OTP, sets a new password, and can log in with the new password on the next session.

**Acceptance Scenarios**:

1. **Given** I am in my profile, **When** I click "Cambiar Contraseña", **Then** a form appears asking for my current password
2. **Given** I enter my current password, **When** I click "Siguiente" or "Enviar OTP", **Then** an OTP is sent to my registered email and a verification input appears
3. **Given** I receive the OTP via email, **When** I enter it in the form, **Then** the system validates it and allows me to proceed to the password reset form
4. **Given** I enter a new password and confirm it, **When** I click "Guardar Nueva Contraseña", **Then** the system persists the new password and shows a success message
5. **Given** I enter an incorrect OTP, **When** I try to proceed, **Then** I see an error message and can request a new OTP

---

### User Story 5 - Additional Profile Settings (Priority: P2)

Users may want to manage additional preferences such as notification settings, language preference, timezone, or delete their account.

**Why this priority**: Enhancement feature; improves user control and data privacy. Can be added incrementally after core profile features.

**Independent Test**: User can navigate to additional settings (e.g., "Preferencias"), update at least one preference, and see it reflected on the next page visit.

**Acceptance Scenarios**:

1. **Given** I am in my profile, **When** I navigate to "Preferencias" or "Configuración Avanzada", **Then** I see options for language, timezone, notifications, etc.
2. **Given** I update a preference, **When** I click "Guardar", **Then** the change is persisted and reflected in my experience
3. **Given** I look for a "Eliminar Cuenta" option, **When** I click it, **Then** I am shown a warning dialog and must confirm the action

---

### Edge Cases

- What happens if a user tries to change their password but doesn't receive the OTP email? → System SHOULD provide an option to resend OTP or contact support
- How does the system handle concurrent password change attempts? → System MUST invalidate previous OTP tokens and only accept the most recent one
- What if a user toggles theme while in the middle of filling out a form? → Form data MUST be preserved during theme switch
- What happens if a user's email bounces when sending OTP? → System MUST notify the user and suggest updating their email address

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST provide a "Volver al Login" button on all registration pages (visible and accessible on mobile and desktop)
- **FR-002**: System MUST redirect users to the login screen when the "Volver al Login" button is clicked
- **FR-003**: System MUST preserve form state when navigating between login and registration screens
- **FR-004**: System MUST display a theme toggle button in the top-right corner of every page (login, registration, profile, dashboard)
- **FR-005**: System MUST toggle between light and dark themes when the button is clicked
- **FR-006**: System MUST persist the user's theme preference (in localStorage or user settings) and apply it on subsequent visits
- **FR-007**: System MUST provide a profile/settings page accessible to authenticated users
- **FR-008**: System MUST display all personal information fields on the profile page (name, email, phone, company details, etc.)
- **FR-009**: System MUST allow users to edit their personal information through an in-line editor or modal form
- **FR-010**: System MUST validate all input fields (email format, phone format, required fields, etc.)
- **FR-011**: System MUST persist updated personal information and show a success message
- **FR-012**: System MUST provide a "Cambiar Contraseña" option in the profile settings
- **FR-013**: System MUST require the user's current password before initiating a password change
- **FR-014**: System MUST send an OTP to the user's registered email when they initiate a password change
- **FR-015**: System MUST require OTP verification before allowing password reset
- **FR-016**: System MUST validate the new password (minimum length, complexity requirements per project standards)
- **FR-017**: System MUST persist the new password securely and invalidate all existing sessions after password change [NEEDS CLARIFICATION: should user be logged out after password change?]
- **FR-018**: System MUST provide a "Resend OTP" option if the user doesn't receive the email
- **FR-019**: System MUST display clear error messages for invalid OTP, expired OTP, or max attempts exceeded
- **FR-020**: System MUST support additional user preferences (language, timezone, notification settings) [NEEDS CLARIFICATION: which preferences are highest priority?]

### Non-Functional Requirements

- Theme toggle MUST respond within 100ms (no perceptible lag)
- Password change flow MUST complete within 5 minutes (OTP expiration window)
- All profile edits MUST persist within 2 seconds of user clicking "Save"
- Profile page MUST load within 1.5 seconds for authenticated users
- OTP emails MUST be delivered within 30 seconds of request

## Success Criteria *(mandatory)*

- ✓ Users can navigate freely between login and registration screens
- ✓ Theme toggle is visible and functional on 100% of user-facing pages (login, registration, dashboard, profile)
- ✓ Users can view and edit their complete personal profile
- ✓ Password change requires OTP verification with no exceptions
- ✓ Profile changes persist across browser sessions and devices
- ✓ Mobile and desktop layouts are fully responsive and accessible
- ✓ All error states display helpful, actionable messages
- ✓ Theme preference persists across sessions (measured via localStorage/cookies)

## Key Entities

### User

- `id` (UUID)
- `name` (string, required)
- `email` (string, unique, required)
- `phone` (string, optional)
- `company_id` (UUID, optional)
- `theme_preference` (enum: 'light' | 'dark' | 'system', default: 'system')
- `language` (enum, default: 'es')
- `password_hash` (string, hashed)
- `updated_at` (timestamp)

### OtpToken

- `id` (UUID)
- `user_id` (UUID, foreign key)
- `token` (string, hashed)
- `purpose` ('password_change' | 'email_verification', etc.)
- `attempts` (integer, default: 0, max: 3)
- `expires_at` (timestamp, TTL: 10 minutes)
- `used_at` (timestamp, nullable)
- `created_at` (timestamp)

## Assumptions

1. User authentication system (login/registration) already exists and is functional
2. Email delivery service (SMTP or third-party like Resend) is configured and operational
3. Form validation library or utilities are already in place in the codebase
4. Theme system (CSS variables or Tailwind dark mode) is already implemented
5. Users have a unique email address per account (no multi-account per email)
6. OTP tokens are generated using cryptographically secure random generation
7. Session management handles logout after password change to ensure security

## Constraints

- OTP tokens MUST expire after 10 minutes
- Maximum 3 OTP verification attempts before requiring a new OTP to be sent
- Password reset MUST invalidate all active sessions (user forced to re-login)
- Theme preference MUST respect user's system preference if set to 'system'
- Profile edits MUST be atomic (all-or-nothing transactions)

## Dependencies

- Email service (currently: Resend)
- Session/authentication middleware (Laravel)
- ORM for database operations (Eloquent)
- Frontend theme framework (Tailwind CSS dark mode)
- UUID generation library
