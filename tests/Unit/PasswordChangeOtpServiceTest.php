<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Auth\Services\PasswordChangeOtpService;
use App\Modules\Registration\Models\EmailVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeOtpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PasswordChangeOtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otpService = app(PasswordChangeOtpService::class);
    }

    // T023: Test OTP generation
    public function test_request_otp_generates_valid_otp(): void
    {
        $user = User::factory()->create();

        $result = $this->otpService->requestOtp($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('cooldown_seconds', $result);
        $this->assertEquals(30, $result['cooldown_seconds']);

        // Verify OTP was created in database
        $verification = EmailVerification::where('user_id', $user->id)
            ->where('purpose', 'password_change')
            ->first();

        $this->assertNotNull($verification);
        $this->assertNull($verification->attempts);
    }

    // T024: Test OTP verification
    public function test_verify_otp_with_valid_code(): void
    {
        $user = User::factory()->create();
        $otp = '123456';

        EmailVerification::create([
            'user_id' => $user->id,
            'verification_code' => Hash::make($otp),
            'purpose' => 'password_change',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $result = $this->otpService->verifyOtp($user, $otp);

        $this->assertTrue($result);
    }

    // T025: Test OTP expiration
    public function test_verify_otp_expired_code(): void
    {
        $user = User::factory()->create();
        $otp = '123456';

        EmailVerification::create([
            'user_id' => $user->id,
            'verification_code' => Hash::make($otp),
            'purpose' => 'password_change',
            'expires_at' => now()->subMinutes(1),
            'attempts' => 0,
        ]);

        $result = $this->otpService->verifyOtp($user, $otp);

        $this->assertFalse($result);
    }

    // T026: Test max attempts exceeded
    public function test_verify_otp_max_attempts_exceeded(): void
    {
        $user = User::factory()->create();
        $otp = '123456';

        EmailVerification::create([
            'user_id' => $user->id,
            'verification_code' => Hash::make($otp),
            'purpose' => 'password_change',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 3,
        ]);

        $result = $this->otpService->verifyOtp($user, 'wrong');

        $this->assertFalse($result);
    }

    // T027: Test password reset
    public function test_reset_password_with_valid_session(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);
        $newPassword = 'newpassword123';

        // Simulate OTP verification in session
        session(['password_change_verified_' . $user->id => true]);

        EmailVerification::create([
            'user_id' => $user->id,
            'verification_code' => Hash::make('123456'),
            'purpose' => 'password_change',
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        $this->otpService->resetPassword($user, $newPassword);

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
    }

    // T028: Test cooldown enforcement
    public function test_request_otp_enforces_cooldown(): void
    {
        $user = User::factory()->create();

        // First request
        $result1 = $this->otpService->requestOtp($user);
        $this->assertEquals(30, $result1['cooldown_seconds']);

        // Immediate second request should have cooldown
        $result2 = $this->otpService->requestOtp($user);
        $this->assertGreater(0, $result2['cooldown_seconds']);
    }
}
