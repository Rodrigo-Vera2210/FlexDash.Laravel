<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Registration\Services\RegistrationService;
use App\Modules\Registration\Models\Company;
use App\Modules\Registration\Contracts\EmailVerificationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class RegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_pending_registration_creates_company_and_user()
    {
        $this->fakeNotifications();

        $data = [
            'company_type'   => 'legal_entity',
            'company_name'   => 'Unit Corp',
            'tax_id'         => '987654321',
            'legal_address'  => 'Unit Street 12',
            'city'           => 'Unit City',
            'state_province' => 'Unit State',
            'postal_code'    => '12345',
            'country'        => 'US',
            'name'           => 'Jane Rep',
            'email'          => 'jane.rep@example.com',
            'password'       => 'Secret1@Test',
        ];

        // Ensure service container resolves service and handles creation
        $service = app(RegistrationService::class);
        $user = $service->createPendingRegistration($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('jane.rep@example.com', $user->email);
        $this->assertEquals('company_representative', $user->role);
        $this->assertEquals('pending_verification', $user->status);

        $company = $user->company;
        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals('Unit Corp', $company->name);
        $this->assertEquals('legal_entity', $company->company_type);
        $this->assertTrue((bool)$company->legal_entity_flag);
        $this->assertFalse((bool)$company->natural_entity_flag);
    }
}
