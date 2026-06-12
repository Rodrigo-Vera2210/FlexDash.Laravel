<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Registration\Services\EmailVerificationService;
use App\Modules\Registration\Models\EmailVerification;
use App\Modules\Registration\Notifications\EmailOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_otp_creates_db_record_and_sends_notification()
    {
        $notificationFake = $this->fakeNotifications();

        $user = User::create([
            'name'     => 'Unit Test User',
            'email'    => 'unit@test.com',
            'password' => bcrypt('password'),
            'status'   => 'pending_verification',
        ]);

        $service = app(EmailVerificationService::class);
        $verification = $service->generateOtp($user);

        $this->assertInstanceOf(EmailVerification::class, $verification);
        $this->assertEquals($user->id, $verification->user_id);
        $this->assertNotNull($verification->verification_code);
        $this->assertTrue($verification->expires_at->isFuture());

        Notification::assertSentTo($user, EmailOtpNotification::class);
    }

    public function test_validate_otp_verifies_successfully()
    {
        $notificationFake = $this->fakeNotifications();

        $user = User::create([
            'name'     => 'Unit Test User',
            'email'    => 'unit@test.com',
            'password' => bcrypt('password'),
            'status'   => 'pending_verification',
        ]);

        $service = app(EmailVerificationService::class);
        $service->generateOtp($user);

        $otpCode = null;
        Notification::assertSentTo($user, EmailOtpNotification::class, function ($notification) use (&$otpCode) {
            $otpCode = $notification->otpCode;
            return true;
        });

        $this->assertNotNull($otpCode);

        $result = $service->validateOtp($user, $otpCode);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertEquals('active', $user->status);
        $this->assertNotNull($user->email_verified_at);
    }
}
