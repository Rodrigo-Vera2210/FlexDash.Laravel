<?php

namespace App\Modules\CashBox\Services;

use App\Modules\CashBox\Models\CashBox;
use App\Modules\CashBox\Models\CashBoxTransaction;
use App\Services\PaymentService;
use App\Modules\Sale\Models\Sale;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Partner\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashBoxService
{

    /**
     * Abre una nueva sesión de caja chica.
     * Valida que no haya otra caja abierta actualmente.
     */
    public function openBox(float $openingBalance, ?string $notes = null): CashBox
    {
        return DB::transaction(function () use ($openingBalance, $notes) {
            $activeBox = CashBox::active()->lockForUpdate()->first();

            if ($activeBox) {
                throw ValidationException::withMessages([
                    'balance' => 'Ya existe una sesión de caja chica abierta.'
                ]);
            }

            $box = CashBox::create([
                'user_id'                  => auth()->id() ?? 1, // fallback to user ID 1 for testing if needed
                'status'                   => 'OPEN',
                'opening_balance'          => $openingBalance,
                'expected_closing_balance' => 0,
                'opened_at'                => now(),
                'notes'                    => $notes,
            ]);

            // Registrar movimiento inicial de apertura
            $this->recordTransaction($box, 'ingreso', $openingBalance, 'Saldo inicial / Apertura de caja');

            return $box;
        });
    }

    /**
     * Cierra la sesión de caja chica actual.
     */
    public function closeBox(CashBox $box, float $actualCash, ?string $notes = null): CashBox
    {
        if (!$box->isOpen()) {
            throw new \InvalidArgumentException('Esta caja ya se encuentra cerrada.');
        }

        return DB::transaction(function () use ($box, $actualCash, $notes) {
            // Recargar para bloquear fila
            $box = CashBox::where('id', $box->id)->lockForUpdate()->first();

            $difference = $actualCash - $box->expected_closing_balance;

            $box->update([
                'status'                 => 'CLOSED',
                'actual_closing_balance' => $actualCash,
                'difference'             => $difference,
                'closed_at'              => now(),
                'notes'                  => $notes ? ($box->notes ? $box->notes . "\n" . $notes : $notes) : $box->notes,
            ]);

            return $box;
        });
    }

    /**
     * Registra un ingreso o egreso manual o automático en la caja.
     */
    public function recordTransaction(CashBox $box, string $type, float $amount, string $concept, ?int $paymentId = null): CashBoxTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor a cero.');
        }

        if (!in_array($type, ['ingreso', 'egreso'])) {
            throw new \InvalidArgumentException('Tipo de movimiento no válido.');
        }

        return DB::transaction(function () use ($box, $type, $amount, $concept, $paymentId) {
            // Bloquear la caja para actualizar saldo esperado
            $box = CashBox::where('id', $box->id)->lockForUpdate()->first();

            $transaction = CashBoxTransaction::create([
                'cash_box_id' => $box->id,
                'user_id'     => auth()->id() ?? $box->user_id, // fallback to box owner if running outside auth (like seeding/tests)
                'payment_id'  => $paymentId,
                'type'        => $type,
                'amount'      => $amount,
                'concept'     => $concept,
            ]);

            $newExpected = $type === 'ingreso' 
                ? $box->expected_closing_balance + $amount 
                : $box->expected_closing_balance - $amount;

            $box->update([
                'expected_closing_balance' => $newExpected,
            ]);

            return $transaction;
        });
    }

    /**
     * Procesa un cobro/pago masivo a múltiples documentos de un mismo cliente o proveedor.
     */
    public function processBatchPayment(string $partnerType, int $partnerId, array $documentIds, float $totalPaid, int $paymentMethodId, string $date, ?string $ref = null, ?string $notes = null): void
    {
        $activeBox = CashBox::active()->first();
        if (!$activeBox) {
            throw new \Exception('No hay una sesión de caja chica abierta para procesar pagos.');
        }

        if ($totalPaid <= 0) {
            throw new \InvalidArgumentException('El monto total del pago debe ser mayor a cero.');
        }

        DB::transaction(function () use ($partnerType, $partnerId, $documentIds, $totalPaid, $paymentMethodId, $date, $ref, $notes, $activeBox) {
            // Verificar el partner
            $partner = Partner::findOrFail($partnerId);

            // Cargar documentos pendientes
            if ($partnerType === 'cliente') {
                $documents = Sale::whereIn('id', $documentIds)
                    ->where('partner_id', $partnerId)
                    ->where('status', 'APROBADO')
                    ->where('pending_balance', '>', 0)
                    ->orderBy('issue_date')
                    ->lockForUpdate()
                    ->get();
            } else {
                $documents = Purchase::whereIn('id', $documentIds)
                    ->where('partner_id', $partnerId)
                    ->where('status', 'APROBADO')
                    ->where('pending_balance', '>', 0)
                    ->orderBy('issue_date')
                    ->lockForUpdate()
                    ->get();
            }

            if ($documents->isEmpty()) {
                throw new \Exception('No se encontraron documentos pendientes válidos para el cliente/proveedor especificado.');
            }

            $remainingAmount = $totalPaid;

            foreach ($documents as $doc) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $pending = (float) $doc->pending_balance;
                $toPay = min($remainingAmount, $pending);

                // Registrar el pago unitario
                $payment = app(PaymentService::class)->register($doc, [
                    'payment_method_id' => $paymentMethodId,
                    'amount'            => $toPay,
                    'payment_date'      => $date,
                    'reference'         => $ref,
                    'notes'             => "Pago masivo - " . ($notes ?? ''),
                ]);

                $remainingAmount -= $toPay;
            }
        });
    }
}
