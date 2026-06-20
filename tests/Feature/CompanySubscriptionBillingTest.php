<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Registration\Models\Company;
use App\Models\SubscriptionPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanySubscriptionBillingTest extends TestCase
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

    private function createCompanyAdmin(string $plan = 'basic', ?string $expiresAt = null): array
    {
        $company = Company::create([
            'company_type'            => 'legal_entity',
            'name'                    => 'Billing Test Corp',
            'legal_entity_flag'       => true,
            'natural_entity_flag'     => false,
            'subscription_plan'       => $plan,
            'subscription_status'     => 'active',
            'subscription_expires_at' => $expiresAt ?? now()->addMonth()->toDateTimeString(),
            'city'                    => 'Quito',
            'state_province'          => 'Pichincha',
            'postal_code'             => '170150',
            'country'                 => 'Ecuador',
        ]);

        $user = User::create([
            'name'              => 'Company Admin',
            'email'             => 'admin@billingtest.com',
            'password'          => Hash::make('password'),
            'company_id'        => $company->id,
            'role'              => 'owner',
            'status'            => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        return [$company, $user];
    }

    public function test_admin_can_view_subscription_billing_page()
    {
        [$company, $admin] = $this->createCompanyAdmin('basic');
        $token = $this->generateJwtForUser($admin);

        $response = $this->withCookie('token', $token)->get('/settings/subscription');

        $response->assertStatus(200);
        $response->assertSee('basic');
    }

    public function test_admin_can_submit_upgrade_request()
    {
        Storage::fake('public');
        [$company, $admin] = $this->createCompanyAdmin('basic');
        $token = $this->generateJwtForUser($admin);

        $file = UploadedFile::fake()->image('receipt.png');

        $response = $this->withCookie('token', $token)->post('/settings/subscription/payment', [
            'plan'                => 'standard',
            'bank_origin'         => 'Banco Pichincha',
            'account_destination' => 'Banco Guayaquil - Ahorros #123456789',
            'payment_receipt'     => $file,
            'type'                => 'upgrade',
        ]);

        $response->assertRedirect('/settings/subscription');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('subscription_payments', [
            'company_id'          => $company->id,
            'plan'                => 'standard',
            'bank_origin'         => 'Banco Pichincha',
            'account_destination' => 'Banco Guayaquil - Ahorros #123456789',
            'status'              => 'pending',
            'type'                => 'upgrade',
        ]);

        // Verify company plan is NOT immediately changed in database
        $this->assertEquals('basic', $company->fresh()->subscription_plan);
    }

    public function test_admin_can_submit_renewal_request()
    {
        Storage::fake('public');
        [$company, $admin] = $this->createCompanyAdmin('standard');
        $token = $this->generateJwtForUser($admin);

        $file = UploadedFile::fake()->image('receipt.png');

        $response = $this->withCookie('token', $token)->post('/settings/subscription/payment', [
            'plan'                => 'standard',
            'bank_origin'         => 'Banco del Pacífico',
            'account_destination' => 'Banco Pichincha - Corriente #987654321',
            'payment_receipt'     => $file,
            'type'                => 'renewal',
        ]);

        $response->assertRedirect('/settings/subscription');
        
        $this->assertDatabaseHas('subscription_payments', [
            'company_id'          => $company->id,
            'plan'                => 'standard',
            'bank_origin'         => 'Banco del Pacífico',
            'account_destination' => 'Banco Pichincha - Corriente #987654321',
            'status'              => 'pending',
            'type'                => 'renewal',
        ]);
    }
}
