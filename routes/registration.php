<?php

use App\Modules\Registration\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Registration Routes — Multi-Enterprise Registration Wizard (spec 001)
|--------------------------------------------------------------------------
|
| These routes drive the 5-step registration wizard. They are loaded from
| routes/web.php (which already applies the 'web' middleware group), so
| session state and CSRF protection are active on every request.
|
| Step 1  GET  /register/type          → showType        (registration.type)
| Step 2  POST /register/account       → postAccount     (registration.account)
| Step 3  POST /register/entity        → postEntity      (registration.entity)
| Step 4  POST /register/review        → postReview      (registration.review)
| Step 5  POST /register/verify-otp    → postVerifyOtp   (registration.verify-otp)
|         POST /register/resend-otp    → postResendOtp   (registration.resend-otp)
|
*/

Route::controller(RegistrationController::class)
    ->prefix('register')
    ->name('registration.')
    ->group(function (): void {
        // Step 1 — Registration type selection screen
        Route::get('type', 'showType')->name('type');
        Route::post('type', 'postType')->name('type.post');

        // Step 2 — Account & contact information
        Route::get('account', 'showAccount')->name('account.show');
        Route::post('account', 'postAccount')->name('account');

        // Step 3 — Entity details (legal entity or natural person)
        Route::get('entity', 'showEntity')->name('entity.show');
        Route::post('entity', 'postEntity')->name('entity');

        // Step 4 — Planes y Pago
        Route::get('billing', 'showBilling')->name('billing.show');
        Route::post('billing', 'postBilling')->name('billing');

        // Step 5 — Review & submit (creates Company + User in DB)
        Route::get('review', 'showReview')->name('review.show');
        Route::post('review', 'postReview')->name('review');

        // Step 5 — OTP email verification
        Route::get('verify-otp', 'showVerifyOtp')->name('verify-otp.show');
        Route::post('verify-otp', 'postVerifyOtp')->name('verify-otp');

        // Resend OTP
        Route::post('resend-otp', 'postResendOtp')->name('resend-otp');
    });
