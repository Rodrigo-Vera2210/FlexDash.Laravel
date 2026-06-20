<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Modules\Partner\Models\Partner;
use App\Modules\Sale\Models\Sale;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\CashBox\Models\CashBox;
use App\Modules\CashBox\Models\CashBoxTransaction;
use App\Modules\CashBox\Services\CashBoxService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CashBoxManagementTest extends TestCase
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

    protected function getAuthUser()
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

    protected function createPartner(string $type = 'cliente')
    {
        return Partner::create([
            'type'            => $type,
            'business_name'   => 'Test Partner LLC',
            'document_type'   => 'RUC',
            'document_number' => '20123456789',
            'is_active'       => true,
        ]);
    }

    public function test_can_open_cash_box_session()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/open', [
                'opening_balance' => 150.00,
                'notes'           => 'Starting morning shift',
            ]);

        $response->assertRedirect('/cashbox');
        $this->assertDatabaseHas('cash_boxes', [
            'user_id'         => $user->id,
            'status'          => 'OPEN',
            'opening_balance' => 150.00,
        ]);

        // Verifies the initial open transaction was recorded
        $box = CashBox::active()->first();
        $this->assertNotNull($box);
        $this->assertDatabaseHas('cash_box_transactions', [
            'cash_box_id' => $box->id,
            'type'        => 'ingreso',
            'amount'      => 150.00,
            'concept'     => 'Saldo inicial / Apertura de caja',
        ]);
    }

    public function test_cannot_open_multiple_cash_boxes_simultaneously()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        // Open first cashbox
        CashBox::create([
            'user_id'                  => $user->id,
            'status'                   => 'OPEN',
            'opening_balance'          => 100.00,
            'expected_closing_balance' => 100.00,
            'opened_at'                => now(),
        ]);

        // Try to open second cashbox
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/open', [
                'opening_balance' => 200.00,
            ]);

        $response->assertSessionHasErrors(['balance']);
    }

    public function test_can_record_manual_cash_movements()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $box = CashBox::create([
            'user_id'                  => $user->id,
            'status'                   => 'OPEN',
            'opening_balance'          => 100.00,
            'expected_closing_balance' => 100.00,
            'opened_at'                => now(),
        ]);

        // Test manual inflow
        $responseInflow = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/adjust', [
                'type'    => 'ingreso',
                'amount'  => 50.00,
                'concept' => 'Manual load',
            ]);
        $responseInflow->assertRedirect('/cashbox');
        $this->assertDatabaseHas('cash_box_transactions', [
            'cash_box_id' => $box->id,
            'type'        => 'ingreso',
            'amount'      => 50.00,
            'concept'     => 'Manual load',
        ]);

        // Test manual outflow
        $responseOutflow = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/adjust', [
                'type'    => 'egreso',
                'amount'  => 20.00,
                'concept' => 'Office cleaning supplies',
            ]);
        $responseOutflow->assertRedirect('/cashbox');
        $this->assertDatabaseHas('cash_box_transactions', [
            'cash_box_id' => $box->id,
            'type'        => 'egreso',
            'amount'      => 20.00,
            'concept'     => 'Office cleaning supplies',
        ]);

        // Expected balance check: 100 + 50 - 20 = 130
        $box->refresh();
        $this->assertEquals(130.00, (float) $box->expected_closing_balance);
    }

    public function test_can_close_cash_box_reconciliation()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $box = CashBox::create([
            'user_id'                  => $user->id,
            'status'                   => 'OPEN',
            'opening_balance'          => 100.00,
            'expected_closing_balance' => 120.00, // Say there was an inflow of S/ 20
            'opened_at'                => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/close', [
                'actual_closing_balance' => 115.00, // Counted S/ 5 short
                'notes'                  => 'S/ 5 difference in drawer',
            ]);

        $response->assertRedirect('/cashbox');
        $box->refresh();
        $this->assertEquals('CLOSED', $box->status);
        $this->assertEquals(115.00, (float) $box->actual_closing_balance);
        $this->assertEquals(-5.00, (float) $box->difference);
        $this->assertNotNull($box->closed_at);
    }

    public function test_standard_payment_registers_cash_box_transaction()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);
        $partner = $this->createPartner('cliente');

        $paymentMethod = PaymentMethod::create(['name' => 'Efectivo', 'is_active' => true]);

        // Create Sale
        $sale = Sale::create([
            'partner_id'      => $partner->id,
            'user_id'         => $user->id,
            'number'          => 'F001-0001',
            'issue_date'      => now()->toDateString(),
            'status'          => 'APROBADO',
            'total'           => 100.00,
            'paid_amount'     => 0.00,
            'pending_balance' => 100.00,
        ]);

        // Attempting payment without open cashbox should fail
        $responseFail = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post("/sales/{$sale->id}/payments", [
                'payment_method_id' => $paymentMethod->id,
                'amount'            => 40.00,
                'payment_date'      => now()->toDateString(),
            ]);
        $responseFail->assertSessionHas('error');
        $this->assertDatabaseMissing('payments', ['payable_id' => $sale->id]);

        // Open Cash Box
        $box = CashBox::create([
            'user_id'                  => $user->id,
            'status'                   => 'OPEN',
            'opening_balance'          => 100.00,
            'expected_closing_balance' => 100.00,
            'opened_at'                => now(),
        ]);

        // Pay Sale
        $responseSuccess = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post("/sales/{$sale->id}/payments", [
                'payment_method_id' => $paymentMethod->id,
                'amount'            => 40.00,
                'payment_date'      => now()->toDateString(),
            ]);
        $responseSuccess->assertRedirect();
        
        // Assert Payment created
        $payment = Payment::where('payable_id', $sale->id)->first();
        $this->assertNotNull($payment);

        // Assert Cash Box Transaction logged
        $this->assertDatabaseHas('cash_box_transactions', [
            'cash_box_id' => $box->id,
            'payment_id'  => $payment->id,
            'type'        => 'ingreso',
            'amount'      => 40.00,
            'concept'     => "Cobro de Venta #{$sale->number}",
        ]);

        $box->refresh();
        $this->assertEquals(140.00, (float) $box->expected_closing_balance);
    }

    public function test_batch_payment_distributes_outstanding_balances_chronologically()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);
        $partner = $this->createPartner('cliente');
        $paymentMethod = PaymentMethod::create(['name' => 'Efectivo', 'is_active' => true]);

        // Create 3 outstanding sales
        $sale1 = Sale::create([
            'partner_id'      => $partner->id,
            'user_id'         => $user->id,
            'number'          => 'F001-0001',
            'issue_date'      => '2026-06-01',
            'status'          => 'APROBADO',
            'total'           => 50.00,
            'paid_amount'     => 0.00,
            'pending_balance' => 50.00,
        ]);
        $sale2 = Sale::create([
            'partner_id'      => $partner->id,
            'user_id'         => $user->id,
            'number'          => 'F001-0002',
            'issue_date'      => '2026-06-02',
            'status'          => 'APROBADO',
            'total'           => 80.00,
            'paid_amount'     => 0.00,
            'pending_balance' => 80.00,
        ]);
        $sale3 = Sale::create([
            'partner_id'      => $partner->id,
            'user_id'         => $user->id,
            'number'          => 'F001-0003',
            'issue_date'      => '2026-06-03',
            'status'          => 'APROBADO',
            'total'           => 40.00,
            'paid_amount'     => 0.00,
            'pending_balance' => 40.00,
        ]);

        // Open Cash Box
        $box = CashBox::create([
            'user_id'                  => $user->id,
            'status'                   => 'OPEN',
            'opening_balance'          => 100.00,
            'expected_closing_balance' => 100.00,
            'opened_at'                => now(),
        ]);

        // Execute batch payment of S/ 100.00 selecting sale1 and sale2
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/cashbox/batch-payment', [
                'partner_type'      => 'cliente',
                'partner_id'        => $partner->id,
                'document_ids'      => [$sale1->id, $sale2->id],
                'amount'            => 100.00,
                'payment_method_id' => $paymentMethod->id,
                'payment_date'      => now()->toDateString(),
            ]);

        $response->assertRedirect('/cashbox');

        // Check balances distribution:
        // sale1 is paid off (S/ 50.00 paid, pending balance 0, status PAGADO)
        $sale1->refresh();
        $this->assertEquals(50.00, (float) $sale1->paid_amount);
        $this->assertEquals(0.00, (float) $sale1->pending_balance);
        $this->assertEquals(Sale::STATUS_PAID, $sale1->status);

        // sale2 is partially paid (S/ 50.00 paid out of remaining amount, pending balance 30.00, status APROBADO)
        $sale2->refresh();
        $this->assertEquals(50.00, (float) $sale2->paid_amount);
        $this->assertEquals(30.00, (float) $sale2->pending_balance);
        $this->assertEquals('APROBADO', $sale2->status);

        // sale3 is untouched
        $sale3->refresh();
        $this->assertEquals(0.00, (float) $sale3->paid_amount);
        $this->assertEquals(40.00, (float) $sale3->pending_balance);

        // Verify cash box transactions exist for both payments
        $this->assertDatabaseHas('cash_box_transactions', [
            'cash_box_id' => $box->id,
            'type'        => 'ingreso',
            'amount'      => 50.00,
            'concept'     => "Cobro de Venta #{$sale1->number}",
        ]);

        $this->assertDatabaseHas('cash_box_transactions', [
            'cash_box_id' => $box->id,
            'type'        => 'ingreso',
            'amount'      => 50.00,
            'concept'     => "Cobro de Venta #{$sale2->number}",
        ]);

        $box->refresh();
        $this->assertEquals(200.00, (float) $box->expected_closing_balance);
    }
}
