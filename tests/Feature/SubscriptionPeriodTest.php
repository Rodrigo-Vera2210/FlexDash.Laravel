<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SubscriptionPayment;
use App\Modules\Registration\Models\Company;
use App\Modules\SuperAdmin\Services\SuperAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubscriptionPeriodTest extends TestCase
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

        $base64url = fn ($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        $signingInput = $base64url($header) . '.' . $base64url($payload);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);

        return $base64url($header) . '.' . $base64url($payload) . '.' . $base64url($signature);
    }

    private function wizardSessionData(): array
    {
        return [
            'name'                  => 'Juan Pérez',
            'email'                 => 'juan.period@test.com',
            'password'              => 'Secret1@Test',
            'password_confirmation' => 'Secret1@Test',
            'company_type'          => 'natural_person',
            'full_name'             => 'Juan Pérez',
            'id_number'             => '1234567890',
            'address'               => 'Calle 10',
            'city'                  => 'Quito',
            'state_province'        => 'Pichincha',
            'postal_code'           => '170150',
            'country'               => 'Ecuador',
        ];
    }

    public function test_signup_stores_duration_and_applies_discount(): void
    {
        Storage::fake('public');
        $this->fakeNotifications();

        $file = UploadedFile::fake()->image('receipt.png');

        $this->withSession(['wizard_data' => $this->wizardSessionData()])
            ->post('/register/billing', [
                'subscription_plan'                => 'standard',
                'subscription_duration_months'     => 12,
                'subscription_amount'              => 601.80,
                'subscription_discount_percentage' => 15,
                'bank_origin'                      => 'Banco Pichincha',
                'account_destination'              => 'Banco Guayaquil - Ahorros #123456789',
                'payment_receipt'                => $file,
            ])
            ->assertRedirect('/register/review');

        $this->withSession(array_merge(
            ['wizard_data' => array_merge($this->wizardSessionData(), [
                'subscription_plan'                => 'standard',
                'subscription_duration_months'     => 12,
                'subscription_amount'              => 601.80,
                'subscription_discount_percentage' => 15,
                'bank_origin'                      => 'Banco Pichincha',
                'account_destination'              => 'Banco Guayaquil - Ahorros #123456789',
                'payment_receipt_path'             => 'receipts/test.png',
            ])]
        ))->post('/register/review');

        $this->assertDatabaseHas('subscription_payments', [
            'plan'                  => 'standard',
            'duration_months'       => 12,
            'discount_percentage'   => 15.00,
            'amount'                => 601.80,
            'status'                => 'pending',
        ]);
    }

    public function test_approval_sets_correct_expiration(): void
    {
        $company = Company::create([
            'company_type'        => 'legal_entity',
            'name'                => 'Period Test Corp',
            'legal_entity_flag'   => true,
            'natural_entity_flag' => false,
            'subscription_plan'   => 'standard',
            'subscription_status' => 'pending_approval',
            'city'                => 'Quito',
            'state_province'      => 'Pichincha',
            'postal_code'         => '170150',
            'country'             => 'Ecuador',
        ]);

        $admin = User::create([
            'name'       => 'Admin Period',
            'email'      => 'admin.period@test.com',
            'password'   => Hash::make('password'),
            'company_id' => $company->id,
            'role'       => 'owner',
            'status'     => 'pending_activation',
        ]);

        $payment = SubscriptionPayment::create([
            'company_id'          => $company->id,
            'plan'                => 'standard',
            'duration_months'     => 12,
            'discount_percentage' => 15,
            'amount'              => 601.80,
            'bank_origin'         => 'Banco Pichincha',
            'account_destination' => 'Cuenta Test',
            'receipt_path'        => 'receipts/test.png',
            'status'              => 'pending',
            'type'                => 'signup',
        ]);

        $this->mock(\App\Modules\Billing\Services\ElectronicInvoicingService::class, function ($mock) {
            $mock->shouldReceive('process')->andReturn(new \App\Modules\Billing\Models\ElectronicInvoice());
        });

        app(SuperAdminService::class)->approveCompany($company, $payment->id);

        $expiresAt = $company->fresh()->subscription_expires_at;
        $this->assertNotNull($expiresAt);
        $this->assertTrue(now()->addMonths(11)->lessThan($expiresAt));
        $this->assertTrue(now()->addMonths(13)->greaterThan($expiresAt));

        $this->assertDatabaseHas('branches', [
            'company_id'         => $company->id,
            'name'               => 'Matriz',
            'establishment_code' => '001',
        ]);

        $this->assertEquals($admin->fresh()->branch_id, $company->fresh()->branches()->first()->id);
    }

    public function test_invalid_duration_rejected(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('receipt.png');

        $response = $this
            ->withSession(['wizard_data' => $this->wizardSessionData()])
            ->post('/register/billing', [
                'subscription_plan'                => 'standard',
                'subscription_duration_months'     => 7,
                'subscription_amount'              => 100,
                'subscription_discount_percentage' => 0,
                'bank_origin'                      => 'Banco Pichincha',
                'account_destination'              => 'Banco Guayaquil - Ahorros #123456789',
                'payment_receipt'                => $file,
            ]);

        $response->assertSessionHasErrors('subscription_duration_months');
    }
}
