<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Registration\Models\Company;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
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

    private function createCompanyAndAdmin(string $name, string $email): array
    {
        $company = Company::create([
            'company_type'        => 'legal_entity',
            'name'                => $name,
            'legal_entity_flag'   => true,
            'natural_entity_flag' => false,
            'subscription_plan'   => 'basic',
            'subscription_status' => 'active',
            'city'                => 'Quito',
            'state_province'      => 'Pichincha',
            'postal_code'         => '170150',
            'country'             => 'Ecuador',
        ]);

        $user = User::create([
            'name'              => $name . ' Admin',
            'email'             => $email,
            'password'          => Hash::make('password'),
            'company_id'        => $company->id,
            'role'              => 'owner',
            'status'            => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        return [$company, $user];
    }

    public function test_tenant_records_are_scoped_automatically_and_isolated()
    {
        // Company A setup
        [$companyA, $adminA] = $this->createCompanyAndAdmin('Company A', 'admina@test.com');
        $tokenA = $this->generateJwtForUser($adminA);

        // Company B setup
        [$companyB, $adminB] = $this->createCompanyAndAdmin('Company B', 'adminb@test.com');
        $tokenB = $this->generateJwtForUser($adminB);

        // Create category and product as Company A (while logged in as A)
        $this->actingAs($adminA);
        
        $tax = \App\Models\Tax::create([
            'name' => 'IVA 12%',
            'code' => 'IVA',
            'rate' => 12.00,
            'is_active' => true,
        ]);

        $categoryA = Category::create([
            'name' => 'Category A',
            'is_active' => true,
        ]);
        
        $productA = Product::create([
            'category_id' => $categoryA->id,
            'tax_id'      => $tax->id,
            'code'        => 'PROD-A',
            'name'        => 'Product A',
            'cost'        => 10,
            'price'       => 20,
            'stock'       => 100,
            'minimum_stock' => 5,
            'is_active'   => true,
        ]);

        // Assert that the created product automatically has company_id set to Company A
        $this->assertEquals($companyA->id, $productA->fresh()->company_id);

        // Now, switch context to Company B
        $this->actingAs($adminB);

        // Attempting to query products as Company B: should return 0 products because Product A belongs to Company A
        $this->assertEquals(0, Product::count());

        // Attempting to find Product A directly: should fail/return null due to the global scope
        $foundProduct = Product::find($productA->id);
        $this->assertNull($foundProduct);
    }
}
