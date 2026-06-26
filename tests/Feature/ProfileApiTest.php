<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_profile_returns_current_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_get_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401);
    }

    public function test_update_profile_with_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'name' => 'Updated Name',
            'email' => 'newemail@example.com',
            'language' => 'en',
            'timezone' => 'America/New_York',
            'notifications_enabled' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Profile updated successfully',
        ]);

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('newemail@example.com', $user->email);
        $this->assertEquals('en', $user->language);
        $this->assertEquals('America/New_York', $user->timezone);
        $this->assertFalse($user->notifications_enabled);
    }

    public function test_update_profile_with_invalid_email(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'name' => 'Updated Name',
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_delete_profile_requires_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/api/profile', [
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_delete_profile_with_correct_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->actingAs($user)->deleteJson('/api/profile', [
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Account deleted successfully',
        ]);

        $this->assertNull(User::find($user->id));
    }
}
