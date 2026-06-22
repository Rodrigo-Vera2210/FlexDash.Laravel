<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Plan;
use App\Modules\Registration\Models\Company;
use App\Modules\Billing\Models\BillingConfig;
use App\Modules\Billing\Services\CertificateHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Exception;

class ElectronicBillingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
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

    public function test_tenant_billing_settings_requires_electronic_billing_plan()
    {
        // 1. Company with plan that doesn't have billing
        $planNoBilling = Plan::where('code', 'basic')->first();
        if ($planNoBilling) {
            $planNoBilling->update(['has_electronic_billing' => false, 'monthly_invoice_limit' => 0]);
        } else {
            $planNoBilling = Plan::create([
                'name' => 'No Billing Plan',
                'code' => 'basic',
                'price' => 10,
                'max_admins' => 1,
                'max_sellers' => 2,
                'max_monthly_transactions' => 50,
                'modules' => [],
                'is_active' => true
            ]);
            $planNoBilling->has_electronic_billing = false;
            $planNoBilling->save();
        }

        $company = Company::create([
            'name' => 'No Billing Company',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'basic',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $user = User::create([
            'name' => 'John Seller',
            'email' => 'john.seller@example.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->get(route('billing.settings.index'));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_tenant_billing_settings_form_renders_for_valid_plan()
    {
        $planWithBilling = Plan::where('code', 'standard')->first();
        if ($planWithBilling) {
            $planWithBilling->update(['has_electronic_billing' => true, 'monthly_invoice_limit' => 200]);
        } else {
            $planWithBilling = Plan::create([
                'name' => 'Billing Plan',
                'code' => 'standard',
                'price' => 29,
                'max_admins' => 2,
                'max_sellers' => 5,
                'max_monthly_transactions' => 200,
                'modules' => [],
                'is_active' => true
            ]);
            $planWithBilling->has_electronic_billing = true;
            $planWithBilling->save();
        }

        $company = Company::create([
            'name' => 'Billing Company',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'standard',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $user = User::create([
            'name' => 'John Owner',
            'email' => 'john.owner@example.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->get(route('billing.settings.index'));
        $response->assertStatus(200);
        $response->assertViewIs('billing.settings.config');
    }

    public function test_tenant_billing_settings_validates_and_stores_certificate()
    {
        $planWithBilling = Plan::where('code', 'standard')->first();
        if ($planWithBilling) {
            $planWithBilling->update(['has_electronic_billing' => true, 'monthly_invoice_limit' => 200]);
        } else {
            $planWithBilling = Plan::create([
                'name' => 'Billing Plan',
                'code' => 'standard',
                'price' => 29,
                'max_admins' => 2,
                'max_sellers' => 5,
                'max_monthly_transactions' => 200,
                'modules' => [],
                'is_active' => true
            ]);
            $planWithBilling->has_electronic_billing = true;
            $planWithBilling->save();
        }

        $company = Company::create([
            'name' => 'Billing Company',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'standard',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $user = User::create([
            'name' => 'John Owner',
            'email' => 'john.owner@example.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        // Mock CertificateHelper
        $this->mock(CertificateHelper::class, function ($mock) {
            $mock->shouldReceive('extractMetadata')
                ->andReturn([
                    'owner_name' => 'FlexDash Test Certificate',
                    'expires_at' => now()->addYear(),
                    'subject'    => [],
                    'issuer'     => [],
                ]);
        });

        $certificateFile = UploadedFile::fake()->create('firmadigital.p12', 100);
        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->post(route('billing.settings.store'), [
            'establishment' => '002',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'certificate' => $certificateFile,
            'password' => 'secret123'
        ]);

        $response->assertRedirect(route('billing.settings.index'));
        $response->assertSessionHas('success');

        $config = BillingConfig::where('company_id', $company->id)->first();
        $this->assertNotNull($config);
        $this->assertEquals('002', $config->establishment);
        $this->assertEquals('001', $config->emission_point);
        $this->assertEquals(10, $config->last_sequence);
        $this->assertEquals('pruebas', $config->environment);

        $certificate = \App\Modules\Billing\Models\CompanyCertificate::where('company_id', $company->id)->first();
        $this->assertNotNull($certificate);
        $this->assertEquals('secret123', $certificate->decrypted_password);
        $this->assertNotNull($certificate->certificate_path);
        $this->assertTrue(Storage::exists($certificate->certificate_path));
        $this->assertTrue($certificate->is_default);
    }

    public function test_tenant_billing_settings_fails_with_invalid_certificate_password()
    {
        $planWithBilling = Plan::where('code', 'standard')->first();
        if ($planWithBilling) {
            $planWithBilling->update(['has_electronic_billing' => true, 'monthly_invoice_limit' => 200]);
        } else {
            $planWithBilling = Plan::create([
                'name' => 'Billing Plan',
                'code' => 'standard',
                'price' => 29,
                'max_admins' => 2,
                'max_sellers' => 5,
                'max_monthly_transactions' => 200,
                'modules' => [],
                'is_active' => true
            ]);
            $planWithBilling->has_electronic_billing = true;
            $planWithBilling->save();
        }

        $company = Company::create([
            'name' => 'Billing Company',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'standard',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $user = User::create([
            'name' => 'John Owner',
            'email' => 'john.owner@example.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        // Mock CertificateHelper to throw error
        $this->mock(CertificateHelper::class, function ($mock) {
            $mock->shouldReceive('extractMetadata')
                ->andThrow(new Exception("Error al leer el certificado digital: Clave de firma incorrecta o archivo dañado."));
        });

        $certificateFile = UploadedFile::fake()->create('firmadigital.p12', 100);
        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->post(route('billing.settings.store'), [
            'establishment' => '002',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'certificate' => $certificateFile,
            'password' => 'wrong_password'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Error al leer el certificado digital: Clave de firma incorrecta o archivo dañado.');
        
        $this->assertNull(BillingConfig::where('company_id', $company->id)->first());
    }

    public function test_superadmin_billing_settings_flow()
    {
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@flexdash.com',
            'password' => bcrypt('password123'),
            'role' => 'superadmin',
            'status' => 'active'
        ]);
        $superadmin->email_verified_at = now();
        $superadmin->save();

        // Mock CertificateHelper
        $this->mock(CertificateHelper::class, function ($mock) {
            $mock->shouldReceive('extractMetadata')
                ->andReturn([
                    'owner_name' => 'FlexDash Test Certificate',
                    'expires_at' => now()->addYear(),
                    'subject'    => [],
                    'issuer'     => [],
                ]);
        });

        $certificateFile = UploadedFile::fake()->create('platform.p12', 100);
        $token = $this->generateJwtForUser($superadmin);

        // 1. Visit SuperAdmin index
        $response = $this->withCookie('token', $token)->get(route('superadmin.billing.index'));
        $response->assertStatus(200);
        $response->assertViewIs('billing.superadmin.billing');

        // 2. Store settings
        $response = $this->withCookie('token', $token)->post(route('superadmin.billing.store'), [
            'establishment' => '001',
            'emission_point' => '001',
            'last_sequence' => 100,
            'environment' => 'produccion',
            'certificate' => $certificateFile,
            'password' => 'platform_pass'
        ]);

        $response->assertRedirect(route('superadmin.billing.index'));
        $response->assertSessionHas('success');

        $config = BillingConfig::whereNull('company_id')->first();
        $this->assertNotNull($config);
        $this->assertEquals('001', $config->establishment);
        $this->assertEquals(100, $config->last_sequence);
        $this->assertEquals('produccion', $config->environment);

        $certificate = \App\Modules\Billing\Models\CompanyCertificate::whereNull('company_id')->first();
        $this->assertNotNull($certificate);
        $this->assertEquals('platform_pass', $certificate->decrypted_password);
        $this->assertTrue(Storage::exists($certificate->certificate_path));
        $this->assertTrue($certificate->is_default);
    }

    public function test_tenant_billing_settings_fails_when_certificate_ruc_does_not_match_company_tax_id()
    {
        $planWithBilling = Plan::where('code', 'standard')->first() ?: Plan::create([
            'name' => 'Billing Plan',
            'code' => 'standard',
            'price' => 29,
            'max_admins' => 2,
            'max_sellers' => 5,
            'max_monthly_transactions' => 200,
            'modules' => [],
            'is_active' => true,
            'has_electronic_billing' => true
        ]);
        $planWithBilling->update(['has_electronic_billing' => true]);

        $company = Company::create([
            'name' => 'Billing Company',
            'company_type' => 'legal_entity',
            'tax_id' => '0999999999001', // Different RUC
            'subscription_plan' => 'standard',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $user = User::create([
            'name' => 'John Owner',
            'email' => 'john.owner.ruc@example.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        // Mock CertificateHelper returning a different RUC
        $this->mock(CertificateHelper::class, function ($mock) {
            $mock->shouldReceive('extractMetadata')
                ->andReturn([
                    'owner_name' => 'FlexDash Test Certificate',
                    'expires_at' => now()->addYear(),
                    'subject'    => [],
                    'issuer'     => [],
                    'ruc'        => '0803592435001', // Non-matching RUC
                    'cedula'     => '0803592435',
                ]);
        });

        $certificateFile = UploadedFile::fake()->create('firmadigital.p12', 100);
        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->post(route('billing.settings.store'), [
            'establishment' => '002',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'certificate' => $certificateFile,
            'password' => 'secret123'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('no coincide con el RUC de la empresa', session('error'));
    }

    public function test_tenant_billing_settings_succeeds_when_certificate_ruc_matches_company_tax_id()
    {
        $planWithBilling = Plan::where('code', 'standard')->first() ?: Plan::create([
            'name' => 'Billing Plan',
            'code' => 'standard',
            'price' => 29,
            'max_admins' => 2,
            'max_sellers' => 5,
            'max_monthly_transactions' => 200,
            'modules' => [],
            'is_active' => true,
            'has_electronic_billing' => true
        ]);
        $planWithBilling->update(['has_electronic_billing' => true]);

        $company = Company::create([
            'name' => 'Billing Company',
            'company_type' => 'legal_entity',
            'tax_id' => '0803592435001', // Same RUC
            'subscription_plan' => 'standard',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $user = User::create([
            'name' => 'John Owner',
            'email' => 'john.owner.match@example.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        // Mock CertificateHelper returning same RUC
        $this->mock(CertificateHelper::class, function ($mock) {
            $mock->shouldReceive('extractMetadata')
                ->andReturn([
                    'owner_name' => 'FlexDash Test Certificate',
                    'expires_at' => now()->addYear(),
                    'subject'    => [],
                    'issuer'     => [],
                    'ruc'        => '0803592435001',
                    'cedula'     => '0803592435',
                ]);
        });

        $certificateFile = UploadedFile::fake()->create('firmadigital.p12', 100);
        $token = $this->generateJwtForUser($user);

        $response = $this->withCookie('token', $token)->post(route('billing.settings.store'), [
            'establishment' => '002',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'certificate' => $certificateFile,
            'password' => 'secret123'
        ]);

        $response->assertRedirect(route('billing.settings.index'));
        $response->assertSessionHas('success');
    }

    public function test_tenant_certificate_limit_enforcement()
    {
        // Setup Plan Basic (max 1 certificate) and Standard (max 3 certificates)
        $basicPlan = Plan::where('code', 'basic')->first() ?: Plan::create([
            'name' => 'Basic Plan',
            'code' => 'basic',
            'price' => 10,
            'max_admins' => 1,
            'max_sellers' => 2,
            'max_monthly_transactions' => 50,
            'modules' => [],
            'is_active' => true,
            'has_electronic_billing' => true,
            'max_certificates' => 1
        ]);
        $basicPlan->update(['has_electronic_billing' => true, 'max_certificates' => 1]);

        $standardPlan = Plan::where('code', 'standard')->first() ?: Plan::create([
            'name' => 'Standard Plan',
            'code' => 'standard',
            'price' => 29,
            'max_admins' => 2,
            'max_sellers' => 5,
            'max_monthly_transactions' => 200,
            'modules' => [],
            'is_active' => true,
            'has_electronic_billing' => true,
            'max_certificates' => 3
        ]);
        $standardPlan->update(['has_electronic_billing' => true, 'max_certificates' => 3]);

        $company = Company::create([
            'name' => 'Limit Company',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'basic',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $user = User::create([
            'name' => 'Limit User',
            'email' => 'limit@example.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        $token = $this->generateJwtForUser($user);

        // Mock CertificateHelper
        $this->mock(CertificateHelper::class, function ($mock) {
            $mock->shouldReceive('extractMetadata')
                ->andReturn([
                    'owner_name' => 'FlexDash Test Certificate',
                    'expires_at' => now()->addYear(),
                    'subject'    => [],
                    'issuer'     => [],
                ]);
        });

        // 1. Upload first certificate -> success (count = 1)
        $certificateFile1 = UploadedFile::fake()->create('firmadigital1.p12', 100);
        $response = $this->withCookie('token', $token)->post(route('billing.settings.store'), [
            'establishment' => '002',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'certificate' => $certificateFile1,
            'password' => 'secret123'
        ]);
        $response->assertRedirect(route('billing.settings.index'));
        $response->assertSessionHas('success');
        $this->assertEquals(1, $company->companyCertificates()->count());

        // 2. Try uploading second certificate under Basic plan -> failure
        $certificateFile2 = UploadedFile::fake()->create('firmadigital2.p12', 100);
        $response = $this->withCookie('token', $token)->post(route('billing.settings.store'), [
            'establishment' => '002',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'certificate' => $certificateFile2,
            'password' => 'secret123'
        ]);
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Límite superado', session('error'));
        $this->assertEquals(1, $company->companyCertificates()->count());

        // 3. Upgrade to standard plan (limit = 3)
        $company->update(['subscription_plan' => 'standard']);

        // 4. Upload two more certificates -> success (count = 3)
        $response = $this->withCookie('token', $token)->post(route('billing.settings.store'), [
            'establishment' => '002',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'certificate' => $certificateFile2,
            'password' => 'secret123'
        ]);
        $response->assertSessionHas('success');

        $certificateFile3 = UploadedFile::fake()->create('firmadigital3.p12', 100);
        $response = $this->withCookie('token', $token)->post(route('billing.settings.store'), [
            'establishment' => '002',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'certificate' => $certificateFile3,
            'password' => 'secret123'
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals(3, $company->companyCertificates()->count());

        // 5. Try uploading fourth certificate under Standard plan -> failure
        $certificateFile4 = UploadedFile::fake()->create('firmadigital4.p12', 100);
        $response = $this->withCookie('token', $token)->post(route('billing.settings.store'), [
            'establishment' => '002',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'certificate' => $certificateFile4,
            'password' => 'secret123'
        ]);
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Límite superado', session('error'));
        $this->assertEquals(3, $company->companyCertificates()->count());
    }

    public function test_default_certificate_assignment_and_sync()
    {
        $plan = Plan::where('code', 'standard')->first() ?: Plan::create([
            'name' => 'Standard Plan',
            'code' => 'standard',
            'price' => 29,
            'max_admins' => 2,
            'max_sellers' => 5,
            'max_monthly_transactions' => 200,
            'modules' => [],
            'is_active' => true,
            'has_electronic_billing' => true,
            'max_certificates' => 3
        ]);
        $plan->update(['has_electronic_billing' => true, 'max_certificates' => 3]);

        $company = Company::create([
            'name' => 'Default Sync Company',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'standard',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $user = User::create([
            'name' => 'Default Sync User',
            'email' => 'defaultsync@example.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        $token = $this->generateJwtForUser($user);

        // Mock CertificateHelper
        $this->mock(CertificateHelper::class, function ($mock) {
            $mock->shouldReceive('extractMetadata')
                ->andReturn([
                    'owner_name' => 'FlexDash Test Certificate',
                    'expires_at' => now()->addYear(),
                    'subject'    => [],
                    'issuer'     => [],
                ]);
        });

        // 1. Upload certificate 1 (should automatically be default since count was 0)
        $certificateFile1 = UploadedFile::fake()->create('firmadigital1.p12', 100);
        $this->withCookie('token', $token)->post(route('billing.settings.store'), [
            'establishment' => '002',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'certificate' => $certificateFile1,
            'password' => 'secret123',
            'is_default' => '0'
        ]);

        $cert1 = $company->companyCertificates()->first();
        $this->assertTrue($cert1->is_default);

        // 2. Upload certificate 2 with is_default = 1
        $certificateFile2 = UploadedFile::fake()->create('firmadigital2.p12', 100);
        $this->withCookie('token', $token)->post(route('billing.settings.store'), [
            'establishment' => '002',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'certificate' => $certificateFile2,
            'password' => 'secret123',
            'is_default' => '1'
        ]);

        $cert1->refresh();
        $cert2 = $company->companyCertificates()->where('id', '!=', $cert1->id)->first();
        $this->assertFalse($cert1->is_default);
        $this->assertTrue($cert2->is_default);

        // 3. Set cert1 back to default via controller action
        $response = $this->withCookie('token', $token)->post(route('billing.settings.certificates.default', $cert1));
        $response->assertRedirect();
        
        $cert1->refresh();
        $cert2->refresh();
        $this->assertTrue($cert1->is_default);
        $this->assertFalse($cert2->is_default);
    }

    public function test_delete_certificate_behavior()
    {
        $company = Company::create([
            'name' => 'Delete Company',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'standard',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $user = User::create([
            'name' => 'Delete User',
            'email' => 'delete@example.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        $token = $this->generateJwtForUser($user);

        // Create certificates directly to bypass validation for speed
        $cert1 = \App\Modules\Billing\Models\CompanyCertificate::create([
            'company_id' => $company->id,
            'certificate_path' => 'secure_certificates/dummy1.p12',
            'certificate_password' => 'pass1',
            'certificate_expires_at' => now()->addYear(),
            'owner_name' => 'Cert 1',
            'is_default' => true
        ]);

        $cert2 = \App\Modules\Billing\Models\CompanyCertificate::create([
            'company_id' => $company->id,
            'certificate_path' => 'secure_certificates/dummy2.p12',
            'certificate_password' => 'pass2',
            'certificate_expires_at' => now()->addYear(),
            'owner_name' => 'Cert 2',
            'is_default' => false
        ]);

        $this->assertTrue($cert1->is_default);
        $this->assertFalse($cert2->is_default);

        // Delete default cert1 -> cert2 should become default automatically
        $response = $this->withCookie('token', $token)->delete(route('billing.settings.certificates.destroy', $cert1));
        $response->assertRedirect();
        
        $cert2->refresh();
        $this->assertTrue($cert2->is_default);
        $this->assertEquals(1, $company->companyCertificates()->count());

        // Delete cert2
        $response = $this->withCookie('token', $token)->delete(route('billing.settings.certificates.destroy', $cert2));
        $response->assertRedirect();
        $this->assertEquals(0, $company->companyCertificates()->count());
    }

    public function test_invoicing_uses_custom_selected_certificate()
    {
        \Illuminate\Support\Facades\Mail::fake();

        $company = Company::create([
            'name' => 'Invoice Company',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'standard',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
            'has_electronic_billing' => true,
        ]);

        $user = User::create([
            'name' => 'Invoice User',
            'email' => 'invoice@example.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'status' => 'active',
            'company_id' => $company->id
        ]);
        $user->email_verified_at = now();
        $user->save();

        $token = $this->generateJwtForUser($user);

        // 1. Create a billing config
        BillingConfig::create([
            'company_id' => $company->id,
            'establishment' => '001',
            'emission_point' => '001',
            'last_sequence' => 10,
            'environment' => 'pruebas',
            'is_active' => true,
        ]);

        // 2. Create 2 certificates
        $cert1 = \App\Modules\Billing\Models\CompanyCertificate::create([
            'company_id' => $company->id,
            'certificate_path' => 'secure_certificates/dummy1.p12',
            'certificate_password' => 'pass1',
            'certificate_expires_at' => now()->addYear(),
            'owner_name' => 'Cert 1',
            'is_default' => true
        ]);

        $cert2 = \App\Modules\Billing\Models\CompanyCertificate::create([
            'company_id' => $company->id,
            'certificate_path' => 'secure_certificates/dummy2.p12',
            'certificate_password' => 'pass2',
            'certificate_expires_at' => now()->addYear(),
            'owner_name' => 'Cert 2',
            'is_default' => false
        ]);

        // Put dummy cert files in Storage fake
        Storage::put('secure_certificates/dummy1.p12', 'dummy');
        Storage::put('secure_certificates/dummy2.p12', 'dummy');

        // 3. Create a paid sale
        $partner = \App\Modules\Partner\Models\Partner::create([
            'company_id' => $company->id,
            'type' => 'cliente',
            'business_name' => 'Client Test',
            'document_type' => 'CI',
            'document_number' => '1712345678',
            'email' => 'client@example.com',
            'is_active' => true,
        ]);

        $sale = \App\Modules\Sale\Models\Sale::create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'user_id' => $user->id,
            'series' => '001',
            'number' => '000000123',
            'issue_date' => now(),
            'due_date' => now(),
            'status' => \App\Modules\Sale\Models\Sale::STATUS_PAID,
            'currency' => 'USD',
            'subtotal' => 100.00,
            'tax_amount' => 12.00,
            'discount' => 0.00,
            'total' => 112.00,
            'paid_amount' => 112.00,
            'pending_balance' => 0.00,
        ]);

        // 4. Mock the internal generators and soap client
        $this->mock(\App\Modules\Billing\Services\XmlGeneratorService::class, function ($mock) {
            $mock->shouldReceive('generateInvoiceXml')->andReturn('<xml>invoice</xml>');
        });
        $this->mock(\App\Modules\Billing\Services\XmlSignerService::class, function ($mock) {
            $mock->shouldReceive('signXml')->andReturn('<xml>signed</xml>');
        });
        $this->mock(\App\Modules\Billing\Services\SriSoapClientService::class, function ($mock) {
            $mock->shouldReceive('sendToReception')->andReturn(['status' => 'RECIBIDA']);
            $mock->shouldReceive('queryAuthorization')->andReturn([
                'status' => 'AUTORIZADO',
                'date' => now()->toIso8601String(),
                'xml' => '<xml>authorized</xml>'
            ]);
        });
        $this->mock(\App\Modules\Billing\Services\RideGeneratorService::class, function ($mock) {
            $mock->shouldReceive('generateRidePdf')->andReturn('secure_invoices/dummy.pdf');
        });

        // 5. Trigger invoicing passing cert2 explicitly
        $response = $this->withCookie('token', $token)->post(route('billing.invoices.store'), [
            'sale_id' => $sale->id,
            'certificate_id' => $cert2->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $invoice = \App\Modules\Billing\Models\ElectronicInvoice::where('invoicable_id', $sale->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($cert2->id, $invoice->certificate_id);
    }
}
