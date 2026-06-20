<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for US2 — Natural Person Self-Registration.
 */
class NaturalPersonRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validAccountData(): array
    {
        return [
            'name'                  => 'María García',
            'email'                 => 'maria@example.com',
            'password'              => 'Secret1@Test',
            'password_confirmation' => 'Secret1@Test',
        ];
    }

    private function validEntityData(): array
    {
        return [
            'company_type'   => 'natural_person',
            'full_name'      => 'María García',
            'id_number'      => '12345678Z',
            'address'        => 'Calle Mayor 45',
            'city'           => 'Madrid',
            'state_province' => 'Madrid',
            'postal_code'    => '28001',
            'country'        => 'ES',
        ];
    }

    private function fullWizardSessionData(): array
    {
        return array_merge($this->validAccountData(), $this->validEntityData());
    }

    public function test_it_stores_natural_person_entity_data_in_session_and_redirects_to_billing(): void
    {
        $response = $this
            ->withSession(['wizard_data' => $this->validAccountData()])
            ->post('/register/entity', $this->validEntityData());

        $response->assertRedirect('/register/billing');
        $response->assertSessionHas('wizard_data');
    }

    public function test_it_creates_company_and_user_with_natural_person_data_on_review_submit(): void
    {
        $this->fakeNotifications();

        $response = $this
            ->withSession(['wizard_data' => $this->fullWizardSessionData()])
            ->post('/register/review');

        $response->assertRedirect('/register/verify-otp');

        // Verify company record
        $this->assertDatabaseHas('companies', [
            'company_type'        => 'natural_person',
            'name'                => 'María García',
            'address'             => 'Calle Mayor 45',
            'natural_entity_flag' => true,
            'legal_entity_flag'   => false,
        ]);

        // Verify user record
        $this->assertDatabaseHas('users', [
            'email'  => 'maria@example.com',
            'role'   => 'owner',
            'status' => 'pending_verification',
        ]);
    }
}
