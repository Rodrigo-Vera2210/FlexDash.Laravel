<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\PasswordChangeController;
use App\Modules\Profile\Controllers\ProfileController;

Route::post('/login', [AuthController::class, 'login']);

// Protected API routes (require authentication with JWT)
Route::middleware(['auth.jwt'])->group(function () {
    // Profile endpoints
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Password change OTP endpoints
    Route::post('/password/request-otp', [PasswordChangeController::class, 'requestOtp'])->name('password.request-otp');
    Route::post('/password/verify-otp', [PasswordChangeController::class, 'verifyOtp'])->name('password.verify-otp');
    Route::put('/password/reset', [PasswordChangeController::class, 'reset'])->name('api.password.reset');
});

