<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlanManagementTest extends TestCase
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

    public function test_superadmin_can_view_plans_index()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        $response = $this->withCookie('token', $token)->get('/superadmin/plans');

        $response->assertStatus(200);
        $response->assertSee('Plan Basic');
        $response->assertSee('Plan Standard');
    }

    public function test_superadmin_can_create_a_plan()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        $response = $this->withCookie('token', $token)->post('/superadmin/plans', [
            'name'                     => 'Super Plan',
            'code'                     => 'super_plan',
            'price'                    => 150.00,
            'max_admins'               => 5,
            'max_sellers'              => 20,
            'max_monthly_transactions' => 1000,
            'max_branches'             => 5,
            'monthly_invoice_limit'    => 200,
            'modules'                  => ['ventas', 'clientes'],
            'is_active'                => true,
        ]);

        $response->assertRedirect('/superadmin/plans');
        $this->assertDatabaseHas('plans', [
            'code' => 'super_plan',
            'name' => 'Super Plan',
        ]);
    }

    public function test_superadmin_can_update_a_plan()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        $plan = Plan::where('code', 'standard')->first();

        $response = $this->withCookie('token', $token)->put("/superadmin/plans/{$plan->id}", [
            'name'                     => 'Standard Extra',
            'code'                     => 'standard',
            'price'                    => 65.00,
            'max_admins'               => 3,
            'max_sellers'              => 12,
            'max_monthly_transactions' => 600,
            'max_branches'             => 3,
            'monthly_invoice_limit'    => 500,
            'modules'                  => ['ventas', 'clientes', 'compras'],
        ]);

        $response->assertRedirect('/superadmin/plans');
        $this->assertEquals('Standard Extra', $plan->fresh()->name);
        $this->assertEquals(65.00, (float)$plan->fresh()->price);
    }

    public function test_superadmin_can_delete_a_plan()
    {
        $superadmin = $this->createSuperAdmin();
        $token = $this->generateJwtForUser($superadmin);

        $plan = Plan::create([
            'name'                     => 'Temp Plan',
            'code'                     => 'temp_plan',
            'price'                    => 10.00,
            'max_admins'               => 1,
            'max_sellers'              => 1,
            'max_monthly_transactions' => 10,
            'max_branches'             => 1,
            'monthly_invoice_limit'    => 50,
            'modules'                  => ['ventas'],
        ]);

        $response = $this->withCookie('token', $token)->delete("/superadmin/plans/{$plan->id}");

        $response->assertRedirect('/superadmin/plans');
        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }
}
