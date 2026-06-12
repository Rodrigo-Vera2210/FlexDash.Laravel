<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Registration\Models\EmailVerification;
use App\Modules\Registration\Notifications\EmailOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_receives_otp_on_registration()
    {
        $this->fakeNotifications();

        $accountData = [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'Secret1@Test',
            'password_confirmation' => 'Secret1@Test',
        ];

        $entityData = [
            'company_type'   => 'natural_person',
            'full_name'      => 'Test User',
            'id_number'      => '12345678Z',
            'address'        => 'Calle Gran Via',
            'city'           => 'Madrid',
            'state_province' => 'Madrid',
            'postal_code'    => '28013',
            'country'        => 'ES',
        ];

        $response = $this
            ->withSession(['wizard_data' => array_merge($accountData, $entityData)])
            ->post('/register/review');

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, EmailOtpNotification::class);
    }

    public function test_valid_otp_verifies_account()
    {
        $notificationFake = $this->fakeNotifications();

        $user = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@example.com',
            'password' => bcrypt('Secret1@Test'),
            'status'   => 'pending_verification',
        ]);

        // Generate OTP
        $service = app(\App\Modules\Registration\Contracts\EmailVerificationServiceInterface::class);
        $service->generateOtp($user);

        // Capture plain OTP code
        $otpCode = null;
        $notificationFake->assertSentTo($user, EmailOtpNotification::class, function ($notification) use (&$otpCode) {
            $otpCode = $notification->otpCode;
            return true;
        });

        $this->assertNotNull($otpCode);

        // Submit OTP code
        $response = $this
            ->withSession(['registered_user_id' => $user->id])
            ->post('/register/verify-otp', [
                'otp_code' => $otpCode,
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionMissing('registered_user_id');

        $user->refresh();
        $this->assertEquals('active', $user->status);
        $this->assertNotNull($user->email_verified_at);

        // Assert database record was cleaned up
        $this->assertDatabaseMissing('email_verifications', [
            'user_id' => $user->id,
        ]);
    }

    public function test_invalid_otp_is_rejected_and_increments_attempts()
    {
        $notificationFake = $this->fakeNotifications();

        $user = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@example.com',
            'password' => bcrypt('Secret1@Test'),
            'status'   => 'pending_verification',
        ]);

        $service = app(\App\Modules\Registration\Contracts\EmailVerificationServiceInterface::class);
        $service->generateOtp($user);

        $response = $this
            ->withSession(['registered_user_id' => $user->id])
            ->from('/register/verify-otp')
            ->post('/register/verify-otp', [
                'otp_code' => '999999', // wrong OTP
            ]);

        $response->assertRedirect('/register/verify-otp');
        $response->assertSessionHasErrors(['otp_code']);

        $user->refresh();
        $this->assertEquals('pending_verification', $user->status);
        $this->assertNull($user->email_verified_at);

        $this->assertDatabaseHas('email_verifications', [
            'user_id'  => $user->id,
            'attempts' => 1,
        ]);
    }

    public function test_otp_fails_after_too_many_attempts()
    {
        $notificationFake = $this->fakeNotifications();

        $user = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@example.com',
            'password' => bcrypt('Secret1@Test'),
            'status'   => 'pending_verification',
        ]);

        $service = app(\App\Modules\Registration\Contracts\EmailVerificationServiceInterface::class);
        $service->generateOtp($user);

        $verification = EmailVerification::where('user_id', $user->id)->first();
        $verification->update(['attempts' => 5]);

        // Submit correct OTP code, but attempts were already 5
        $response = $this
            ->withSession(['registered_user_id' => $user->id])
            ->from('/register/verify-otp')
            ->post('/register/verify-otp', [
                'otp_code' => '123456', // doesn't matter, it should reject due to attempts limit
            ]);

        $response->assertRedirect('/register/verify-otp');
        $response->assertSessionHasErrors(['otp_code']);
    }

    public function test_expired_otp_is_rejected()
    {
        $user = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@example.com',
            'password' => bcrypt('Secret1@Test'),
            'status'   => 'pending_verification',
        ]);

        $service = app(\App\Modules\Registration\Contracts\EmailVerificationServiceInterface::class);
        $service->generateOtp($user);

        // Expire the verification record manually
        $verification = EmailVerification::where('user_id', $user->id)->first();
        $verification->update(['expires_at' => now()->subMinute()]);

        $response = $this
            ->withSession(['registered_user_id' => $user->id])
            ->from('/register/verify-otp')
            ->post('/register/verify-otp', [
                'otp_code' => '123456',
            ]);

        $response->assertRedirect('/register/verify-otp');
        $response->assertSessionHasErrors(['otp_code']);
    }

    public function test_resend_otp_invalidates_previous_and_sends_new()
    {
        $notificationFake = $this->fakeNotifications();

        $user = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@example.com',
            'password' => bcrypt('Secret1@Test'),
            'status'   => 'pending_verification',
        ]);

        // Resend
        $response = $this
            ->withSession(['registered_user_id' => $user->id])
            ->post('/register/resend-otp');

        $response->assertRedirect('/register/verify-otp');
        $response->assertSessionHas('status');

        Notification::assertSentTo($user, EmailOtpNotification::class);
    }
}
