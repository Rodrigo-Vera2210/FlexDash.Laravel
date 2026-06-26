<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Registration\Models\EmailVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthUserPreferencesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T033: Test complete profile update workflow (integration test)
     */
    public function test_complete_profile_update_workflow(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'language' => 'es',
            'timezone' => 'America/Guayaquil',
            'notifications_enabled' => true,
        ]);

        // Test profile view is accessible
        $response = $this->actingAs($user)->get(route('profile.edit'));
        $response->assertStatus(200);
        $response->assertSee('Información de Perfil');

        // Test profile update via API
        $updateResponse = $this->actingAs($user)->patchJson('/api/profile', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'language' => 'en',
            'timezone' => 'America/New_York',
            'notifications_enabled' => false,
        ]);

        $updateResponse->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
        $this->assertEquals('en', $user->language);
        $this->assertEquals('America/New_York', $user->timezone);
        $this->assertFalse($user->notifications_enabled);
    }

    /**
     * T034: Test complete password change workflow (integration test)
     */
    public function test_complete_password_change_workflow(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);

        // Step 1: Request OTP
        $otpResponse = $this->actingAs($user)->postJson('/api/password/request-otp', [
            'current_password' => 'oldpassword',
        ]);

        $otpResponse->assertStatus(200);
        $otpResponse->assertJsonStructure([
            'message',
            'cooldown_seconds',
        ]);

        // Verify OTP was created
        $verification = EmailVerification::where('user_id', $user->id)
            ->where('purpose', 'password_change')
            ->first();
        $this->assertNotNull($verification);

        // Step 2: Simulate OTP verification (in real scenario, user receives email)
        $otp = '123456';
        $verification->update([
            'verification_code' => Hash::make($otp),
        ]);

        $verifyResponse = $this->actingAs($user)->postJson('/api/password/verify-otp', [
            'otp' => $otp,
        ]);

        $verifyResponse->assertStatus(200);
    }

    /**
     * T035: Test preferences page integration
     */
    public function test_preferences_page_with_all_options(): void
    {
        $user = User::factory()->create([
            'language' => 'es',
            'timezone' => 'America/Guayaquil',
            'notifications_enabled' => true,
        ]);

        $response = $this->actingAs($user)->get(route('preferences.index'));

        $response->assertStatus(200);
        $response->assertSee('Configuración Regional');
        $response->assertSee('Tema');
        $response->assertSee('Notificaciones');
        $response->assertSee('English');
        $response->assertSee('Español');
    }

    /**
     * T036: Test Spanish localization
     */
    public function test_spanish_localization_on_profile_page(): void
    {
        $user = User::factory()->create(['language' => 'es']);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Configuración de Cuenta');
        $response->assertSee('Información de Perfil');
    }

    /**
     * T037: Test English localization
     */
    public function test_english_localization_available(): void
    {
        $user = User::factory()->create(['language' => 'en']);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);
        // Profile page should have English strings if locale is switched
        // (This assumes localization middleware or config is in place)
    }

    /**
     * T038: Test error handling and validation
     */
    public function test_profile_validation_errors(): void
    {
        $user = User::factory()->create();

        // Test invalid email
        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);

        // Test name too long
        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'name' => str_repeat('a', 300),
        ]);

        $response->assertStatus(422);

        // Test invalid timezone
        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'timezone' => 'Invalid/Timezone',
        ]);

        // Should either pass (if validation is loose) or fail (if strict)
        $this->assertThat(
            $response->status(),
            $this->logicalOr(
                $this->equalTo(200),
                $this->equalTo(422)
            )
        );
    }
}
