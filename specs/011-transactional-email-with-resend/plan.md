# Technical Implementation Plan: Transactional Email with Resend

**Branch**: `011-transactional-email-with-resend` | **Date**: 2026-06-21

**Spec**: `/specs/011-transactional-email-with-resend/spec.md`

---

## 1. Summary of Technical Approach

1. **Install Resend Laravel Package**: Use `composer require resend/resend-laravel` to add the official Resend mailer driver for Laravel.
2. **Environment Configuration**: Set `MAIL_MAILER=resend` and `RESEND_API_KEY=re_LqFviMgy_EVfe3z7T62Pjm7tnTzvevr4R` in `.env`. Update `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` to FlexDash values.
3. **Refactor OTP Notification**: Replace `EmailOtpNotification`'s generic `MailMessage` builder with a reference to a dedicated branded Blade view (`emails.otp-verification`).
4. **Custom Password Reset Notification**: Override Laravel's built-in `ResetPassword` notification on the `User` model to point to a branded Blade view (`emails.password-reset`).
5. **Password Changed Confirmation**: Create a new `PasswordChangedNotification` dispatched from `NewPasswordController` after successful reset.
6. **Subscription Status Notifications**: Create two new Notification classes — `PaymentApprovedNotification` and `PaymentRejectedNotification` — dispatched from `SuperAdminController` when payment status changes.
7. **Subscription Expiry Warning**: Create `SubscriptionExpiryNotification` with daily throttling via Laravel's Cache to avoid repeated sends. Dispatch it from the layout's PHP block or from a dedicated middleware/observer.
8. **Branded Blade Email Templates**: All email views live in `resources/views/emails/` and use an inline-CSS, responsive HTML layout consistent with the FlexDash design system (dark header, teal accent `#0A7EA5`, Plus Jakarta Sans font stack).
9. **Test Environment Isolation**: Use Laravel's `Mail::fake()` in all feature tests. The `MAIL_MAILER=log` value in `.env.testing` ensures no real API calls are made in CI.

---

## 2. Project File Structure

```text
composer.json
└── [MODIFY] Add resend/resend-laravel dependency

.env
└── [MODIFY] Add RESEND_API_KEY, update MAIL_MAILER, MAIL_FROM_ADDRESS

config/mail.php
└── [VERIFY] Confirm `resend` mailer entry exists (auto-added by package)

app/Modules/Registration/Notifications/
├── EmailOtpNotification.php          [MODIFY] Point toMail() to branded Blade view

app/Modules/Auth/Notifications/
├── PasswordResetNotification.php     [NEW] Override Laravel's ResetPassword for branded template
└── PasswordChangedNotification.php   [NEW] Confirmation email after successful password reset

app/Modules/SuperAdmin/Notifications/
├── PaymentApprovedNotification.php   [NEW] Email to company owner when payment is approved
└── PaymentRejectedNotification.php   [NEW] Email to company owner when payment is rejected

app/Modules/Registration/Notifications/
└── SubscriptionExpiryNotification.php [NEW] Expiry warning email (throttled)

app/Models/User.php
└── [MODIFY] Add sendPasswordResetNotification() override

app/Modules/SuperAdmin/Controllers/SuperAdminController.php
└── [MODIFY] Dispatch PaymentApproved/RejectedNotification in approve/reject actions

app/Modules/Auth/Controllers/NewPasswordController.php
└── [MODIFY] Dispatch PasswordChangedNotification after successful reset

resources/views/emails/
├── layout.blade.php                  [NEW] Master branded HTML email shell
├── otp-verification.blade.php        [NEW] OTP verification template
├── password-reset.blade.php          [NEW] Password reset link template
├── password-changed.blade.php        [NEW] Password changed confirmation template
├── payment-approved.blade.php        [NEW] Subscription payment approved template
├── payment-rejected.blade.php        [NEW] Subscription payment rejected template
└── subscription-expiry.blade.php     [NEW] Subscription about-to-expire warning template

tests/Feature/
└── TransactionalEmailTest.php        [NEW] Feature tests asserting mail dispatch for all 7 cases
```

---

## 3. Implementation Details

