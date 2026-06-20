<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to generate a valid signed JWT for a given user.
     */
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

    public function test_guest_without_token_redirects_to_login()
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_unauthorized_json_request_receives_401()
    {
        $response = $this->getJson('/dashboard');

        $response->assertStatus(401);
    }

    public function test_request_with_valid_jwt_cookie_allows_access()
    {
        $user = User::create([
            'name'              => 'Authenticated User',
            'email'             => 'auth@example.com',
            'password'          => Hash::make('password'),
            'status'            => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        $token = $this->generateJwtForUser($user);

        // Access dashboard with the token cookie
        $response = $this->withCookie('token', $token)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_request_with_expired_jwt_is_redirected()
    {
        $user = User::create([
            'name'              => 'Authenticated User',
            'email'             => 'auth@example.com',
            'password'          => Hash::make('password'),
            'status'            => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        // Token expired 10 seconds ago
        $token = $this->generateJwtForUser($user, -10);

        $response = $this->withCookie('token', $token)->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_logout_clears_the_cookie_and_redirects()
    {
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $response->assertCookieExpired('token');
    }
}
