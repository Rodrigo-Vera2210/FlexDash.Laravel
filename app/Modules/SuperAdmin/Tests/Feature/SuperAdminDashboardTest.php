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

        $response = $this->from('/superadmin/dashboard')
            ->withCookie('token', $token)
            ->post("/superadmin/companies/{$company->id}/approve", [
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

        $response = $this->from('/superadmin/dashboard')
            ->withCookie('token', $token)
            ->post("/superadmin/companies/{$company->id}/reject", [
                'payment_id' => $payment->id,
                'reason'     => 'Comprobante no válido o ilegible',
            ]);

        $response->assertRedirect('/superadmin/dashboard');

        // Assert Company is rejected and suspension reason is set
        $this->assertEquals('rejected', $company->fresh()->subscription_status);
        $this->assertEquals('Comprobante no válido o ilegible', $company->fresh()->suspension_reason);

        // Assert user status remains pending_activation
        $this->assertEquals('pending_activation', $admin->fresh()->status);

        // Assert Payment status is rejected and reason is set
        $this->assertEquals('rejected', $payment->fresh()->status);
        $this->assertEquals('Comprobante no válido o ilegible', $payment->fresh()->rejection_reason);

        // Assert Audit Logs were recorded
        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $superadmin->id,
            'event'          => 'subscription.reject_payment',
            'auditable_id'   => $payment->id,
            'auditable_type' => get_class($payment),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $superadmin->id,
            'event'          => 'subscription.reject_company',
            'auditable_id'   => $company->id,
            'auditable_type' => get_class($company),
        ]);
    }

    public function test_superadmin_can_toggle_subscription_status()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        [$company, $admin] = $this->createCompanyAndAdmin('active');

        // Create an approved payment to allow re-activation
        SubscriptionPayment::create([
            'company_id'          => $company->id,
            'plan'                => 'basic',
            'bank_origin'         => 'Bank A',
            'account_destination' => 'Account B',
            'receipt_path'        => 'receipts/test.png',
            'status'              => 'approved',
            'type'                => 'signup',
        ]);

        $response = $this->from('/superadmin/dashboard')
            ->withCookie('token', $token)
            ->post("/superadmin/companies/{$company->id}/toggle-status", [
                'reason' => 'Falta de pago del mes actual',
            ]);

        $response->assertRedirect('/superadmin/dashboard');
        $this->assertEquals('inactive', $company->fresh()->subscription_status);
        $this->assertEquals('Falta de pago del mes actual', $company->fresh()->suspension_reason);

        // Assert Audit Logs were recorded
        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $superadmin->id,
            'event'          => 'subscription.toggle_status',
            'auditable_id'   => $company->id,
            'auditable_type' => get_class($company),
        ]);

        $response = $this->from('/superadmin/dashboard')
            ->withCookie('token', $token)
            ->post("/superadmin/companies/{$company->id}/toggle-status");

        $response->assertRedirect('/superadmin/dashboard');
        $this->assertEquals('active', $company->fresh()->subscription_status);
        $this->assertNull($company->fresh()->suspension_reason);
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

    public function test_superadmin_redirected_from_tenant_dashboard()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        $response = $this->withCookie('token', $token)->get('/dashboard');

        $response->assertRedirect('/superadmin/dashboard');
    }

    public function test_superadmin_can_access_company_detail()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        [$company, $admin] = $this->createCompanyAndAdmin('active', 'active');

        // Add a mock seller user to verify sellers section
        $seller = User::create([
            'name'       => 'Seller User',
            'email'      => 'seller@tenant.com',
            'password'   => Hash::make('password'),
            'company_id' => $company->id,
            'role'       => 'vendedor',
            'status'     => 'active',
        ]);

        // Add a payment
        $payment = SubscriptionPayment::create([
            'company_id'          => $company->id,
            'plan'                => 'basic',
            'bank_origin'         => 'Pichincha',
            'account_destination' => 'Produbanco',
            'receipt_path'        => 'receipts/test.png',
            'status'              => 'approved',
            'type'                => 'signup',
        ]);

        $response = $this->withCookie('token', $token)->get("/superadmin/companies/{$company->id}");

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.company-detail');
        $response->assertSee($company->name);
        $response->assertSee('Tenant Admin'); // Active Admin
        $response->assertSee('Seller User'); // Active Seller
        $response->assertSee('Pichincha'); // Origin bank from payment list
    }

    public function test_superadmin_cannot_activate_subscription_without_approved_payment()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        [$company, $admin] = $this->createCompanyAndAdmin('inactive');

        // Attempt toggle-status to activate
        $response = $this->from('/superadmin/dashboard')
            ->withCookie('token', $token)
            ->post("/superadmin/companies/{$company->id}/toggle-status");

        $response->assertRedirect('/superadmin/dashboard');
        $response->assertSessionHas('error', 'No se puede activar la suscripción de la empresa sin verificar y aprobar al menos un pago primero.');
        $this->assertEquals('inactive', $company->fresh()->subscription_status);
    }

    public function test_superadmin_can_activate_subscription_with_approved_payment()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        [$company, $admin] = $this->createCompanyAndAdmin('inactive');

        // Create an approved payment first
        SubscriptionPayment::create([
            'company_id'          => $company->id,
            'plan'                => 'basic',
            'bank_origin'         => 'Pichincha',
            'account_destination' => 'Produbanco',
            'receipt_path'        => 'receipts/test.png',
            'status'              => 'approved',
            'type'                => 'signup',
        ]);

        $response = $this->from('/superadmin/dashboard')
            ->withCookie('token', $token)
            ->post("/superadmin/companies/{$company->id}/toggle-status");

        $response->assertRedirect('/superadmin/dashboard');
        $response->assertSessionHas('success');
        $this->assertEquals('active', $company->fresh()->subscription_status);
    }

    public function test_superadmin_can_access_payments_index()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        [$company, $admin] = $this->createCompanyAndAdmin('active');

        // Create approved and pending payments
        SubscriptionPayment::create([
            'company_id'          => $company->id,
            'plan'                => 'basic',
            'bank_origin'         => 'Banco Austro',
            'account_destination' => 'Produbanco',
            'receipt_path'        => 'receipts/test1.png',
            'status'              => 'approved',
            'type'                => 'signup',
        ]);

        SubscriptionPayment::create([
            'company_id'          => $company->id,
            'plan'                => 'standard',
            'bank_origin'         => 'Banco Guayaquil',
            'account_destination' => 'Pichincha',
            'receipt_path'        => 'receipts/test2.png',
            'status'              => 'pending',
            'type'                => 'renewal',
        ]);

        $response = $this->withCookie('token', $token)->get('/superadmin/payments');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.payments');
        $response->assertSee('Banco Austro');
        $response->assertSee('Banco Guayaquil');
        $response->assertSee('$29.00'); // Estimated revenue for 1 basic approved payment
    }
}
