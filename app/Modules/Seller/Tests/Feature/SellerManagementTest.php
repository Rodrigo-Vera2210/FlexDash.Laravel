<?php

namespace App\Modules\Seller\Tests\Feature;

use App\Models\User;
use App\Modules\Registration\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SellerManagementTest extends TestCase
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

    private function createCompanyAdmin(string $plan = 'basic'): array
    {
        $company = Company::create([
            'company_type'            => 'legal_entity',
            'name'                    => 'Seller Test Corp',
            'legal_entity_flag'       => true,
            'natural_entity_flag'     => false,
            'subscription_plan'       => $plan,
            'subscription_status'     => 'active',
            'city'                    => 'Quito',
            'state_province'          => 'Pichincha',
            'postal_code'             => '170150',
            'country'                 => 'Ecuador',
        ]);

        $user = User::create([
            'name'              => 'Company Admin',
            'email'             => 'admin@sellertest.com',
            'password'          => Hash::make('password'),
            'company_id'        => $company->id,
            'role'              => 'owner',
            'status'            => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        return [$company, $user];
    }

    public function test_admin_can_view_sellers_list()
    {
        [$company, $admin] = $this->createCompanyAdmin('basic');
        $token = $this->generateJwtForUser($admin);

        $response = $this->withCookie('token', $token)->get('/sellers');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_seller_within_limits()
    {
        [$company, $admin] = $this->createCompanyAdmin('basic');
        $token = $this->generateJwtForUser($admin);

        $response = $this->withCookie('token', $token)->post('/sellers', [
            'name'     => 'John Seller',
            'email'    => 'john@sellertest.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/sellers');
        $this->assertDatabaseHas('users', [
            'email'      => 'john@sellertest.com',
            'role'       => 'vendedor',
            'status'     => 'active',
            'company_id' => $company->id,
        ]);
    }

    public function test_admin_cannot_exceed_basic_plan_seller_limit()
    {
        [$company, $admin] = $this->createCompanyAdmin('basic');
        $token = $this->generateJwtForUser($admin);

        // Limit for basic is 2 sellers. Let's create 2.
        for ($i = 1; $i <= 2; $i++) {
            User::create([
                'name'              => "Seller $i",
                'email'             => "seller$i@test.com",
                'password'          => Hash::make('password'),
                'company_id'        => $company->id,
                'role'              => 'vendedor',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]);
        }

        // Try to create 3rd seller
        $response = $this->withCookie('token', $token)->post('/sellers', [
            'name'     => 'Seller 3',
            'email'    => 'seller3@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['limit']);
        $this->assertDatabaseMissing('users', ['email' => 'seller3@test.com']);
    }

    public function test_admin_cannot_exceed_standard_plan_seller_limit()
    {
        [$company, $admin] = $this->createCompanyAdmin('standard');
        $token = $this->generateJwtForUser($admin);

        // Limit for standard is 10 sellers. Let's create 10.
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name'              => "Seller $i",
                'email'             => "seller$i@test.com",
                'password'          => Hash::make('password'),
                'company_id'        => $company->id,
                'role'              => 'vendedor',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]);
        }

        // Try to create 11th seller
        $response = $this->withCookie('token', $token)->post('/sellers', [
            'name'     => 'Seller 11',
            'email'    => 'seller11@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['limit']);
        $this->assertDatabaseMissing('users', ['email' => 'seller11@test.com']);
    }

    public function test_admin_can_toggle_seller_status()
    {
        [$company, $admin] = $this->createCompanyAdmin('basic');
        $token = $this->generateJwtForUser($admin);

        $seller = User::create([
            'name'              => 'Toggle Seller',
            'email'             => 'toggle@test.com',
            'password'          => Hash::make('password'),
            'company_id'        => $company->id,
            'role'              => 'vendedor',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        // Toggle to inactive
        $response = $this->withCookie('token', $token)->post("/sellers/{$seller->id}/toggle");
        $response->assertRedirect('/sellers');
        $this->assertEquals('inactive', $seller->fresh()->status);

        // Toggle back to active
        $response = $this->withCookie('token', $token)->post("/sellers/{$seller->id}/toggle");
        $response->assertRedirect('/sellers');
        $this->assertEquals('active', $seller->fresh()->status);
    }
}