### A. Resend Package Installation

```bash
composer require resend/resend-laravel
```

The package auto-registers a `resend` mailer transport in `config/mail.php`. No manual config edits needed beyond the driver name.

### B. Environment Variables

```dotenv
MAIL_MAILER=resend
RESEND_API_KEY=re_LqFviMgy_EVfe3z7T62Pjm7tnTzvevr4R
MAIL_FROM_ADDRESS="noreply@flexdash.app"
MAIL_FROM_NAME="FlexDash"
```

> **Test environment** (`.env.testing` or `phpunit.xml`): Keep `MAIL_MAILER=log` and `RESEND_API_KEY=test_key` to avoid real API calls.

### C. Branded Email Layout (`resources/views/emails/layout.blade.php`)

Inline-CSS HTML email template. Structure:
- Dark header (`#0D1E36`) with FlexDash wordmark
- White content area with `#0A7EA5` accent for CTAs and highlights
- Footer with legal notice, unsubscribe link (static), and company info
- Fully responsive with `max-width: 600px` centered container
- Font stack: `'Plus Jakarta Sans', -apple-system, sans-serif`

### D. OTP Notification Refactor (`EmailOtpNotification.php`)

Replace `MailMessage` chain with:

```php
public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('Verifica tu cuenta en FlexDash')
        ->view('emails.otp-verification', [
            'otpCode'    => $this->otpCode,
            'userName'   => $notifiable->name,
            'expiresIn'  => $this->expiresInMinutes,
        ]);
}
```

### E. Password Reset Override (`User.php`)

```php
public function sendPasswordResetNotification($token): void
{
    $this->notify(new \App\Modules\Auth\Notifications\PasswordResetNotification($token));
}
```

`PasswordResetNotification` passes `$token` and the user's email to the `emails.password-reset` Blade view. The reset URL is built with `route('password.reset', ['token' => $token, 'email' => $this->email])`.

### F. Password Changed Confirmation

In `NewPasswordController::store()`, after `Password::reset()` succeeds:

```php
$user->notify(new \App\Modules\Auth\Notifications\PasswordChangedNotification());
```

### G. Subscription Payment Notifications

In `SuperAdminController::approve()`:
```php
$company->owner?->notify(new PaymentApprovedNotification($payment, $subscription));
```

In `SuperAdminController::reject()`:
```php
$company->owner?->notify(new PaymentRejectedNotification($payment, $rejectionReason));
```

`$company->owner` resolves to the user with `role = 'owner'` associated to the company.

### H. Subscription Expiry Warning (Throttled)

Dispatched when `$daysRemaining >= 0 && $daysRemaining <= 5` inside `app.blade.php` PHP block or a dedicated `CheckSubscriptionExpiry` middleware:

```php
$cacheKey = "expiry_email_sent_{$company->id}";
if (!Cache::has($cacheKey)) {
    $company->owner?->notify(new SubscriptionExpiryNotification($daysRemaining));
    Cache::put($cacheKey, true, now()->endOfDay());
}
```

### I. Feature Tests (`TransactionalEmailTest.php`)

```php
use Illuminate\Support\Facades\Mail;

Mail::fake();

// Assert OTP sent
Mail::assertSent(EmailOtpNotification::class);

// Assert password reset sent
Mail::assertSent(PasswordResetNotification::class, fn ($mail) =>
    $mail->hasTo('user@example.com')
);
```

All 7 trigger scenarios covered with `Mail::fake()` assertions. No real HTTP calls.

---

## 4. Verification Plan

### Automated Tests
```bash
php artisan test --filter=TransactionalEmailTest
```
Expected: All 7 email trigger scenarios pass with `Mail::fake()` assertions.

### Manual Verification
1. Set `MAIL_MAILER=resend` in `.env` with the real API key.
2. Register a new account with a real email — verify OTP email arrives.
3. Request a password reset — verify reset link email arrives.
4. Reset password — verify confirmation email arrives.
5. As superadmin, approve/reject a payment — verify company owner receives correct email.
6. Manually trigger expiry warning by adjusting `subscription_expires_at` to today + 2 days.
