<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tax;
use App\Modules\Registration\Models\Company;
use App\Modules\Partner\Models\Partner;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleDetail;
use App\Modules\Product\Models\Product;
use App\Modules\Service\Models\Service;
use App\Modules\Billing\Models\BillingConfig;
use App\Modules\Billing\Models\ElectronicInvoice;
use App\Modules\Billing\Services\ElectronicInvoicingService;
use App\Modules\Billing\Services\XmlSignerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceSaleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();

        // Mock XmlSignerService so we don't need real .p12 certificates
        $this->mock(XmlSignerService::class, function ($mock) {
            $mock->shouldReceive('signXml')
                ->andReturn('<?xml version="1.0" encoding="UTF-8"?><factura id="comprobante" version="2.1.0"><infoTributaria><claveAcceso>0206201801179125611500120010010000000011234567814</claveAcceso></infoTributaria></factura>');
        });
    }

    protected function setupBillingConfig(Company $company, string $password = 'secret123'): BillingConfig
    {
        $fileName = 'cert_' . $company->id . '_' . time() . '.p12';
        $securePath = 'secure_certificates/' . $fileName;
        Storage::put($securePath, 'dummy_p12_content');

        \App\Modules\Billing\Models\CompanyCertificate::create([
            'company_id' => $company->id,
            'certificate_path' => $securePath,
            'certificate_password' => $password,
            'certificate_expires_at' => now()->addYear(),
            'owner_name' => $company->name,
            'ruc' => $company->tax_id ?: '1799999999001',
            'is_default' => true
        ]);

        return BillingConfig::create([
            'company_id' => $company->id,
            'establishment' => '001',
            'emission_point' => '001',
            'last_sequence' => 0,
            'environment' => 'pruebas',
            'is_active' => true,
        ]);
    }

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

    public function test_can_create_sale_with_only_services()
    {
        $company = Company::create([
            'name' => 'Test Tenant',
            'tax_id' => '1799999999001',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'premium',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
            'has_electronic_billing' => true,
        ]);
        $user = User::create([
            'name' => 'Owner User',
            'email' => 'owner@test.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        $partner = Partner::create([
            'company_id' => $company->id,
            'business_name' => 'CLIENT',
            'document_type' => 'CI',
            'document_number' => '1712345678',
            'is_active' => true,
            'type' => 'cliente'
        ]);

        $tax = Tax::create([
            'company_id' => $company->id,
            'name' => 'IVA 12%',
            'code' => 'IVA12',
            'rate' => 12.00,
            'is_active' => true
        ]);

        $service = Service::create([
            'company_id' => $company->id,
            'code' => 'SERV-TEST',
            'name' => 'Asesoría VIP',
            'price' => 100.00,
            'cost' => 30.00,
            'tax_id' => $tax->id,
            'is_active' => true
        ]);

        $token = $this->generateJwtForUser($user);

        // Store sale
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/sales', [
                'partner_id' => $partner->id,
                'tax_id' => $tax->id,
                'issue_date' => now()->format('Y-m-d'),
                'items' => [
                    [
                        'service_id' => $service->id,
                        'quantity' => 2.00,
                        'unit_price' => 100.00,
                    ]
                ]
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertEquals(Sale::STATUS_DRAFT, $sale->status);
        $this->assertEquals(200.00, $sale->subtotal);
        $this->assertEquals(24.00, $sale->tax_amount); // 12% of 200
        $this->assertEquals(224.00, $sale->total);

        $detail = $sale->details->first();
        $this->assertNotNull($detail);
        $this->assertTrue($detail->isService());
        $this->assertFalse($detail->isProduct());
        $this->assertEquals($service->id, $detail->service_id);
        $this->assertEquals(30.00, $detail->cost_price);
        $this->assertEquals(200.00, $detail->subtotal);
    }

    public function test_can_create_sale_with_mixed_items_and_approving_affects_only_product_stock()
    {
        $company = Company::create([
            'name' => 'Test Tenant 2',
            'tax_id' => '1799999999002',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'premium',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
            'has_electronic_billing' => true,
        ]);
        $user = User::create([
            'name' => 'Owner User 2',
            'email' => 'owner2@test.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        // Authenticate user session for internal inventory calls
        $this->actingAs($user);

        $partner = Partner::create([
            'company_id' => $company->id,
            'business_name' => 'CLIENT 2',
            'document_type' => 'CI',
            'document_number' => '1712345679',
            'is_active' => true,
            'type' => 'cliente'
        ]);

        $tax = Tax::create([
            'company_id' => $company->id,
            'name' => 'IVA 12%',
            'code' => 'IVA12',
            'rate' => 12.00,
            'is_active' => true
        ]);

        $category = \App\Modules\Product\Models\Category::create([
            'company_id' => $company->id,
            'name' => 'Accesorios',
            'is_active' => true
        ]);

        $product = Product::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'name' => 'Item físico',
            'code' => 'PROD-PHYS',
            'price' => 50.00,
            'cost' => 20.00,
            'tax_id' => $tax->id,
            'is_active' => true
        ]);

        // Initialize stock
        $inventoryService = app(\App\Services\InventoryService::class);
        $inventoryService->adjust($product, 10.00, 'Stock inicial');
        $this->assertEquals(10.00, $product->fresh()->stock);

        $service = Service::create([
            'company_id' => $company->id,
            'code' => 'SERV-INST',
            'name' => 'Instalación Básica',
            'price' => 40.00,
            'cost' => 10.00,
            'tax_id' => $tax->id,
            'is_active' => true
        ]);

        $token = $this->generateJwtForUser($user);

        // Store mixed sale
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/sales', [
                'partner_id' => $partner->id,
                'tax_id' => $tax->id,
                'issue_date' => now()->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 3.00,
                        'unit_price' => 50.00,
                    ],
                    [
                        'service_id' => $service->id,
                        'quantity' => 1.00,
                        'unit_price' => 40.00,
                    ]
                ]
            ]);

        $response->assertSessionHasNoErrors();

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertEquals(190.00, $sale->subtotal); // (3 * 50) + (1 * 40) = 190
        $this->assertEquals(22.80, $sale->tax_amount); // 12% of 190
        $this->assertEquals(212.80, $sale->total);

        // Approve sale
        $responseApprove = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post("/sales/{$sale->id}/approve");
        $responseApprove->assertRedirect();

        // Product stock should have been deducted (10 - 3 = 7)
        $this->assertEquals(7.00, $product->fresh()->stock);

        // Verify inventory movements
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'quantity' => 3.00,
            'type' => 'salida', // matches exit method's type "salida"
        ]);
        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => null,
            'type' => 'salida',
        ]);
    }

    public function test_electronic_invoicing_xml_for_services()
    {
        $company = Company::create([
            'name' => 'Ecuador POS Store',
            'tax_id' => '1799999999001',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'premium',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
            'has_electronic_billing' => true,
        ]);

        $user = User::create([
            'name' => 'Cashier',
            'email' => 'cashier@ecuadorpos.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);

        $config = $this->setupBillingConfig($company);

        $partner = Partner::create([
            'company_id' => $company->id,
            'business_name' => 'JUAN PEREZ',
            'document_type' => 'CI',
            'document_number' => '1712345678',
            'email' => 'juan.perez@example.com',
            'phone' => '0999999999',
            'address' => 'Av. Amazonas, Quito',
            'is_active' => true,
            'type' => 'cliente'
        ]);

        $tax = Tax::create([
            'company_id' => $company->id,
            'name' => 'IVA 12%',
            'code' => 'IVA12',
            'rate' => 12.00,
            'is_active' => true
        ]);

        $service = Service::create([
            'company_id' => $company->id,
            'code' => 'SERV-REP',
            'name' => 'Reparación Técnica',
            'price' => 80.00,
            'cost' => 10.00,
            'tax_id' => $tax->id,
            'is_active' => true
        ]);

        $sale = Sale::create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'user_id' => $user->id,
            'tax_id' => $tax->id,
            'series' => '001',
            'number' => '000000123',
            'issue_date' => now(),
            'due_date' => now(),
            'status' => Sale::STATUS_PAID,
            'currency' => 'USD',
            'subtotal' => 80.00,
            'tax_amount' => 9.60,
            'discount' => 0.00,
            'total' => 89.60,
            'paid_amount' => 89.60,
            'pending_balance' => 0.00,
        ]);

        $detail = SaleDetail::create([
            'sale_id' => $sale->id,
            'service_id' => $service->id,
            'quantity' => 1.00,
            'unit_price' => 80.00,
            'subtotal' => 80.00,
            'tax_amount' => 9.60,
            'discount' => 0.00
        ]);

        // Test XmlGeneratorService directly
        $xmlGenerator = app(\App\Modules\Billing\Services\XmlGeneratorService::class);
        $xmlContent = $xmlGenerator->generateInvoiceXml($sale, $config, 1);
        
        // Assert XML contains service details
        $this->assertStringContainsString('SERV-REP', $xmlContent);
    }
}
