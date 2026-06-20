<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginDenyIfUnverifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_cannot_login()
    {
        $user = User::create([
            'email' => 'jane@example.com',
            'password' => 'password',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/login', [
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
                 ->assertJson(['error' => 'email_not_verified']);
    }

    public function test_verified_user_receives_token()
    {
        $user = User::create([
            'email' => 'john@example.com',
            'password' => 'password',
        ]);
        $user->email_verified_at = now();
        $user->save();

        $response = $this->postJson('/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);
    }
}
