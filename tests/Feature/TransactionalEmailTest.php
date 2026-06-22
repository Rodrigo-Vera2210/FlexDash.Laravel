<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SubscriptionPayment;
use App\Modules\Registration\Models\Company;
use App\Modules\Registration\Notifications\EmailOtpNotification;
use App\Modules\Registration\Notifications\SubscriptionExpiryNotification;
use App\Modules\Auth\Notifications\PasswordResetNotification;
use App\Modules\Auth\Notifications\PasswordChangedNotification;
use App\Modules\SuperAdmin\Notifications\PaymentApprovedNotification;
use App\Modules\SuperAdmin\Notifications\PaymentRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class TransactionalEmailTest extends TestCase
{
    use RefreshDatabase;

    private function createCompanyAndOwner(string $companyStatus = 'active', ?string $expiresAt = null): array
    {
        $company = Company::create([
            'company_type'            => 'legal_entity',
            'name'                    => 'Test Email Company',
            'legal_entity_flag'       => true,
            'natural_entity_flag'     => false,
            'subscription_plan'       => 'basic',
            'subscription_status'     => $companyStatus,
            'subscription_expires_at' => $expiresAt,
            'city'                    => 'Quito',
            'state_province'          => 'Pichincha',
            'postal_code'             => '170150',
            'country'                 => 'Ecuador',
        ]);

        $user = User::create([
            'name'              => 'Owner User',
            'email'             => 'owner@company.com',
            'password'          => Hash::make('password123'),
            'company_id'        => $company->id,
            'role'              => 'owner',
            'status'            => 'active',
        ]);

        $user->email_verified_at = now();
        $user->save();

        return [$company, $user];
    }

    protected function generateJwtForUser(User $user, int $expiryOffset = 86400): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'user_id' => $user->id,
            'role'    => $user->role ?? 'user',
            'iat'     => time(),
            'exp'     => time() + $expiryOffset,
        ]);

        $secret = config('app.key') ?: env('APP_KEY', 'secret');
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        $base64url = function ($data) {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        };

        $signingInput = $base64url($header) . '.' . $base64url($payload);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);

        return $base64url($header) . '.' . $base64url($payload) . '.' . $base64url($signature);
    }

    /**
     * Case 1: OTP verification email is sent during registration.
     */
    public function test_otp_verification_email_sent_on_registration()
    {
        Notification::fake();

        $accountData = [
            'name'                  => 'New User',
            'email'                 => 'new@example.com',
            'password'              => 'Secret1@Test',
            'password_confirmation' => 'Secret1@Test',
        ];

        $entityData = [
            'company_type'   => 'natural_person',
            'full_name'      => 'New Company',
            'id_number'      => '12345678Z',
            'address'        => 'Calle Principal 123',
            'city'           => 'Madrid',
            'state_province' => 'Madrid',
            'postal_code'    => '28001',
            'country'        => 'ES',
        ];

        $this->withSession(['wizard_data' => array_merge($accountData, $entityData)])
            ->post('/register/review');

        $user = User::where('email', 'new@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, EmailOtpNotification::class, function ($notification) {
            $mail = $notification->toMail(new User());
            $this->assertEquals('Verifica tu cuenta en FlexDash', $mail->subject);
            return true;
        });
    }

    /**
     * Case 2: OTP resend triggers EmailOtpNotification.
     */
    public function test_otp_resend_email_sent()
    {
        Notification::fake();

        $user = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@example.com',
            'password' => Hash::make('password123'),
            'status'   => 'pending_verification',
        ]);

        $this->withSession(['registered_user_id' => $user->id])
            ->post('/register/resend-otp');

        Notification::assertSentTo($user, EmailOtpNotification::class);
    }

    /**
     * Case 3: Forgot password form dispatches PasswordResetNotification.
     */
    public function test_password_reset_notification_sent()
    {
        Notification::fake();

        $user = User::create([
            'name'     => 'Alex Smith',
            'email'    => 'alex@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->post('/forgot-password', [
            'email' => 'alex@example.com',
        ]);

        Notification::assertSentTo($user, PasswordResetNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);
            $this->assertEquals('Restablece tu contraseña en FlexDash', $mail->subject);
            $this->assertStringContainsString('reset-password', $mail->viewData['resetUrl']);
            return true;
        });
    }

    /**
     * Case 4: Successful password reset dispatches PasswordChangedNotification.
     */
    public function test_password_changed_notification_sent()
    {
        Notification::fake();

        $user = User::create([
            'name'     => 'Alex Smith',
            'email'    => 'alex@example.com',
            'password' => Hash::make('password123'),
        ]);

        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => 'alex@example.com',
            'password'              => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        Notification::assertSentTo($user, PasswordChangedNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);
            $this->assertEquals('Tu contraseña ha sido cambiada — FlexDash', $mail->subject);
            return true;
        });
    }

    /**
     * Case 5: Payment approved dispatches PaymentApprovedNotification to company owner.
     */
    public function test_payment_approved_notification_sent()
    {
        Notification::fake();

        [$company, $owner] = $this->createCompanyAndOwner('pending_approval');

        $payment = SubscriptionPayment::create([
            'company_id'          => $company->id,
            'plan'                => 'standard',
            'bank_origin'         => 'Banco Pichincha',
            'account_destination' => '1234567890',
            'receipt_path'        => 'receipts/test.jpg',
            'status'              => 'pending',
            'type'                => 'signup',
        ]);

        // Mock a superadmin log-in
        $superadmin = User::create([
            'name'              => 'Super Admin',
            'email'             => 'admin@flexdash.app',
            'password'          => Hash::make('admin123'),
            'role'              => 'superadmin',
            'status'            => 'active',
        ]);
        $superadmin->email_verified_at = now();
        $superadmin->save();

        $token = $this->generateJwtForUser($superadmin);

        $this->withCookie('token', $token)
            ->post("/superadmin/companies/{$company->id}/approve", [
                'payment_id' => $payment->id,
            ]);

        Notification::assertSentTo($owner, PaymentApprovedNotification::class, function ($notification) use ($owner) {
            $mail = $notification->toMail($owner);
            $this->assertEquals('✅ Pago aprobado — Tu suscripción FlexDash está activa', $mail->subject);
            $this->assertEquals('Test Email Company', $mail->viewData['companyName']);
            $this->assertEquals('standard', $mail->viewData['planName']);
            return true;
        });
    }

    /**
     * Case 6: Payment rejected dispatches PaymentRejectedNotification to company owner.
     */
    public function test_payment_rejected_notification_sent()
    {
        Notification::fake();

        [$company, $owner] = $this->createCompanyAndOwner('pending_approval');

        $payment = SubscriptionPayment::create([
            'company_id'          => $company->id,
            'plan'                => 'standard',
            'bank_origin'         => 'Banco Pichincha',
            'account_destination' => '1234567890',
            'receipt_path'        => 'receipts/test.jpg',
            'status'              => 'pending',
            'type'                => 'signup',
        ]);

        $superadmin = User::create([
            'name'              => 'Super Admin',
            'email'             => 'admin@flexdash.app',
            'password'          => Hash::make('admin123'),
            'role'              => 'superadmin',
            'status'            => 'active',
        ]);
        $superadmin->email_verified_at = now();
        $superadmin->save();

        $token = $this->generateJwtForUser($superadmin);

        $this->withCookie('token', $token)
            ->post("/superadmin/companies/{$company->id}/reject", [
                'payment_id' => $payment->id,
                'reason'     => 'Comprobante ilegible.',
            ]);

        Notification::assertSentTo($owner, PaymentRejectedNotification::class, function ($notification) use ($owner) {
            $mail = $notification->toMail($owner);
            $this->assertEquals('❌ Pago rechazado — FlexDash', $mail->subject);
            $this->assertEquals('Comprobante ilegible.', $mail->viewData['rejectionReason']);
            return true;
        });
    }

    /**
     * Case 7: Subscription expiry warnings sent and throttled to once per day.
     */
    public function test_subscription_expiry_warning_sent_and_throttled()
    {
        Notification::fake();
        Cache::clear();

        // 3 days remaining until expiration
        [$company, $owner] = $this->createCompanyAndOwner('active', now()->addDays(3)->addHours(1)->toDateTimeString());
        $token = $this->generateJwtForUser($owner);

        // Load dashboard to trigger expiry check
        $response = $this->withCookie('token', $token)->get('/dashboard');
        $response->assertStatus(200);

        Notification::assertSentTo($owner, SubscriptionExpiryNotification::class, function ($notification) use ($owner) {
            $mail = $notification->toMail($owner);
            $this->assertEquals('⚠️ Tu suscripción de FlexDash está por vencer', $mail->subject);
            $this->assertEquals(3, $mail->viewData['daysRemaining']);
            return true;
        });

        // Reset notification fake to clear history and assert throttling
        Notification::fake();

        // Load dashboard again (should not send another notification because of throttle)
        $response2 = $this->withCookie('token', $token)->get('/dashboard');
        $response2->assertStatus(200);

        Notification::assertNotSentTo($owner, SubscriptionExpiryNotification::class);
    }
}
