<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Plan;
use App\Modules\Registration\Models\Company;
use App\Modules\Sale\Models\Sale;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Partner\Models\Partner;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Category;
use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubscriptionModuleAccessTest extends TestCase
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

    private function createCompanyAndAdmin(string $planCode = 'basic'): array
    {
        $company = Company::create([
            'company_type'        => 'legal_entity',
            'name'                => 'Override Corp',
            'legal_entity_flag'   => true,
            'natural_entity_flag' => false,
            'subscription_plan'   => $planCode,
            'subscription_status' => 'active',
            'city'                => 'Quito',
            'state_province'      => 'Pichincha',
            'postal_code'         => '170150',
            'country'             => 'Ecuador',
        ]);

        $user = User::create([
            'name'              => 'Company Admin',
            'email'             => 'admin@override.com',
            'password'          => Hash::make('password'),
            'company_id'        => $company->id,
            'role'              => 'owner',
            'status'            => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        return [$company, $user];
    }

    public function test_basic_plan_cannot_access_purchases_module_by_default()
    {
        [$company, $admin] = $this->createCompanyAndAdmin('basic');
        $token = $this->generateJwtForUser($admin);

        // Try to load /purchases index
        $response = $this->withCookie('token', $token)->get('/purchases');

        // Should be redirected to dashboard with an error
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error', 'Acceso denegado. Su plan o suscripción no incluye acceso al módulo de Compras.');
    }

    public function test_superadmin_can_override_company_modules_and_grant_access()
    {
        // 1. Create company and check it is blocked from purchases
        [$company, $admin] = $this->createCompanyAndAdmin('basic');
        $adminToken = $this->generateJwtForUser($admin);

        // 2. Create superadmin to make override request
        $superadmin = User::create([
            'name'              => 'Super Admin',
            'email'             => 'superadmin_override_test@flexdash.com',
            'password'          => Hash::make('password'),
            'role'              => 'superadmin',
            'status'            => 'active',
            'company_id'        => null,
        ]);
        $superadmin->email_verified_at = now();
        $superadmin->save();
        $superadminToken = $this->generateJwtForUser($superadmin);

        // 3. Post override custom limits enabling purchases (compras)
        $response = $this->from("/superadmin/companies/{$company->id}")
            ->withCookie('token', $superadminToken)
            ->post("/superadmin/companies/{$company->id}/custom-limits", [
                'override_modules' => '1',
                'active_modules'   => ['ventas', 'clientes', 'caja_chica', 'settings', 'kardex', 'compras'],
            ]);

        $response->assertRedirect("/superadmin/companies/{$company->id}");
        $this->assertTrue($company->fresh()->hasModuleAccess('compras'));

        // 4. Try to access /purchases with company administrator token
        $response = $this->withCookie('token', $adminToken)->get('/purchases');
        
        // Should load successfully now
        $response->assertStatus(200);
    }

    public function test_transaction_limits_block_creating_sales_when_reached()
    {
        [$company, $admin] = $this->createCompanyAndAdmin('basic');
        $token = $this->generateJwtForUser($admin);

        // Set monthly transaction limit to 1
        $company->max_monthly_transactions = 1;
        $company->save();

        // Seed partner, category & product
        $partner = Partner::create([
            'company_id'      => $company->id,
            'type'            => 'cliente',
            'document_number' => '12345678',
            'business_name'   => 'John Doe',
            'is_active'       => true,
        ]);

        $category = Category::create([
            'company_id' => $company->id,
            'name'       => 'Test Cat',
            'is_active'  => true,
        ]);

        $tax = Tax::create([
            'name'      => 'IVA 12%',
            'code'      => 'IVA',
            'rate'      => 12.00,
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id'  => $company->id,
            'category_id' => $category->id,
            'tax_id'      => $tax->id,
            'code'        => 'P001',
            'name'        => 'Product A',
            'price'       => 10.00,
            'cost'        => 5.00,
            'stock'       => 100,
            'is_active'   => true,
        ]);

        // Create first sale (completes successfully)
        $sale = Sale::create([
            'company_id' => $company->id,
            'user_id'    => $admin->id,
            'partner_id' => $partner->id,
            'number'     => '000001',
            'series'     => 'F001',
            'issue_date' => now()->toDateString(),
            'status'     => 'draft',
            'total'      => 10.00,
        ]);

        // Try to store another sale via POST endpoint
        $response = $this->withCookie('token', $token)
            ->from('/sales/create')
            ->post('/sales', [
                'partner_id' => $partner->id,
                'issue_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => 1,
                        'unit_price' => 10.00,
                    ]
                ]
            ]);

        // Verify it was blocked and returned validation error
        $response->assertRedirect('/sales/create');
        $response->assertSessionHasErrors('limit');
    }
}
