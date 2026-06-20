<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Registration\Models\Company;
use App\Modules\Partner\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubscriptionEnforcementTest extends TestCase
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

    private function createCompanyAndUser(string $companyStatus = 'active', ?string $expiresAt = null, string $userRole = 'owner'): array
    {
        $company = Company::create([
            'company_type'            => 'legal_entity',
            'name'                    => 'Test Company',
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
            'name'              => 'Test User',
            'email'             => 'test@company.com',
            'password'          => Hash::make('password'),
            'company_id'        => $company->id,
            'role'              => $userRole,
            'status'            => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        return [$company, $user];
    }

    public function test_active_company_can_access_dashboard()
    {
        [$company, $user] = $this->createCompanyAndUser('active');
        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_pending_approval_company_redirects_to_suspended()
    {
        [$company, $user] = $this->createCompanyAndUser('pending_approval');
        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->get('/dashboard');

        $response->assertRedirect('/subscription-suspended');
    }

    public function test_suspended_company_redirects_to_suspended()
    {
        [$company, $user] = $this->createCompanyAndUser('suspended');
        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->get('/dashboard');

        $response->assertRedirect('/subscription-suspended');
    }

    public function test_expired_subscription_company_redirects_to_suspended()
    {
        // Expired yesterday
        [$company, $user] = $this->createCompanyAndUser('active', now()->subDay()->toDateTimeString());
        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->get('/dashboard');

        $response->assertRedirect('/subscription-suspended');
    }

    public function test_blocked_company_json_request_returns_403()
    {
        [$company, $user] = $this->createCompanyAndUser('suspended');
        $token = $this->generateJwtForUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/dashboard');

        $response->assertStatus(403);
        $response->assertJson(['error' => 'subscription_inactive']);
    }

    public function test_superadmin_can_access_without_company_restriction()
    {
        $superadmin = User::create([
            'name'              => 'Super Admin Test',
            'email'             => 'superadmin_test@flexdash.com',
            'password'          => Hash::make('password'),
            'role'              => 'superadmin',
            'status'            => 'active',
            'company_id'        => null,
        ]);
        $superadmin->email_verified_at = now();
        $superadmin->save();

        $token = $this->generateJwtForUser($superadmin);

        $response = $this->withCookie('token', $token)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_vendedor_role_is_restricted_from_dashboard_and_redirects_to_sales()
    {
        [$company, $user] = $this->createCompanyAndUser('active', null, 'vendedor');
        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->get('/dashboard');

        $response->assertRedirect('/sales');
    }

    public function test_vendedor_role_can_access_sales()
    {
        [$company, $user] = $this->createCompanyAndUser('active', null, 'vendedor');
        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->get('/sales');

        $response->assertStatus(200);
    }

    public function test_vendedor_role_cannot_access_purchases()
    {
        [$company, $user] = $this->createCompanyAndUser('active', null, 'vendedor');
        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->get('/purchases');

        $response->assertRedirect('/sales');
    }

    public function test_vendedor_role_cannot_access_suppliers()
    {
        [$company, $user] = $this->createCompanyAndUser('active', null, 'vendedor');
        $token = $this->generateJwtForUser($user);

        // Create a supplier partner
        $supplier = Partner::create([
            'company_id'    => $company->id,
            'type'          => 'proveedor',
            'business_name' => 'Supplier Inc',
            'document_type' => 'RUC',
            'document_number' => '1234567890001',
            'email'         => 'supplier@example.com',
            'is_active'     => true,
        ]);

        // Accessing supplier list
        $response = $this->withCookie('token', $token)->get('/partners?type=proveedor');
        $response->assertRedirect('/sales');

        // Accessing specific supplier show
        $response = $this->withCookie('token', $token)->get("/partners/{$supplier->id}");
        $response->assertRedirect('/sales');
    }
}
