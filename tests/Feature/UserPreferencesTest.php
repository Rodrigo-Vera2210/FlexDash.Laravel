<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T029: Test preferences page is accessible
     */
    public function test_preferences_page_accessible_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('preferences.index'));

        $response->assertStatus(200);
        $response->assertSee('Mis Preferencias');
    }

    /**
     * T030: Test language preference update
     */
    public function test_language_preference_can_be_updated(): void
    {
        $user = User::factory()->create(['language' => 'es']);

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'language' => 'en',
        ]);

        $response->assertStatus(200);
        
        $user->refresh();
        $this->assertEquals('en', $user->language);
    }

    /**
     * T031: Test timezone preference update
     */
    public function test_timezone_preference_can_be_updated(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Guayaquil']);

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'timezone' => 'America/New_York',
        ]);

        $response->assertStatus(200);
        
        $user->refresh();
        $this->assertEquals('America/New_York', $user->timezone);
    }

    /**
     * T032: Test notification preferences update
     */
    public function test_notification_preference_can_be_updated(): void
    {
        $user = User::factory()->create(['notifications_enabled' => true]);

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'notifications_enabled' => false,
        ]);

        $response->assertStatus(200);
        
        $user->refresh();
        $this->assertFalse($user->notifications_enabled);
    }
}
