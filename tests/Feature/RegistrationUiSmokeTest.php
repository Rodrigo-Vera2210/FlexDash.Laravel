<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationUiSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_wizard_renders_with_brand_header_and_colors()
    {
        $response = $this->get('/register/type');

        $response->assertStatus(200);

        // Assert that the page renders the brand header (FlexDash)
        $response->assertSee('FlexDash');

        // Assert that brand class tokens are present
        $response->assertSee('bg-brand-blue');
        $response->assertSee('bg-brand-yellow');
    }
}
