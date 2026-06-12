<?php

namespace App\Services;

use App\Exceptions\PaymentExceedsBalanceException;
use App\Models\Payment;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Sale\Models\Sale;
use App\Modules\CashBox\Models\CashBox;
use App\Modules\CashBox\Services\CashBoxService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private CashBoxService $cashBoxService) {}

    /**
     * Registra un pago sobre un documento (Sale o Purchase).
     * Valida que el monto no supere el saldo pendiente.
     */
    public function register(Model $payable, array $data): Payment
    {
        return DB::transaction(function () use ($payable, $data) {
            // Verificar si hay una caja abierta antes de procesar el pago
            $activeBox = CashBox::active()->lockForUpdate()->first();
            if (!$activeBox) {
                throw new \Exception('No se puede registrar el pago porque no hay ninguna sesión de caja chica abierta.');
            }

            // Bloquear fila para evitar pagos dobles concurrentes
            $payable = get_class($payable)::where('id', $payable->id)->lockForUpdate()->first();

            $amount  = (float) $data['amount'];
            $balance = (float) $payable->pending_balance;

            Payment::validateAmount($amount, $balance);

            $payment = Payment::create([
                'payment_method_id' => $data['payment_method_id'],
                'user_id'           => auth()->id() ?? $activeBox->user_id,
                'payable_type'      => get_class($payable),
                'payable_id'        => $payable->id,
                'amount'            => $amount,
                'payment_date'      => $data['payment_date'],
                'reference'         => $data['reference'] ?? null,
                'notes'             => $data['notes'] ?? null,
            ]);

            $newPaid    = $payable->paid_amount + $amount;
            $newBalance = $payable->total - $newPaid;

            $status = $newBalance <= 0
                ? ($payable instanceof Sale ? Sale::STATUS_PAID : Purchase::STATUS_PAID)
                : $payable->status;

            $payable->update([
                'paid_amount'     => $newPaid,
                'pending_balance' => max(0, $newBalance),
                'status'          => $status,
            ]);

            // Registrar automáticamente el movimiento de caja chica conectado a este pago
            $type = $payable instanceof Sale ? 'ingreso' : 'egreso';
            $concept = $payable instanceof Sale 
                ? "Cobro de Venta #{$payable->number}" 
                : "Pago de Compra #{$payable->number}";

            $this->cashBoxService->recordTransaction($activeBox, $type, $amount, $concept, $payment->id);

            return $payment;
        });
    }
}
