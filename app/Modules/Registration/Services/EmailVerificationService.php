<?php

namespace App\Modules\Registration\Services;

use App\Models\User;
use App\Modules\Registration\Contracts\EmailVerificationServiceInterface;
use App\Modules\Registration\Models\EmailVerification;
use App\Modules\Registration\Notifications\EmailOtpNotification;
use App\Modules\Registration\Repositories\EmailVerificationRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Concrete implementation of EmailVerificationServiceInterface.
 */
class EmailVerificationService implements EmailVerificationServiceInterface
{
    public function __construct(
        protected EmailVerificationRepository $emailVerificationRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function generateOtp(User $user): EmailVerification
    {
        $code = sprintf('%06d', mt_rand(0, 999999));

        $this->emailVerificationRepository->deleteByUserId($user->id);

        $verification = $this->emailVerificationRepository->create([
            'user_id'           => $user->id,
            'verification_code' => Hash::make($code),
            'expires_at'        => now()->addMinutes(1440),
            'attempts'          => 0,
        ]);

        $user->notify(new EmailOtpNotification($code));

        Log::info("OTP code generated and sent to user ID {$user->id}");

        return $verification;
    }

    /**
     * {@inheritdoc}
     */
    public function validateOtp(User $user, string $code): bool
    {
        $verification = $this->emailVerificationRepository->findActiveByUserId($user->id);

        if (!$verification) {
            Log::warning("OTP validation failed for user ID {$user->id}: no active verification record");
            return false;
        }

        if ($verification->attempts >= 5) {
            Log::warning("OTP validation failed for user ID {$user->id}: exceeded maximum attempts");
            return false;
        }

        if (!Hash::check($code, $verification->verification_code)) {
            $this->emailVerificationRepository->incrementAttempts($verification->id);
            Log::warning("OTP validation failed for user ID {$user->id}: invalid code (attempt " . ($verification->attempts + 1) . ")");
            return false;
        }

        $user->email_verified_at = now();
        $user->status = $user->company_id ? 'pending_activation' : 'active';
        $user->save();

        $this->emailVerificationRepository->deleteByUserId($user->id);

        Log::info("OTP validated successfully for user ID {$user->id}. Account status set to active.");

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resendOtp(User $user): EmailVerification
    {
        Log::info("OTP resend requested for user ID {$user->id}");
        return $this->generateOtp($user);
    }
}
