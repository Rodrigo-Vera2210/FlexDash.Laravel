<?php

namespace App\Modules\CashBox\Tests\Feature;

use App\Models\User;
use App\Modules\CashBox\Models\CashBox;
use App\Modules\CashBox\Models\CashBoxTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashBoxExcelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $jwtToken;
    private CashBox $cashBox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->jwtToken = $this->generateJwt($this->user);

        // Create a test cash box session
        $this->cashBox = CashBox::create([
            'user_id' => $this->user->id,
            'status' => 'OPEN',
            'opening_balance' => 100.00,
            'expected_closing_balance' => 100.00,
            'actual_closing_balance' => 0.00,
            'difference' => 0.00,
            'opened_at' => now(),
            'notes' => 'Apertura de prueba',
        ]);

        CashBoxTransaction::create([
            'cash_box_id' => $this->cashBox->id,
            'user_id' => $this->user->id,
            'type' => 'ingreso',
            'amount' => 50.00,
            'concept' => 'Venta rápida en efectivo',
        ]);
    }

    private function generateJwt(User $user): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $secret = config('app.key') ?: env('APP_KEY', 'secret');
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        $payload = json_encode([
            'user_id' => $user->id,
            'role'    => $user->role ?? 'user',
            'iat'     => time(),
            'exp'     => time() + 86400,
        ]);

        $base64url = fn($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        $segments   = [$base64url($header), $base64url($payload)];
        $signature  = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = $base64url($signature);

        return implode('.', $segments);
    }

    private function authGet(string $uri)
    {
        return $this->withCookie('token', $this->jwtToken)->get($uri);
    }

    public function test_authenticated_user_can_export_cashbox_excel(): void
    {
        $response = $this->authGet("/cashbox/{$this->cashBox->id}/export");

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('spreadsheet', $response->headers->get('Content-Type'));
    }

    public function test_invalid_cashbox_returns_404(): void
    {
        $response = $this->authGet("/cashbox/99999/export");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_export_cashbox_excel(): void
    {
        $response = $this->get("/cashbox/{$this->cashBox->id}/export");

        $response->assertRedirect();
    }
}
