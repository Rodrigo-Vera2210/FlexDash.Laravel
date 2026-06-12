<?php

namespace App\Services;

use App\Modules\Product\Models\Product;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseDetail;
use App\Exceptions\ImmutableDocumentException;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private InventoryService $inventoryService) {}

    public function create(array $header, array $items): Purchase
    {
        return DB::transaction(function () use ($header, $items) {
            $purchase = Purchase::create(array_merge($header, [
                'status'          => Purchase::STATUS_DRAFT,
                'subtotal'        => 0,
                'tax_amount'      => 0,
                'total'           => 0,
                'paid_amount'     => 0,
                'pending_balance' => 0,
            ]));

            $this->syncDetails($purchase, $items);

            return $purchase->fresh('details');
        });
    }

    public function update(Purchase $purchase, array $header, array $items): Purchase
    {
        $purchase->assertEditable();

        return DB::transaction(function () use ($purchase, $header, $items) {
            $purchase->update($header);
            $purchase->details()->delete();
            $this->syncDetails($purchase, $items);
            return $purchase->fresh('details');
        });
    }

    /**
     * Aprueba la compra: ingresa stock de todos los productos.
     */
    public function approve(Purchase $purchase): Purchase
    {
        $purchase->assertEditable();

        return DB::transaction(function () use ($purchase) {
            foreach ($purchase->details as $detail) {
                $this->inventoryService->entry(
                    $detail->product,
                    $detail->quantity,
                    $detail->unit_cost,
                    Purchase::class,
                    $purchase->id,
                    "Compra #{$purchase->number}"
                );
            }

            $purchase->update([
                'status'      => Purchase::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

            return $purchase->fresh();
        });
    }

    public function cancel(Purchase $purchase, string $reason = ''): Purchase
    {
        if (in_array($purchase->status, [Purchase::STATUS_CANCELLED, Purchase::STATUS_PAID])) {
            throw new ImmutableDocumentException('Compra', $purchase->number, $purchase->status);
        }

        return DB::transaction(function () use ($purchase, $reason) {
            if ($purchase->status === Purchase::STATUS_APPROVED) {
                foreach ($purchase->details as $detail) {
                    $this->inventoryService->exit(
                        $detail->product,
                        $detail->quantity,
                        Purchase::class,
                        $purchase->id,
                        "Anulación compra #{$purchase->number}"
                    );
                }
            }

            $purchase->update([
                'status'       => Purchase::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'notes'        => $purchase->notes . "\nAnulada: {$reason}",
            ]);

            return $purchase->fresh();
        });
    }

    private function syncDetails(Purchase $purchase, array $items): void
    {
        $subtotal  = 0;
        $taxRate   = $purchase->tax ? ($purchase->tax->rate / 100) : 0;

        foreach ($items as $item) {
            $qty      = (float) $item['quantity'];
            $cost     = (float) $item['unit_cost'];
            $discount = (float) ($item['discount'] ?? 0);
            $lineNet  = round(($cost * $qty) - $discount, 2);

            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
                'product_id'  => $item['product_id'],
                'quantity'    => $qty,
                'unit_cost'   => $cost,
                'discount'    => $discount,
                'subtotal'    => $lineNet,
            ]);

            $subtotal += $lineNet;
        }

        $taxAmount = round($subtotal * $taxRate, 2);
        $total     = $subtotal + $taxAmount - ($purchase->discount ?? 0);

        $purchase->update([
            'subtotal'        => $subtotal,
            'tax_amount'      => $taxAmount,
            'total'           => $total,
            'pending_balance' => $total - $purchase->paid_amount,
        ]);
    }

    public static function nextNumber(string $series = 'C001'): string
    {
        $last = Purchase::where('series', $series)->max('number');
        if (!$last) return $series . '-00000001';
        $seq  = (int) substr($last, -8);
        return $series . '-' . str_pad($seq + 1, 8, '0', STR_PAD_LEFT);
    }
}
