<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tax;
use App\Models\PaymentMethod;
use App\Modules\Registration\Models\Company;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Category;
use App\Modules\Partner\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiTenantUniqueConstraintsTest extends TestCase
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
            'active_modules'      => ['ventas', 'clientes', 'caja_chica', 'settings', 'kardex', 'compras', 'proveedores'],
        ]);

        $user = User::create([
            'name'              => $name . ' Owner',
            'email'             => $email,
            'password'          => Hash::make('password'),
            'company_id'        => $company->id,
            'role'              => 'owner',
            'status'            => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        $token = $this->generateJwtForUser($user);

        return [$company, $user, $token];
    }

    public function test_product_code_uniqueness_is_scoped_by_company()
    {
        [$companyA, $userA, $tokenA] = $this->createCompanyAndAdmin('Company A', 'ownerA@company.com');
        [$companyB, $userB, $tokenB] = $this->createCompanyAndAdmin('Company B', 'ownerB@company.com');

        // Create category and tax for Company A
        $this->actingAs($userA);
        $categoryA = Category::create(['name' => 'Cat A', 'is_active' => true]);
        $taxA = Tax::create(['name' => 'Tax A', 'code' => 'T1', 'rate' => 18, 'is_active' => true]);

        // Post request as Company A to create a product
        $response = $this->withCookie('token', $tokenA)->post('/products', [
            'category_id' => $categoryA->id,
            'tax_id' => $taxA->id,
            'code' => 'PROD-001',
            'name' => 'Product 1',
            'unit' => 'UND',
            'cost' => 10,
            'price' => 15,
            'minimum_stock' => 0,
        ]);
        $response->assertRedirect();
        
        $this->assertDatabaseHas('products', [
            'company_id' => $companyA->id,
            'code' => 'PROD-001',
            'name' => 'Product 1',
        ]);

        // Switch to Company B
        $this->actingAs($userB);
        $categoryB = Category::create(['name' => 'Cat B', 'is_active' => true]);
        $taxB = Tax::create(['name' => 'Tax B', 'code' => 'T2', 'rate' => 18, 'is_active' => true]);

        // Post request as Company B to create a product with the SAME code
        $response = $this->withCookie('token', $tokenB)->post('/products', [
            'category_id' => $categoryB->id,
            'tax_id' => $taxB->id,
            'code' => 'PROD-001',
            'name' => 'Product B1',
            'unit' => 'UND',
            'cost' => 20,
            'price' => 25,
            'minimum_stock' => 0,
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('products', [
            'company_id' => $companyB->id,
            'code' => 'PROD-001',
            'name' => 'Product B1',
        ]);

        // Try to create duplicate product code PROD-001 as Company A again
        $response = $this->withCookie('token', $tokenA)->post('/products', [
            'category_id' => $categoryA->id,
            'tax_id' => $taxA->id,
            'code' => 'PROD-001',
            'name' => 'Product 2',
            'unit' => 'UND',
            'cost' => 10,
            'price' => 15,
            'minimum_stock' => 0,
        ]);
        $response->assertSessionHasErrors(['code']);
    }

    public function test_tax_code_uniqueness_is_scoped_by_company()
    {
        [$companyA, $userA, $tokenA] = $this->createCompanyAndAdmin('Company A', 'ownerA@company.com');
        [$companyB, $userB, $tokenB] = $this->createCompanyAndAdmin('Company B', 'ownerB@company.com');

        // Create tax for Company A
        $response = $this->withCookie('token', $tokenA)->post('/settings/catalogs/taxes', [
            'name' => 'Tax A',
            'code' => 'IGV',
            'rate' => 18,
        ]);
        $response->assertRedirect();
        
        $this->assertDatabaseHas('taxes', [
            'company_id' => $companyA->id,
            'code' => 'IGV',
        ]);

        // Create same tax for Company B
        $response = $this->withCookie('token', $tokenB)->post('/settings/catalogs/taxes', [
            'name' => 'Tax B',
            'code' => 'IGV',
            'rate' => 18,
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('taxes', [
            'company_id' => $companyB->id,
            'code' => 'IGV',
        ]);

        // Duplicate tax for Company A
        $response = $this->withCookie('token', $tokenA)->post('/settings/catalogs/taxes', [
            'name' => 'Tax A Duplicate',
            'code' => 'IGV',
            'rate' => 18,
        ]);
        $response->assertSessionHasErrors(['code']);
    }

    public function test_category_name_uniqueness_is_scoped_by_company()
    {
        [$companyA, $userA, $tokenA] = $this->createCompanyAndAdmin('Company A', 'ownerA@company.com');
        [$companyB, $userB, $tokenB] = $this->createCompanyAndAdmin('Company B', 'ownerB@company.com');

        // Create category for Company A
        $response = $this->withCookie('token', $tokenA)->post('/settings/catalogs/categories', [
            'name' => 'Electrónica',
            'description' => 'Dispositivos electrónicos',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'company_id' => $companyA->id,
            'name' => 'Electrónica',
        ]);

        // Create category for Company B with same name
        $response = $this->withCookie('token', $tokenB)->post('/settings/catalogs/categories', [
            'name' => 'Electrónica',
            'description' => 'Otros',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'company_id' => $companyB->id,
            'name' => 'Electrónica',
        ]);

        // Duplicate category for Company A
        $response = $this->withCookie('token', $tokenA)->post('/settings/catalogs/categories', [
            'name' => 'Electrónica',
            'description' => 'Duplicate',
        ]);
        $response->assertSessionHasErrors(['name']);
    }

    public function test_payment_method_name_uniqueness_is_scoped_by_company()
    {
        [$companyA, $userA, $tokenA] = $this->createCompanyAndAdmin('Company A', 'ownerA@company.com');
        [$companyB, $userB, $tokenB] = $this->createCompanyAndAdmin('Company B', 'ownerB@company.com');

        // Create payment method for Company A
        $response = $this->withCookie('token', $tokenA)->post('/settings/catalogs/payment-methods', [
            'name' => 'Efectivo',
            'description' => 'Cash',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('payment_methods', [
            'company_id' => $companyA->id,
            'name' => 'Efectivo',
        ]);

        // Create payment method for Company B with same name
        $response = $this->withCookie('token', $tokenB)->post('/settings/catalogs/payment-methods', [
            'name' => 'Efectivo',
            'description' => 'Cash B',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('payment_methods', [
            'company_id' => $companyB->id,
            'name' => 'Efectivo',
        ]);

        // Duplicate payment method for Company A
        $response = $this->withCookie('token', $tokenA)->post('/settings/catalogs/payment-methods', [
            'name' => 'Efectivo',
            'description' => 'Duplicate',
        ]);
        $response->assertSessionHasErrors(['name']);
    }

    public function test_partner_document_number_uniqueness_is_scoped_by_company()
    {
        [$companyA, $userA, $tokenA] = $this->createCompanyAndAdmin('Company A', 'ownerA@company.com');
        [$companyB, $userB, $tokenB] = $this->createCompanyAndAdmin('Company B', 'ownerB@company.com');

        // Create partner for Company A
        $response = $this->withCookie('token', $tokenA)->post('/partners', [
            'type' => 'cliente',
            'business_name' => 'Partner A',
            'document_type' => 'RUC',
            'document_number' => '20100047218',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('partners', [
            'company_id' => $companyA->id,
            'document_number' => '20100047218',
        ]);

        // Create partner for Company B with same document number
        $response = $this->withCookie('token', $tokenB)->post('/partners', [
            'type' => 'proveedor',
            'business_name' => 'Partner B',
            'document_type' => 'RUC',
            'document_number' => '20100047218',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('partners', [
            'company_id' => $companyB->id,
            'document_number' => '20100047218',
        ]);

        // Duplicate partner for Company A
        $response = $this->withCookie('token', $tokenA)->post('/partners', [
            'type' => 'cliente',
            'business_name' => 'Partner A Duplicate',
            'document_type' => 'RUC',
            'document_number' => '20100047218',
        ]);
        $response->assertSessionHasErrors(['document_number']);
    }

    public function test_update_validation_ignores_self_but_respects_others_for_same_company()
    {
        [$companyA, $userA, $tokenA] = $this->createCompanyAndAdmin('Company A', 'ownerA@company.com');

        // 1. Products ignore self
        $this->actingAs($userA);
        $category = Category::create(['name' => 'Cat A', 'is_active' => true]);
        $tax = Tax::create(['name' => 'Tax A', 'code' => 'T1', 'rate' => 18, 'is_active' => true]);

        $prod1 = Product::create([
            'category_id' => $category->id,
            'tax_id' => $tax->id,
            'code' => 'PROD-111',
            'name' => 'Product 111',
            'unit' => 'UND',
            'cost' => 10,
            'price' => 15,
            'stock' => 0,
            'minimum_stock' => 0,
            'is_active' => true,
        ]);

        $prod2 = Product::create([
            'category_id' => $category->id,
            'tax_id' => $tax->id,
            'code' => 'PROD-222',
            'name' => 'Product 222',
            'unit' => 'UND',
            'cost' => 10,
            'price' => 15,
            'stock' => 0,
            'minimum_stock' => 0,
            'is_active' => true,
        ]);

        // Update prod1 with its own code (ignore self) -> should succeed
        $response = $this->withCookie('token', $tokenA)->put("/products/{$prod1->id}", [
            'category_id' => $category->id,
            'tax_id' => $tax->id,
            'code' => 'PROD-111',
            'name' => 'Product 111 Updated',
            'unit' => 'UND',
            'cost' => 10,
            'price' => 15,
            'minimum_stock' => 0,
        ]);
        $response->assertRedirect();
        
        $this->assertDatabaseHas('products', [
            'id' => $prod1->id,
            'name' => 'Product 111 Updated',
        ]);

        // Update prod1 with prod2's code -> should fail validation
        $response = $this->withCookie('token', $tokenA)->put("/products/{$prod1->id}", [
            'category_id' => $category->id,
            'tax_id' => $tax->id,
            'code' => 'PROD-222',
            'name' => 'Product 111 Invalid Update',
            'unit' => 'UND',
            'cost' => 10,
            'price' => 15,
            'minimum_stock' => 0,
        ]);
        $response->assertSessionHasErrors(['code']);

        // 2. Taxes ignore self
        $tax1 = Tax::create(['name' => 'Tax 1', 'code' => 'TX1', 'rate' => 10, 'is_active' => true]);
        $tax2 = Tax::create(['name' => 'Tax 2', 'code' => 'TX2', 'rate' => 12, 'is_active' => true]);

        // Update tax1 keeping TX1 -> should succeed
        $response = $this->withCookie('token', $tokenA)->put("/settings/catalogs/taxes/{$tax1->id}", [
            'name' => 'Tax 1 Updated',
            'code' => 'TX1',
            'rate' => 10,
        ]);
        $response->assertRedirect();

        // Update tax1 with TX2 -> should fail
        $response = $this->withCookie('token', $tokenA)->put("/settings/catalogs/taxes/{$tax1->id}", [
            'name' => 'Tax 1 Invalid',
            'code' => 'TX2',
            'rate' => 10,
        ]);
        $response->assertSessionHasErrors(['code']);
    }
}
