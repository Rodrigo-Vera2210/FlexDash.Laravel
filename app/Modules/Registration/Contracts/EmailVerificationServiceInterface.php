<?php

namespace App\Modules\Registration\Contracts;

use App\Models\User;
use App\Modules\Registration\Models\EmailVerification;

/**
 * Contract for the Email Verification Service.
 *
 * Defines the public API for all OTP-related operations within the registration
 * flow. Implementations are responsible for OTP generation, secure storage,
 * expiry management, attempt tracking, and resend throttling.
 *
 * Constitution rule: Cross-layer communication flows through defined interfaces
 * only. No implementation details belong here.
 */
interface EmailVerificationServiceInterface
{
    /**
     * Generate a new OTP for the given user and persist it.
     *
     * Creates (or replaces) an `EmailVerification` record for the user, storing
     * a hashed OTP code and an expiry timestamp. Also dispatches the OTP
     * notification to the user's email address.
     *
     * @param  \App\Models\User  $user  The unverified user who needs an OTP.
     *
     * @return \App\Modules\Registration\Models\EmailVerification  The freshly
     *         created verification record (with plain-text code accessible
     *         only before it is hashed, if applicable).
     *
     * @throws \Throwable  If OTP generation or persistence fails.
     */
    public function generateOtp(User $user): EmailVerification;

    /**
     * Validate an OTP code submitted by the user.
     *
     * Checks the provided code against the stored hash, verifies the record
     * has not expired, and ensures the attempt limit has not been exceeded.
     * On success, marks `email_verified_at` on the user and cleans up the
     * verification record. On failure, increments the attempt counter.
     *
     * @param  \App\Models\User  $user  The user attempting verification.
     * @param  string            $code  The plain-text OTP submitted by the user.
     *
     * @return bool  `true` when the code is correct, unexpired, and within the
     *               allowed attempt window; `false` otherwise.
     */
    public function validateOtp(User $user, string $code): bool;

    /**
     * Resend (regenerate) the OTP for the given user.
     *
     * Invalidates any existing verification record for the user and issues a
     * fresh OTP with a new expiry. Rate-limiting / resend throttle enforcement
     * is left to the implementation.
     *
     * @param  \App\Models\User  $user  The user requesting a new OTP.
     *
     * @return \App\Modules\Registration\Models\EmailVerification  The newly
     *         created verification record after the resend.
     *
     * @throws \Throwable  If the resend is throttled or persistence fails.
     */
    public function resendOtp(User $user): EmailVerification;
}
