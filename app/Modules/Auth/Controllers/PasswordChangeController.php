<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\RequestPasswordOtpRequest;
use App\Modules\Auth\Requests\VerifyPasswordOtpRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use App\Modules\Auth\Services\PasswordChangeOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordChangeController extends Controller
{
    public function __construct(private PasswordChangeOtpService $otpService)
    {
    }

    /**
     * Request OTP for password change.
     * POST /api/password/request-otp
     */
    public function requestOtp(RequestPasswordOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->otpService->requestOtp($request->user());

            return response()->json([
                'message' => 'OTP sent to your email',
                'cooldown_seconds' => $result['cooldown_seconds'],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify OTP token.
     * POST /api/password/verify-otp
     */
    public function verifyOtp(VerifyPasswordOtpRequest $request): JsonResponse
    {
        try {
            $isValid = $this->otpService->verifyOtp(
                $request->user(),
                $request->validated()['otp']
            );

            if (!$isValid) {
                return response()->json([
                    'message' => 'Invalid or expired OTP',
                ], 422);
            }

            return response()->json([
                'message' => 'OTP verified',
                'token_valid' => true,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reset password with valid OTP.
     * PUT /api/password/reset
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->otpService->resetPassword(
                $request->user(),
                $request->validated()['new_password']
            );

            return response()->json([
                'message' => 'Password changed successfully. Other sessions have been logged out.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
