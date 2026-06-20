<?php

namespace App\Modules\SuperAdmin\Tests\Feature;

use App\Models\User;
use App\Modules\Registration\Models\Company;
use App\Models\SubscriptionPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

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

    private function createSuperAdmin(): User
    {
        $user = User::create([
            'name'              => 'Super Admin',
            'email'             => 'superadmin_test@flexdash.com',
            'password'          => Hash::make('password'),
            'role'              => 'superadmin',
            'status'            => 'active',
            'company_id'        => null,
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    private function createCompanyAndAdmin(string $companyStatus = 'pending_approval', string $userStatus = 'pending_activation'): array
    {
        $company = Company::create([
            'company_type'        => 'legal_entity',
            'name'                => 'Tenant Corp',
            'legal_entity_flag'   => true,
            'natural_entity_flag' => false,
            'subscription_plan'   => 'basic',
            'subscription_status' => $companyStatus,
            'city'                => 'Quito',
            'state_province'      => 'Pichincha',
            'postal_code'         => '170150',
            'country'             => 'Ecuador',
        ]);

        $user = User::create([
            'name'              => 'Tenant Admin',
            'email'             => 'admin@tenant.com',
            'password'          => Hash::make('password'),
            'company_id'        => $company->id,
            'role'              => 'owner',
            'status'            => $userStatus,
        ]);
        $user->email_verified_at = now();
        $user->save();

        return [$company, $user];
    }

    public function test_non_superadmin_is_redirected_from_superadmin_dashboard()
    {
        [$company, $admin] = $this->createCompanyAndAdmin('active', 'active');
        $token = $this->generateJwtForUser($admin);

        $response = $this->withCookie('token', $token)->get('/superadmin/dashboard');

        $response->assertRedirect('/dashboard');
    }

    public function test_superadmin_can_access_superadmin_dashboard()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        $response = $this->withCookie('token', $token)->get('/superadmin/dashboard');

        $response->assertStatus(200);
    }

    public function test_superadmin_can_approve_pending_company()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        [$company, $admin] = $this->createCompanyAndAdmin('pending_approval');

        $payment = SubscriptionPayment::create([
            'company_id'          => $company->id,
            'plan'                => 'basic',
            'bank_origin'         => 'Bank A',
            'account_destination' => 'Account B',
            'receipt_path'        => 'receipts/test.png',
            'status'              => 'pending',
            'type'                => 'signup',
        ]);

        $response = $this->withCookie('token', $token)->post("/superadmin/companies/{$company->id}/approve", [
            'payment_id' => $payment->id,
        ]);

        $response->assertRedirect('/superadmin/dashboard');

        // Assert Company is active and expiration is 1 month in future
        $this->assertEquals('active', $company->fresh()->subscription_status);
        $this->assertNotNull($company->fresh()->subscription_expires_at);
        $this->assertTrue(now()->addDays(28)->lessThan($company->fresh()->subscription_expires_at));

        // Assert Administrator user is activated
        $this->assertEquals('active', $admin->fresh()->status);

        // Assert Payment status is approved
        $this->assertEquals('approved', $payment->fresh()->status);
    }

    public function test_superadmin_can_reject_pending_company()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        [$company, $admin] = $this->createCompanyAndAdmin('pending_approval');

        $payment = SubscriptionPayment::create([
            'company_id'          => $company->id,
            'plan'                => 'basic',
            'bank_origin'         => 'Bank A',
            'account_destination' => 'Account B',
            'receipt_path'        => 'receipts/test.png',
            'status'              => 'pending',
            'type'                => 'signup',
        ]);

        $response = $this->withCookie('token', $token)->post("/superadmin/companies/{$company->id}/reject", [
            'payment_id' => $payment->id,
        ]);

        $response->assertRedirect('/superadmin/dashboard');

        // Assert Company is rejected
        $this->assertEquals('rejected', $company->fresh()->subscription_status);

        // Assert user status remains pending_activation
        $this->assertEquals('pending_activation', $admin->fresh()->status);

        // Assert Payment status is rejected
        $this->assertEquals('rejected', $payment->fresh()->status);
    }

    public function test_superadmin_can_toggle_subscription_status()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        [$company, $admin] = $this->createCompanyAndAdmin('active');

        $response = $this->withCookie('token', $token)->post("/superadmin/companies/{$company->id}/toggle-status");

        $response->assertRedirect('/superadmin/dashboard');
        $this->assertEquals('inactive', $company->fresh()->subscription_status);

        $response = $this->withCookie('token', $token)->post("/superadmin/companies/{$company->id}/toggle-status");
        $this->assertEquals('active', $company->fresh()->subscription_status);
    }

    public function test_superadmin_can_change_company_plan()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        [$company, $admin] = $this->createCompanyAndAdmin('active');

        $response = $this->withCookie('token', $token)->post("/superadmin/companies/{$company->id}/change-plan", [
            'plan' => 'standard',
        ]);

        $response->assertRedirect('/superadmin/dashboard');
        $this->assertEquals('standard', $company->fresh()->subscription_plan);
    }
}
