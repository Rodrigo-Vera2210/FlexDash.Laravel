# Feature Specification: Transactional Email with Resend

**Feature Branch**: `011-transactional-email-with-resend`

**Created**: 2026-06-21

**Status**: Draft

---

## 1. Feature Description & Context

FlexDash currently sends transactional emails (OTP verification, password reset) via Laravel's default `log` mail driver, which means no emails are actually delivered in production or staging environments. This feature replaces that driver with **Resend** (https://resend.com), a developer-focused transactional email API, to ensure reliable, real email delivery.

All email communication must use a **branded, HTML template** consistent with FlexDash's visual identity (dark tones, primary teal/blue accent, Plus Jakarta Sans typography). Emails must be written in **Spanish** and include proper branding, clear CTAs, and security notices.

### Email Cases Covered

| # | Trigger | Recipient | Template |
|---|---------|-----------|----------|
| 1 | New user registration (owner/company_representative) | User who just registered | OTP code verification |
| 2 | User requests OTP resend | Same user | OTP code re-send |
| 3 | User requests password reset via "Forgot Password" | User's email | Password reset link |
| 4 | User successfully resets password | User's email | Password changed confirmation |
| 5 | Subscription payment approved by superadmin | Company owner/representative | Payment approved & subscription active |
| 6 | Subscription payment rejected by superadmin | Company owner/representative | Payment rejected with reason |
| 7 | Subscription expiry warning (≤5 days remaining) | Company owner/representative | Subscription about to expire |

### Technical Integration Strategy

- **Driver**: `resend` via the official `resend/resend-laravel` Composer package.
- **API Key**: Stored in `.env` as `RESEND_API_KEY`.
- **From Address**: `onboarding@resend.dev` (configurable via `MAIL_FROM_ADDRESS`).
- **Mail Channel**: All notifications continue to use the standard Laravel `mail` channel. The only change is the underlying mailer driver — no Notification classes need to change their `via()` return value.
- **Templates**: Each email scenario uses a **dedicated Mailable or Notification** with a custom Blade HTML view. No `MailMessage` markdown is used for new emails — all new templates are full-HTML Blade views for pixel-perfect rendering.
- **Queuing**: All transactional mails dispatched via `ShouldQueue` with the `sync` driver in development and `database` driver in production.

---

## 2. User Scenarios & Testing

### User Story 1: OTP Verification Email on Registration (Priority: P1)

As a new user completing the FlexDash registration form, I want to receive a properly branded OTP verification email at my registered address so I can confirm my identity and activate my account.

**Independent Test**:
Complete the multi-step registration form for a new company/owner. After the final submission step, check the email inbox for the OTP email. Verify sender, subject, OTP display, and expiry information.

**Acceptance Scenarios**:
1. **Given** a user completes registration step 4 (company data), **When** the system processes the form, **Then** an email titled "Verifica tu cuenta en FlexDash" is delivered to the user's email via Resend within 30 seconds.
2. **Given** the OTP email is received, **When** the user inspects it, **Then** it displays: the 6-digit OTP code in large text, the expiry time, a note about ignoring if not requested, and the FlexDash logo/branding.
3. **Given** the user clicks "Reenviar código" in the OTP verification UI, **When** a new OTP is generated, **Then** a new email is sent replacing the previous code.

---

### User Story 2: Password Reset Email (Priority: P1)

As a registered FlexDash user who has forgotten their password, I want to receive a password reset link by email so that I can securely create a new password.

**Independent Test**:
Navigate to `/forgot-password`, submit a valid email. Check inbox for reset email. Click the link and verify it leads to the reset form with a valid token.

**Acceptance Scenarios**:
1. **Given** I submit my email on the "Forgot Password" page, **When** my email exists in the system, **Then** I receive an email titled "Restablece tu contraseña en FlexDash" with a secure reset link that expires in 60 minutes.
2. **Given** I receive the reset email, **When** I click the reset link, **Then** I am taken to the new password form with my email and token pre-filled.
3. **Given** I successfully reset my password, **When** the process completes, **Then** I receive a confirmation email titled "Tu contraseña ha sido cambiada" informing me of the change with a security advisory.

---

### User Story 3: Subscription Payment Status Emails (Priority: P2)

As a company owner/administrator, I want to receive email notifications when my subscription payment is approved or rejected by the superadmin, so I know the current state of my account.

**Independent Test**:
As superadmin, approve a pending payment on `/superadmin/payments`. Check the company owner's inbox for an approval email. Repeat with rejection, verifying the rejection reason appears in the email body.

**Acceptance Scenarios**:
1. **Given** a superadmin approves a pending payment, **When** the approval is saved, **Then** the company's primary contact receives an email titled "✅ Pago aprobado — Tu suscripción FlexDash está activa" with subscription dates.
2. **Given** a superadmin rejects a payment with a reason, **When** the rejection is saved, **Then** the company's primary contact receives an email titled "❌ Pago rechazado — FlexDash" including the rejection reason and a CTA to re-upload.
3. **Given** a company subscription expires in ≤5 days, **When** the expiry banner condition is met during a page load, **Then** an expiry warning email is dispatched (throttled to once per day per company).

---

## 3. Constraints & Non-Goals

- **Non-Goal**: Custom email domain verification is outside scope. Resend's sandbox can be used for development.
- **Non-Goal**: Email analytics or open/click tracking UI inside FlexDash are out of scope.
- **Constraint**: The `MAIL_MAILER=log` default must be preserved for test environments. Feature tests MUST NOT make real HTTP calls to Resend.
- **Constraint**: All secrets (`RESEND_API_KEY`) must be in `.env` only — never hardcoded.
- **Constraint**: The API Key `re_LqFviMgy_EVfe3z7T62Pjm7tnTzvevr4R` is stored in `.env` as `RESEND_API_KEY`. It must never appear in source code.
