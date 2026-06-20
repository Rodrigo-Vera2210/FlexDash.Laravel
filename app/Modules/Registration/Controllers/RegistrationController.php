<?php

namespace App\Modules\Registration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Registration\Contracts\RegistrationServiceInterface;
use App\Modules\Registration\Contracts\EmailVerificationServiceInterface;
use App\Modules\Registration\Requests\RegistrationAccountRequest;
use App\Modules\Registration\Requests\RegistrationEntityRequest;
use App\Modules\Registration\Requests\ReviewRegistrationRequest;
use App\Modules\Registration\Requests\SelectCompanyTypeRequest;
use App\Modules\Registration\Requests\VerifyOtpRequest;
use App\Modules\Registration\Requests\RegistrationBillingRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Orchestrates the multi-step registration wizard.
 *
 * Constitution rule: no business logic here — only receive request,
 * delegate to service, return response. Max 30 lines per method.
 */
class RegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationServiceInterface $registrationService,
        private readonly EmailVerificationServiceInterface $emailVerificationService,
    ) {}

    // ---------------------------------------------------------------
    // Step 1 — Company Type Selection
    // ---------------------------------------------------------------

    /** GET /register/type */
    public function showType(): View
    {
        return view('registration::wizard', ['step' => 'type']);
    }

    /** POST /register/type — store company_type and go to account */
    public function postType(SelectCompanyTypeRequest $request): RedirectResponse
    {
        $request->session()->put(
            'wizard_data',
            array_merge($request->session()->get('wizard_data', []), $request->validated()),
        );

        return redirect()->route('registration.account.show');
    }

    // ---------------------------------------------------------------
    // Step 2 — Account & Contact
    // ---------------------------------------------------------------

    /** GET /register/account */
    public function showAccount(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has('wizard_data.company_type')) {
            return redirect()->route('registration.type');
        }

        return view('registration::wizard', ['step' => 'account']);
    }

    /** POST /register/account */
    public function postAccount(RegistrationAccountRequest $request): RedirectResponse
    {
        $request->session()->put(
            'wizard_data',
            array_merge($request->session()->get('wizard_data', []), $request->validated()),
        );

        return redirect()->route('registration.entity.show');
    }

    // ---------------------------------------------------------------
    // Step 3 — Entity Details
    // ---------------------------------------------------------------

    /** GET /register/entity */
    public function showEntity(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has('wizard_data.email')) {
            return redirect()->route('registration.account.show');
        }

        return view('registration::wizard', ['step' => 'entity']);
    }

    /** POST /register/entity */
    public function postEntity(RegistrationEntityRequest $request): RedirectResponse
    {
        $request->session()->put(
            'wizard_data',
            array_merge($request->session()->get('wizard_data', []), $request->validated()),
        );

        return redirect()->route('registration.billing.show');
    }

    // ---------------------------------------------------------------
    // Step 4 — Planes y Pago
    // ---------------------------------------------------------------

    /** GET /register/billing */
    public function showBilling(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has('wizard_data.email')) {
            return redirect()->route('registration.account.show');
        }

        return view('registration::wizard', ['step' => 'billing']);
    }

    /** POST /register/billing */
    public function postBilling(RegistrationBillingRequest $request): RedirectResponse
    {
        $path = $request->file('payment_receipt')->store('receipts', 'public');

        $data = $request->validated();
        $data['payment_receipt_path'] = $path;
        unset($data['payment_receipt']);

        $request->session()->put(
            'wizard_data',
            array_merge($request->session()->get('wizard_data', []), $data),
        );

        return redirect()->route('registration.review.show');
    }

    // ---------------------------------------------------------------
    // Step 5 — Review & Submit
    // ---------------------------------------------------------------

    /** GET /register/review */
    public function showReview(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has('wizard_data.company_name') &&
            ! $request->session()->has('wizard_data.full_name')) {
            return redirect()->route('registration.entity.show');
        }

        if (! $request->session()->has('wizard_data.subscription_plan')) {
            return redirect()->route('registration.billing.show');
        }

        return view('registration::wizard', [
            'step'       => 'review',
            'wizardData' => $request->session()->get('wizard_data', []),
        ]);
    }

    /** POST /register/review */
    public function postReview(ReviewRegistrationRequest $request): RedirectResponse
    {
        $data = array_merge(
            $request->session()->get('wizard_data', []),
            $request->validated(),
        );

        $user = $this->registrationService->createPendingRegistration($data);

        $request->session()->put('registered_user_id', $user->id);
        $request->session()->forget('wizard_data');

        return redirect()->route('registration.verify-otp.show');
    }

    // ---------------------------------------------------------------
    // Step 5 — OTP Verification
    // ---------------------------------------------------------------

    /** GET /register/verify-otp */
    public function showVerifyOtp(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has('registered_user_id')) {
            return redirect()->route('registration.type');
        }

        return view('registration::wizard', ['step' => 'verify']);
    }

    /** POST /register/verify-otp */
    public function postVerifyOtp(VerifyOtpRequest $request): RedirectResponse
    {
        $userId = $request->session()->get('registered_user_id');

        if (!$userId) {
            return redirect()->route('registration.type');
        }

        $user = User::findOrFail($userId);

        $isValid = $this->emailVerificationService->validateOtp($user, $request->validated()['otp_code']);

        if (!$isValid) {
            return redirect()->back()->withErrors([
                'otp_code' => 'The verification code is invalid, expired, or has exceeded retry limits.'
            ]);
        }

        $request->session()->forget('registered_user_id');

        return redirect()->route('login')->with('status', 'Your account has been verified successfully. You can now log in.');
    }

    /** POST /register/resend-otp */
    public function postResendOtp(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('registered_user_id');

        if (!$userId) {
            return redirect()->route('registration.type');
        }

        $user = User::findOrFail($userId);

        $this->emailVerificationService->resendOtp($user);

        return redirect()->route('registration.verify-otp.show')->with('status', 'A new verification code has been sent to your email.');
    }
}
