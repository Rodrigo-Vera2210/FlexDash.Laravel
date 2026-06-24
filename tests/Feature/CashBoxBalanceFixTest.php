<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\CashBox\Models\CashBox;
use App\Modules\CashBox\Models\CashBoxTransaction;
use App\Modules\CashBox\Services\CashBoxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CashBoxBalanceFixTest extends TestCase
{
    use RefreshDatabase;

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

    protected function getAuthUser(): User
    {
        $user = User::create([
            'name'     => 'Cashier User',
            'email'    => 'cashier@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    public function test_opening_balance_not_doubled()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/open', [
                'opening_balance' => 100.00,
            ]);

        $response->assertRedirect('/cashbox');

        $box = CashBox::first();
        $this->assertNotNull($box);
        $this->assertEquals(100.00, (float) $box->opening_balance);
        // The critical assertion: expected_closing_balance must be 100, NOT 200
        $this->assertEquals(100.00, (float) $box->expected_closing_balance);
    }

    public function test_balance_after_inflow_and_outflow()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        // Open box with $100
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/open', ['opening_balance' => 100.00]);

        $box = CashBox::first();
        $this->assertNotNull($box);
        $this->assertEquals(100.00, (float) $box->expected_closing_balance);

        // Add inflow of $2
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/adjust', [
                'type'    => 'ingreso',
                'amount'  => 2.00,
                'concept' => 'Small deposit',
            ]);

        $box->refresh();
        $this->assertEquals(102.00, (float) $box->expected_closing_balance);

        // Add outflow of $2
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/adjust', [
                'type'    => 'egreso',
                'amount'  => 2.00,
                'concept' => 'Small expense',
            ]);

        $box->refresh();
        // After +2 and -2, expected should be back to 100
        $this->assertEquals(100.00, (float) $box->expected_closing_balance);
    }

    public function test_close_with_correct_expected_balance()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        // Open box with $100
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/open', ['opening_balance' => 100.00]);

        $box = CashBox::first();

        // Inflow $50
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/adjust', [
                'type' => 'ingreso', 'amount' => 50.00, 'concept' => 'Deposit',
            ]);

        // Outflow $20
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/adjust', [
                'type' => 'egreso', 'amount' => 20.00, 'concept' => 'Expense',
            ]);

        // Expected = 100 + 50 - 20 = 130
        $box->refresh();
        $this->assertEquals(130.00, (float) $box->expected_closing_balance);

        // Close with exactly $130 → difference should be $0
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/close', [
                'actual_closing_balance' => 130.00,
                'notes' => 'Perfect close',
            ]);

        $box->refresh();
        $this->assertEquals('CLOSED', $box->status);
        $this->assertEquals(130.00, (float) $box->actual_closing_balance);
        $this->assertEquals(0.00, (float) $box->difference);
    }

    public function test_close_with_discrepancy()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        // Open box with $100
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/open', ['opening_balance' => 100.00]);

        $box = CashBox::first();
        $this->assertEquals(100.00, (float) $box->expected_closing_balance);

        // Close with $95 → difference should be -5
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/close', [
                'actual_closing_balance' => 95.00,
            ]);

        $box->refresh();
        $this->assertEquals('CLOSED', $box->status);
        $this->assertEquals(95.00, (float) $box->actual_closing_balance);
        $this->assertEquals(-5.00, (float) $box->difference);
    }
}
