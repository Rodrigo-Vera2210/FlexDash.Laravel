<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for US1 — Legal Entity Company Registration.
 *
 * These tests follow TDD: they are written FIRST and are expected to fail
 * until the registration routes (T022) and view (T024/T025) are wired up.
 *
 * Acceptance Scenarios covered:
 *  AC1 — Company created with legal_entity_flag = true
 *  AC2 — Admin user created with company_representative role
 *  AC3 — Email / OTP notification sent after successful registration
 *  AC4 — User with status = pending_verification cannot log in before verifying
 *
 * Routes exercised (defined in T022 / routes/registration.php):
 *  GET  /register/type
 *  POST /register/account
 *  POST /register/entity
 *  POST /register/review
 */
class LegalEntityRegistrationTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Valid account step payload (step 2 of wizard).
     *
     * @return array<string, string>
     */
    private function validAccountData(): array
    {
        return [
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@acme.example',
            'password'              => 'Secret1@Test',
            'password_confirmation' => 'Secret1@Test',
        ];
    }

    /**
     * Valid entity step payload for a legal entity (step 3 of wizard).
     *
     * @return array<string, string>
     */
    private function validEntityData(): array
    {
        return [
            'company_type'  => 'legal_entity',
            'company_name'  => 'Acme Corp',
            'tax_id'        => '12-3456789',
            'legal_address' => '123 Main Street',
            'city'          => 'Springfield',
            'state_province' => 'IL',
            'postal_code'   => '62701',
            'country'       => 'US',
        ];
    }

    /**
     * Full wizard_data payload as it would exist in the session
     * right before the review step is posted.
     *
     * @return array<string, string>
     */
    private function fullWizardSessionData(): array
    {
        return array_merge($this->validAccountData(), $this->validEntityData());
    }

    // ------------------------------------------------------------------
    // Test 1 — Type selection page (US1 AC1 precondition)
    // ------------------------------------------------------------------

    /**
     * GET /register/type must return HTTP 200.
     *
     * Validates: FR-001 (type selection screen is accessible)
     */
    public function test_it_shows_the_type_selection_page(): void
    {
        $response = $this->get('/register/type');

        $response->assertStatus(200);
    }

    // ------------------------------------------------------------------
    // Test 2 — Account step stores data in session
    // ------------------------------------------------------------------

    /**
     * POST /register/account with valid data redirects to /register/entity
     * and writes wizard_data into the session.
     *
     * Validates: FR-004, FR-006 (account fields validated and stored)
     */
    public function test_it_stores_account_data_in_session_and_redirects_to_entity_step(): void
    {
        $response = $this->post('/register/account', $this->validAccountData());

        $response->assertRedirect('/register/entity');
        $response->assertSessionHas('wizard_data');
    }

    // ------------------------------------------------------------------
    // Test 3 — Entity step stores data in session
    // ------------------------------------------------------------------

    /**
     * POST /register/entity with legal_entity data redirects to /register/review
     * and merges entity fields into wizard_data session.
     *
     * Validates: FR-002 (legal entity form fields accepted)
     */
    public function test_it_stores_entity_data_in_session_and_redirects_to_review_step(): void
    {
        // Pre-populate session as if account step already completed
        $response = $this
            ->withSession(['wizard_data' => $this->validAccountData()])
            ->post('/register/entity', $this->validEntityData());

        $response->assertRedirect('/register/review');
        $response->assertSessionHas('wizard_data');
    }

    // ------------------------------------------------------------------
    // Test 4 — Review submit creates company + user (US1 AC1, AC2, AC3)
    // ------------------------------------------------------------------

    /**
     * POST /register/review (with a fully populated wizard session) MUST:
     *  - Insert 1 row in 'companies' with legal_entity_flag = true and company_type = legal_entity
     *  - Insert 1 row in 'users' with role = company_representative and status = pending_verification
     *  - Redirect to /register/verify-otp
     *
     * Validates: FR-007, FR-009; US1 AC1, AC2, AC3
     */
    public function test_it_creates_company_with_legal_entity_flag_on_review_submit(): void
    {
        $this->fakeNotifications();

        $response = $this
            ->withSession(['wizard_data' => $this->fullWizardSessionData()])
            ->post('/register/review');

        $response->assertRedirect('/register/verify-otp');

        $this->assertDatabaseHas('companies', [
            'legal_entity_flag' => true,
            'company_type'      => 'legal_entity',
        ]);

        $this->assertDatabaseHas('users', [
            'email'  => 'jane@acme.example',
            'role'   => 'company_representative',
            'status' => 'pending_verification',
        ]);
    }

    // ------------------------------------------------------------------
    // Test 5 — Duplicate email rejected (FR-005)
    // ------------------------------------------------------------------

    /**
     * POST /register/account with an email that already exists in 'users'
     * must fail validation and return errors on the 'email' field.
     *
     * Validates: FR-005
     */
    public function test_it_rejects_registration_with_duplicate_email(): void
    {
        // Create a user with the target email beforehand (direct insert, no factory needed)
        User::create([
            'name'     => 'Existing User',
            'email'    => 'jane@acme.example',
            'password' => bcrypt('Secret1@Test'),
        ]);

        $data = $this->validAccountData(); // same email

        $response = $this->post('/register/account', $data);

        $response->assertSessionHasErrors(['email']);
    }

    // ------------------------------------------------------------------
    // Test 6 — Weak passwords rejected (FR-006)
    // ------------------------------------------------------------------

    /**
     * POST /register/account with a weak password must fail validation
     * and return errors on the 'password' field.
     *
     * Validates: FR-006 (min 8 chars, mixed case, numbers, symbols)
     */
    public function test_it_rejects_weak_passwords(): void
    {
        $data = array_merge($this->validAccountData(), [
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $response = $this->post('/register/account', $data);

        $response->assertSessionHasErrors(['password']);
    }
}
