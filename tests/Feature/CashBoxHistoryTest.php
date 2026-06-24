<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\CashBox\Models\CashBox;
use App\Modules\CashBox\Models\CashBoxTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CashBoxHistoryTest extends TestCase
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

    public function test_history_shows_only_closed_sessions()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        // Create an open cashbox session
        $openBox = CashBox::create([
            'user_id' => $user->id,
            'status' => 'OPEN',
            'opening_balance' => 100.00,
            'expected_closing_balance' => 100.00,
            'opened_at' => now()->subDays(2),
        ]);

        // Create a closed cashbox session
        $closedBox = CashBox::create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'opening_balance' => 200.00,
            'expected_closing_balance' => 200.00,
            'actual_closing_balance' => 200.00,
            'difference' => 0.00,
            'opened_at' => now()->subDay(),
            'closed_at' => now()->subDay()->addHours(8),
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('/cashbox/history');

        $response->assertStatus(200);
        $response->assertSee('Sesiones de Caja Cerradas');
        
        // Assert we see the closed one and not the open one in the history list
        $response->assertSee('S/ 200.00');
        $response->assertDontSee('S/ 100.00');
    }

    public function test_history_filters_by_date_range()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        // Session 1: 5 days ago
        CashBox::create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'opening_balance' => 150.00,
            'expected_closing_balance' => 150.00,
            'actual_closing_balance' => 150.00,
            'difference' => 0.00,
            'opened_at' => now()->subDays(5),
            'closed_at' => now()->subDays(5)->addHours(8),
        ]);

        // Session 2: 2 days ago
        CashBox::create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'opening_balance' => 250.00,
            'expected_closing_balance' => 250.00,
            'actual_closing_balance' => 250.00,
            'difference' => 0.00,
            'opened_at' => now()->subDays(2),
            'closed_at' => now()->subDays(2)->addHours(8),
        ]);

        // Filter for date range that only covers Session 2
        $dateFrom = now()->subDays(3)->format('Y-m-d');
        $dateTo = now()->subDay()->format('Y-m-d');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get("/cashbox/history?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200);
        $response->assertSee('S/ 250.00');
        $response->assertDontSee('S/ 150.00');
    }

    public function test_history_show_displays_session_detail()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $closedBox = CashBox::create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'opening_balance' => 300.00,
            'expected_closing_balance' => 350.00,
            'actual_closing_balance' => 345.00,
            'difference' => -5.00,
            'opened_at' => now()->subDay(),
            'closed_at' => now()->subDay()->addHours(8),
            'notes' => 'Some test closing notes',
        ]);

        // Add a transaction
        $tx = CashBoxTransaction::create([
            'cash_box_id' => $closedBox->id,
            'user_id' => $user->id,
            'type' => 'ingreso',
            'amount' => 50.00,
            'concept' => 'Sales inflow',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get("/cashbox/history/{$closedBox->id}");

        $response->assertStatus(200);
        $response->assertSee('Detalle de Sesión #' . $closedBox->id);
        $response->assertSee('S/ 300.00');
        $response->assertSee('S/ 350.00');
        $response->assertSee('S/ 345.00');
        $response->assertSee('-S/ 5.00');
        $response->assertSee('Sales inflow');
        $response->assertSee('Some test closing notes');
    }

    public function test_transaction_shows_user_attribution()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        // Create secondary user
        $otherUser = User::create([
            'name'     => 'Other Operator',
            'email'    => 'operator@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        $closedBox = CashBox::create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'opening_balance' => 100.00,
            'expected_closing_balance' => 120.00,
            'actual_closing_balance' => 120.00,
            'difference' => 0.00,
            'opened_at' => now()->subDay(),
            'closed_at' => now()->subDay()->addHours(8),
        ]);

        // Create transaction by $otherUser
        CashBoxTransaction::create([
            'cash_box_id' => $closedBox->id,
            'user_id' => $otherUser->id,
            'type' => 'ingreso',
            'amount' => 20.00,
            'concept' => 'Other operator deposit',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get("/cashbox/history/{$closedBox->id}");

        $response->assertStatus(200);
        $response->assertSee('Other operator deposit');
        $response->assertSee('Other Operator');
    }
}
