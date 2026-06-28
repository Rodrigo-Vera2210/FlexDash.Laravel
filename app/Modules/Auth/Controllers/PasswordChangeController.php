<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\PasswordChangeOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Modules\Auth\Requests\VerifyPasswordOtpRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use App\Modules\Auth\Requests\RequestPasswordOtpRequest;

class PasswordChangeController extends Controller
{
    public function __construct(private PasswordChangeOtpService $otpService)
    {
    }

    /**
     * Show the password change form (enter current + new password).
     * GET /password/change
     */
    public function showChangeForm()
    {
        return view('password.change');
    }

    /**
     * Validate current password, send OTP, store new password in session.
     * POST /password/change
     */
    public function submitChangeForm(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'La contraseña actual es incorrecta.'])
                ->withInput();
        }

        try {
            $this->otpService->requestOtp($user);
        } catch (\Exception $e) {
            return back()->withErrors(['current_password' => $e->getMessage()])->withInput();
        }

        // Store new password temporarily in session (cleared after OTP verification)
        session()->put('password_change_new_' . $user->id, $request->new_password);

        return redirect()->route('password.change.verify');
    }

    /**
     * Show the OTP verification page.
     * GET /password/change/verify
     */
    public function showVerifyForm(Request $request)
    {
        $user = $request->user();

        if (!session()->has('password_change_new_' . $user->id)) {
            return redirect()->route('password.change')
                ->withErrors(['form' => 'Por favor completa el formulario primero.']);
        }

        return view('password.verify', [
            'userEmail' => $user->email,
        ]);
    }

    /**
     * Verify OTP and apply the new password.
     * POST /password/change/verify
     */
    public function submitVerifyForm(Request $request)
    {
        $request->validate([
            'otp_code' => ['required', 'string', 'digits:6'],
        ]);

        $user = $request->user();

        try {
            $this->otpService->verifyOtp($user, $request->otp_code);
        } catch (\Exception $e) {
            return back()->withErrors(['otp_code' => $e->getMessage()]);
        }

        $newPassword = session()->pull('password_change_new_' . $user->id);

        if (!$newPassword) {
            return redirect()->route('password.change')
                ->withErrors(['form' => 'La sesión expiró. Por favor intenta de nuevo.']);
        }

        try {
            $this->otpService->resetPassword($user, $newPassword);
        } catch (\Exception $e) {
            return back()->withErrors(['otp_code' => $e->getMessage()]);
        }

        return redirect()->route('profile.edit')->with('status', 'password-updated');
    }

    /**
     * Resend the OTP code.
     * POST /password/change/resend
     */
    public function resendOtp(Request $request)
    {
        $user = $request->user();

        if (!session()->has('password_change_new_' . $user->id)) {
            return redirect()->route('password.change');
        }

        try {
            $this->otpService->resendOtp($user);
        } catch (\Exception $e) {
            return back()->withErrors(['form' => $e->getMessage()]);
        }

        return back()->with('status', 'otp-resent');
    }

    /**
     * Request OTP via API.
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
