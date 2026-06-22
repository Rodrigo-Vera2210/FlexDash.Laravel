<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Plan;
use App\Models\Tax;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\SubscriptionPayment;
use App\Modules\Registration\Models\Company;
use App\Modules\Registration\Services\RegistrationService;
use App\Modules\SuperAdmin\Services\SuperAdminService;
use App\Modules\Partner\Models\Partner;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleDetail;
use App\Modules\Product\Models\Product;
use App\Modules\Billing\Models\BillingConfig;
use App\Modules\Billing\Models\ElectronicInvoice;
use App\Modules\Billing\Services\ElectronicInvoicingService;
use App\Modules\Billing\Services\XmlSignerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvoiceGenerationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();

        // Mock XmlSignerService so we don't need real .p12 certificates and openssl configurations
        $this->mock(XmlSignerService::class, function ($mock) {
            $mock->shouldReceive('signXml')
                ->andReturn('<?xml version="1.0" encoding="UTF-8"?><factura id="comprobante" version="2.1.0"><infoTributaria><claveAcceso>0206201801179125611500120010010000000011234567814</claveAcceso></infoTributaria></factura>');
        });
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

    /**
     * Helper to setup a valid dummy billing config for a company.
     */
    protected function setupBillingConfig(?Company $company, string $password = 'secret123'): BillingConfig
    {
        $fileName = 'cert_' . ($company ? $company->id : 'platform') . '_' . time() . '.p12';
        $securePath = 'secure_certificates/' . $fileName;
        Storage::put($securePath, 'dummy_p12_content');

        \App\Modules\Billing\Models\CompanyCertificate::create([
            'company_id' => $company ? $company->id : null,
            'certificate_path' => $securePath,
            'certificate_password' => $password,
            'certificate_expires_at' => now()->addYear(),
            'owner_name' => $company ? $company->name : 'Platform Admin',
            'ruc' => $company ? ($company->tax_id ?: '1799999999001') : null,
            'is_default' => true
        ]);

        return BillingConfig::create([
            'company_id' => $company ? $company->id : null,
            'establishment' => '001',
            'emission_point' => '001',
            'last_sequence' => 0,
            'environment' => 'pruebas',
            'is_active' => true,
        ]);
    }

    public function test_full_electronic_invoicing_flow_for_pos_sale()
    {
        $mailFake = $this->fakeMail();

        // 1. Setup company, plan, user
        $plan = Plan::where('code', 'premium')->first();
        if ($plan) {
            $plan->update(['has_electronic_billing' => true, 'max_monthly_transactions' => 1000]);
        } else {
            $plan = Plan::create([
                'name' => 'Premium Plan',
                'code' => 'premium',
                'price' => 50,
                'max_admins' => 5,
                'max_sellers' => 10,
                'max_monthly_transactions' => 1000,
                'modules' => [],
                'has_electronic_billing' => true,
                'is_active' => true
            ]);
        }

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
        ]);

        $user = User::create([
            'name' => 'Cashier Quito',
            'email' => 'cashier@ecuadorpos.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        // 2. Setup billing config
        $this->setupBillingConfig($company);

        // 3. Create partner
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

        // 4. Setup tax
        $tax = Tax::create([
            'company_id' => $company->id,
            'name' => 'IVA 12%',
            'code' => 'IVA12',
            'rate' => 12.00,
            'is_active' => true
        ]);

        // Create category
        $category = \App\Modules\Product\Models\Category::create([
            'company_id' => $company->id,
            'name' => 'Accesorios',
            'is_active' => true
        ]);

        // 5. Setup product
        $product = Product::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'name' => 'Teclado Gamer',
            'code' => 'TEC-GAM-01',
            'price' => 25.00,
            'tax_id' => $tax->id,
            'is_active' => true
        ]);

        // 6. Create paid sale
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
            'subtotal' => 25.00,
            'tax_amount' => 3.00,
            'discount' => 0.00,
            'total' => 28.00,
            'paid_amount' => 28.00,
            'pending_balance' => 0.00,
            'notes' => 'Test POS sale billing'
        ]);

        $detail = SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1.00,
            'unit_price' => 25.00,
            'subtotal' => 25.00,
            'tax_amount' => 3.00,
            'discount' => 0.00
        ]);

        // 7. Run ElectronicInvoicingService coordinator
        $service = app(ElectronicInvoicingService::class);
        $invoice = $service->process($sale);

        // 8. Assertions
        $this->assertInstanceOf(ElectronicInvoice::class, $invoice);
        $this->assertEquals('authorized', $invoice->status);
        $this->assertEquals($company->id, $invoice->company_id);
        $this->assertEquals('001-001-000000001', $invoice->sequence);
        $this->assertEquals(49, strlen($invoice->access_key));
        
        $this->assertNotNull($invoice->xml_path);
        $this->assertNotNull($invoice->pdf_path);
        $this->assertTrue(Storage::exists($invoice->xml_path));
        $this->assertTrue(Storage::exists($invoice->pdf_path));

        // Assert mail was sent to customer
        $mailFake->assertSent(\App\Modules\Billing\Emails\ElectronicInvoiceMail::class, function ($mail) use ($partner) {
            return $mail->hasTo($partner->email);
        });
    }

    public function test_monthly_billing_limits_enforced()
    {
        // 1. Setup company with limit of 1 invoice
        $plan = Plan::where('code', 'limited')->first();
        if ($plan) {
            $plan->update(['has_electronic_billing' => true, 'monthly_invoice_limit' => 1]);
        } else {
            $plan = Plan::create([
                'name' => 'Limited Plan',
                'code' => 'limited',
                'price' => 15,
                'max_admins' => 1,
                'max_sellers' => 2,
                'max_monthly_transactions' => 100,
                'modules' => [],
                'has_electronic_billing' => true,
                'monthly_invoice_limit' => 1,
                'is_active' => true
            ]);
        }

        $company = Company::create([
            'name' => 'Limited Company',
            'tax_id' => '1799999999002',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'limited',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $user = User::create([
            'name' => 'Limited Owner',
            'email' => 'owner@limited.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        $this->setupBillingConfig($company);

        $partner = Partner::create([
            'company_id' => $company->id,
            'business_name' => 'CLIENT',
            'document_type' => 'CI',
            'document_number' => '1712345678',
            'is_active' => true,
            'type' => 'cliente'
        ]);

        $sale1 = Sale::create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'user_id' => $user->id,
            'status' => Sale::STATUS_PAID,
            'series' => '001',
            'number' => '000000001',
            'issue_date' => now(),
            'due_date' => now(),
            'total' => 10.00
        ]);

        $sale2 = Sale::create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'user_id' => $user->id,
            'status' => Sale::STATUS_PAID,
            'series' => '001',
            'number' => '000000002',
            'issue_date' => now(),
            'due_date' => now(),
            'total' => 20.00
        ]);

        // Process first sale - should succeed
        $service = app(ElectronicInvoicingService::class);
        $invoice1 = $service->process($sale1);
        $this->assertEquals('authorized', $invoice1->status);

        // Process second sale - should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Se ha alcanzado el límite mensual de facturación electrónica");
        
        $service->process($sale2);
    }

    public function test_superadmin_payment_approval_triggers_auto_billing()
    {
        $mailFake = $this->fakeMail();
        $notificationFake = $this->fakeNotifications();

        // 1. Setup platform billing config
        $this->setupBillingConfig(null, 'platform_password');

        // 2. Setup subscribing company and payment
        $companyPlan = Plan::where('code', 'standard')->first();
        if ($companyPlan) {
            $companyPlan->update(['has_electronic_billing' => true, 'price' => 29.00]);
        } else {
            $companyPlan = Plan::create([
                'name' => 'Standard Plan',
                'code' => 'standard',
                'price' => 29,
                'max_admins' => 2,
                'max_sellers' => 5,
                'max_monthly_transactions' => 500,
                'modules' => [],
                'has_electronic_billing' => true,
                'is_active' => true
            ]);
        }

        $company = Company::create([
            'name' => 'New Tenant Corp',
            'tax_id' => '1799999999003',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'standard',
            'subscription_status' => 'pending_approval',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $owner = User::create([
            'name' => 'Tenant Owner',
            'email' => 'owner@newtenant.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'pending_activation',
            'company_id' => $company->id
        ]);
        $owner->email_verified_at = now();
        $owner->save();

        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'plan' => 'standard',
            'bank_origin' => 'Pichincha',
            'account_destination' => 'FlexDash Platform',
            'status' => 'pending',
            'receipt_path' => 'receipts/test.jpg',
            'type' => 'signup'
        ]);

        // 3. Approve company subscription
        $superAdminService = app(SuperAdminService::class);
        $superAdminService->approveCompany($company, $payment->id);

        // 4. Assertions
        $invoice = ElectronicInvoice::where('invoicable_type', SubscriptionPayment::class)
            ->where('invoicable_id', $payment->id)
            ->first();

        $this->assertNotNull($invoice);
        $this->assertEquals('authorized', $invoice->status);
        $this->assertNull($invoice->company_id); // platform billing has null company_id
        $this->assertEquals('001-001-000000001', $invoice->sequence);
        $this->assertTrue(Storage::exists($invoice->xml_path));
        $this->assertTrue(Storage::exists($invoice->pdf_path));

        // Assert mail was sent to company owner
        $mailFake->assertSent(\App\Modules\Billing\Emails\ElectronicInvoiceMail::class, function ($mail) use ($owner) {
            return $mail->hasTo($owner->email);
        });
    }
}
