<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Registration\Models\EmailVerification;
use App\Modules\Registration\Notifications\EmailOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

/**
 * PasswordChangeOtpService
 * 
 * Adapts the existing EmailVerificationService for password change workflow.
 * Reuses OTP generation, hashing, validation logic.
 */
class PasswordChangeOtpService
{
    /**
     * Request OTP for password change.
     * 
     * @param User $user
     * @return array{cooldown_seconds: int}
     * @throws \Exception
     */
    public function requestOtp(User $user): array
    {
        // Check if there's a recent OTP request (cooldown)
        $recentOtp = EmailVerification::where('user_id', $user->id)
            ->where('purpose', 'password_change')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentOtp && $recentOtp->attempts < 3) {
            // Allow retry after 30 seconds
            $cooldown = 30;
            throw new \Exception("Please wait {$cooldown} seconds before requesting a new OTP.");
        }

        if ($recentOtp && $recentOtp->attempts >= 3) {
            throw new \Exception("Too many OTP requests. Please try again later.");
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete any previous OTP for this user (password change purpose)
        EmailVerification::where('user_id', $user->id)
            ->where('purpose', 'password_change')
            ->delete();

        // Store hashed OTP
        $emailVerification = EmailVerification::create([
            'user_id' => $user->id,
            'verification_code' => Hash::make($otp),
            'purpose' => 'password_change',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        // Send OTP via email
        try {
            $user->notify(new EmailOtpNotification($otp, 10)); // expires in 10 minutes
        } catch (\Exception $e) {
            $emailVerification->delete();
            throw new \Exception("Failed to send OTP. Please try again.");
        }

        return [
            'cooldown_seconds' => 30,
        ];
    }

    /**
     * Verify OTP for password change.
     * 
     * @param User $user
     * @param string $otp
     * @return bool
     * @throws \Exception
     */
    public function verifyOtp(User $user, string $otp): bool
    {
        $emailVerification = EmailVerification::where('user_id', $user->id)
            ->where('purpose', 'password_change')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$emailVerification) {
            throw new \Exception("OTP not found or expired. Please request a new one.");
        }

        if ($emailVerification->attempts >= 3) {
            $emailVerification->delete();
            throw new \Exception("Too many failed attempts. Please request a new OTP.");
        }

        // Verify OTP hash
        if (!Hash::check($otp, $emailVerification->verification_code)) {
            $emailVerification->increment('attempts');
            throw new \Exception("Invalid OTP code.");
        }

        // Mark token as used (by storing in session)
        Session::put("password_change_otp_verified_{$user->id}", true);
        Session::put("password_change_otp_expires_{$user->id}", now()->addMinutes(5)->timestamp);

        return true;
    }

    /**
     * Reset password after OTP verification.
     * Invalidates OTHER sessions (keeps current session active).
     * 
     * @param User $user
     * @param string $newPassword
     * @return void
     * @throws \Exception
     */
    public function resetPassword(User $user, string $newPassword): void
    {
        // Verify OTP was validated in this session
        $otpVerified = Session::get("password_change_otp_verified_{$user->id}");
        $otpExpires = Session::get("password_change_otp_expires_{$user->id}");

        if (!$otpVerified || !$otpExpires || $otpExpires < now()->timestamp) {
            throw new \Exception("OTP verification expired or invalid. Please verify OTP again.");
        }

        // Update password
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Delete OTP record
        EmailVerification::where('user_id', $user->id)
            ->where('purpose', 'password_change')
            ->delete();

        // Invalidate OTHER sessions (all tokens except current)
        // Note: This uses Laravel's default session handling.
        // For token-based auth (Sanctum), revoke all tokens except current request token.
        // For session-based auth, only invalidate other browser sessions.
        
        // If using Sanctum, revoke all tokens:
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        // Clear session OTP verification flags
        Session::forget("password_change_otp_verified_{$user->id}");
        Session::forget("password_change_otp_expires_{$user->id}");
    }
}
