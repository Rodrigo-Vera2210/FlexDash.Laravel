<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Branch\Models\Branch;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Modules\Registration\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchManagementTest extends TestCase
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

    private function createCompanyAdmin(string $plan = 'standard'): array
    {
        $company = Company::create([
            'company_type'        => 'legal_entity',
            'name'                => 'Branch Test Corp',
            'legal_entity_flag'   => true,
            'natural_entity_flag' => false,
            'subscription_plan'   => $plan,
            'subscription_status' => 'active',
            'city'                => 'Quito',
            'state_province'      => 'Pichincha',
            'postal_code'         => '170150',
            'country'             => 'Ecuador',
        ]);

        $user = User::create([
            'name'              => 'Branch Admin',
            'email'             => 'branchadmin@test.com',
            'password'          => Hash::make('password'),
            'company_id'        => $company->id,
            'role'              => 'owner',
            'status'            => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        return [$company, $user];
    }

    private function createProduct(Company $company): Product
    {
        $this->actingAs(User::where('company_id', $company->id)->first());

        $tax = \App\Models\Tax::create([
            'name'      => 'IVA 15%',
            'code'      => 'IVA15',
            'rate'      => 15.00,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name'      => 'General',
            'is_active' => true,
        ]);

        return Product::create([
            'category_id'   => $category->id,
            'tax_id'        => $tax->id,
            'code'          => 'BR-001',
            'name'          => 'Producto Branch Test',
            'unit'          => 'UND',
            'cost'          => 10,
            'price'         => 20,
            'minimum_stock' => 1,
            'stock'         => 0,
        ]);
    }

    public function test_can_create_branch(): void
    {
        [$company, $admin] = $this->createCompanyAdmin();
        $token = $this->generateJwtForUser($admin);

        $response = $this->withCookie('token', $token)->post('/branches', [
            'name'               => 'Sucursal Norte',
            'address'            => 'Av. Amazonas',
            'phone'              => '0991112233',
            'establishment_code' => '002',
            'is_active'          => true,
        ]);

        $response->assertRedirect('/branches');
        $this->assertDatabaseHas('branches', [
            'company_id'          => $company->id,
            'name'                => 'Sucursal Norte',
            'establishment_code'  => '002',
        ]);
    }

    public function test_cannot_create_branches_beyond_plan_limit(): void
    {
        [$company, $admin] = $this->createCompanyAdmin('standard');
        $token = $this->generateJwtForUser($admin);

        foreach (['001', '002', '003'] as $code) {
            Branch::create([
                'company_id'         => $company->id,
                'name'               => "Local {$code}",
                'establishment_code' => $code,
                'is_active'          => true,
            ]);
        }

        $response = $this->withCookie('token', $token)->post('/branches', [
            'name'               => 'Local Extra',
            'establishment_code' => '004',
            'is_active'          => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(3, Branch::where('company_id', $company->id)->where('is_active', true)->count());
    }

    public function test_branch_product_seeded_on_creation(): void
    {
        [$company, $admin] = $this->createCompanyAdmin();
        $product = $this->createProduct($company);
        $token = $this->generateJwtForUser($admin);

        $this->withCookie('token', $token)->post('/branches', [
            'name'               => 'Matriz',
            'establishment_code' => '001',
            'is_active'          => true,
        ]);

        $branch = Branch::where('establishment_code', '001')->first();

        $this->assertDatabaseHas('branch_product', [
            'branch_id'  => $branch->id,
            'product_id' => $product->id,
            'stock'      => 0,
        ]);
    }
}
