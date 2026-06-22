# Tasks: Transactional Email with Resend

## Phase 1: Package Installation & Environment Configuration

- [x] T001 Install `resend/resend-laravel` via Composer: `composer require resend/resend-laravel`.
- [x] T002 Update `.env`: set `MAIL_MAILER=resend`, `RESEND_API_KEY=re_LqFviMgy_EVfe3z7T62Pjm7tnTzvevr4R`, `MAIL_FROM_ADDRESS="onboarding@resend.dev"`, `MAIL_FROM_NAME="FlexDash"`.
- [x] T003 Update `.env.example` with placeholder values: `RESEND_API_KEY=your_resend_api_key_here`.
- [x] T004 Verify that `config/mail.php` correctly registers the `resend` mailer transport after package installation.

---

## Phase 2: Branded Email Layout & Templates

- [x] T005 Create master email layout at `resources/views/emails/layout.blade.php` — dark header with FlexDash branding (`#0D1E36`), teal accent (`#0A7EA5`), white content area, footer. All CSS inline for email client compatibility.
- [x] T006 Create OTP verification template at `resources/views/emails/otp-verification.blade.php` — prominently displays 6-digit code, expiry notice, and security advisory. In Spanish.
- [x] T007 Create password reset template at `resources/views/emails/password-reset.blade.php` — includes branded CTA button linking to the reset URL, 60-minute expiry notice. In Spanish.
- [x] T008 Create password changed confirmation template at `resources/views/emails/password-changed.blade.php` — security notice, timestamp, "if not you, contact us" advisory. In Spanish.
- [x] T009 Create payment approved template at `resources/views/emails/payment-approved.blade.php` — congratulatory message, subscription start/end dates, CTA to login. In Spanish.
- [x] T010 Create payment rejected template at `resources/views/emails/payment-rejected.blade.php` — sympathetic tone, rejection reason displayed, CTA to re-upload receipt. In Spanish.
- [x] T011 Create subscription expiry warning template at `resources/views/emails/subscription-expiry.blade.php` — urgency notice, days remaining, CTA to manage subscription. In Spanish.

---

## Phase 3: Notification Classes

- [x] T012 Modify `app/Modules/Registration/Notifications/EmailOtpNotification.php` — update `toMail()` to use `emails.otp-verification` Blade view and Spanish subject "Verifica tu cuenta en FlexDash".
- [x] T013 Create `app/Modules/Auth/Notifications/PasswordResetNotification.php` — extends `Notification`, uses `emails.password-reset` view, builds reset URL from token and user email.
- [x] T014 Create `app/Modules/Auth/Notifications/PasswordChangedNotification.php` — extends `Notification`, uses `emails.password-changed` view.
- [x] T015 Create `app/Modules/SuperAdmin/Notifications/PaymentApprovedNotification.php` — accepts `SubscriptionPayment` and subscription dates, uses `emails.payment-approved` view.
- [x] T016 Create `app/Modules/SuperAdmin/Notifications/PaymentRejectedNotification.php` — accepts `SubscriptionPayment` and rejection reason string, uses `emails.payment-rejected` view.
- [x] T017 Create `app/Modules/Registration/Notifications/SubscriptionExpiryNotification.php` — accepts `$daysRemaining` int, uses `emails.subscription-expiry` view.

---

## Phase 4: Model & Controller Wiring

- [x] T018 Modify `app/Models/User.php` — add `sendPasswordResetNotification($token)` override that dispatches `PasswordResetNotification`.
- [x] T019 Modify `app/Modules/Auth/Controllers/NewPasswordController.php` — after `Password::reset()` succeeds, dispatch `PasswordChangedNotification` to the user.
- [x] T020 Modify `app/Modules/SuperAdmin/Controllers/SuperAdminController.php` — in the `approve()` action, resolve the company owner user and dispatch `PaymentApprovedNotification`.
- [x] T021 Modify `app/Modules/SuperAdmin/Controllers/SuperAdminController.php` — in the `reject()` action, resolve the company owner user and dispatch `PaymentRejectedNotification` with the rejection reason.
- [x] T022 Add subscription expiry email dispatch in `resources/views/layouts/app.blade.php` PHP block (or in a dedicated middleware): use `Cache::has()` to throttle to once per day per company before dispatching `SubscriptionExpiryNotification`.

---

## Phase 5: Test Suite

- [x] T023 Create `tests/Feature/TransactionalEmailTest.php` with `Mail::fake()`.
- [x] T024 Write test: OTP verification email is sent when `EmailVerificationService::generateOtp()` is called.
- [x] T025 Write test: OTP resend email is sent when `resendOtp()` is called.
- [x] T026 Write test: Password reset email is sent when `PasswordResetLinkController::store()` is invoked with a valid email.
- [x] T027 Write test: Password changed confirmation email is sent when `NewPasswordController::store()` completes successfully.
- [x] T028 Write test: Payment approved email is sent to the company owner when `SuperAdminController::approve()` is called.
- [x] T029 Write test: Payment rejected email is sent to the company owner with the correct rejection reason when `SuperAdminController::reject()` is called.
- [x] T030 Write test: Subscription expiry notification is sent when days remaining ≤ 5, and NOT sent a second time on the same day (throttle test with Cache).

---

## Phase 6: Verification & Polish

- [x] T031 Run `php artisan test --filter=TransactionalEmailTest` — all 7+ assertions must pass.
- [x] T032 Run the full test suite `php artisan test` and confirm zero regressions.
- [x] T033 Manual smoke test with real `RESEND_API_KEY` in `.env`: complete registration and verify OTP email arrives in inbox with correct branding.
- [x] T034 Manual smoke test: request a password reset and verify the reset link email arrives and the link works end-to-end.
- [x] T035 Manual smoke test: approve a pending payment as superadmin and verify the company owner's email receives the approval notification.
