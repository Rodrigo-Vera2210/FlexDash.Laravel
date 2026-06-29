<?php

namespace App\Services;

use App\Exceptions\ImmutableDocumentException;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Product\Models\Product;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleDetail;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(private InventoryService $inventoryService) {}

    /**
     * Crea una venta en BORRADOR con sus detalles.
     * $items = [['product_id' => X, 'quantity' => Y, 'unit_price' => Z, 'discount' => 0], ...]
     */
    public function create(array $header, array $items): Sale
    {
        return DB::transaction(function () use ($header, $items) {
            $sale = Sale::create(array_merge($header, [
                'status'          => Sale::STATUS_DRAFT,
                'subtotal'        => 0,
                'tax_amount'      => 0,
                'total'           => 0,
                'paid_amount'     => 0,
                'pending_balance' => 0,
            ]));

            $this->syncDetails($sale, $items);

            return $sale->fresh('details');
        });
    }

    /**
     * Actualiza una venta en BORRADOR.
     */
    public function update(Sale $sale, array $header, array $items): Sale
    {
        $sale->assertEditable();

        return DB::transaction(function () use ($sale, $header, $items) {
            $sale->update($header);
            $sale->details()->delete();
            $this->syncDetails($sale, $items);
            return $sale->fresh('details');
        });
    }

    /**
     * Aprueba la venta: descuenta stock, congela el documento.
     */
    public function approve(Sale $sale): Sale
    {
        $sale->assertEditable();

        return DB::transaction(function () use ($sale) {
            foreach ($sale->details as $detail) {
                if ($detail->isProduct()) {
                    $this->inventoryService->exit(
                        $detail->product,
                        $detail->quantity,
                        Sale::class,
                        $sale->id,
                        "Venta #{$sale->number}",
                        $sale->branch_id
                    );
                }
            }

            $sale->update([
                'status'      => Sale::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

            return $sale->fresh();
        });
    }

    /**
     * Anula la venta: devuelve stock si estaba APROBADO.
     */
    public function cancel(Sale $sale, string $reason = ''): Sale
    {
        if ($sale->status === Sale::STATUS_CANCELLED) {
            throw new ImmutableDocumentException('Venta', $sale->number, $sale->status);
        }
        if ($sale->status === Sale::STATUS_PAID) {
            throw new ImmutableDocumentException('Venta', $sale->number, 'PAGADO (no anulable)');
        }

        return DB::transaction(function () use ($sale, $reason) {
            if ($sale->status === Sale::STATUS_APPROVED) {
                foreach ($sale->details as $detail) {
                    if ($detail->isProduct()) {
                        $this->inventoryService->return(
                            $detail->product,
                            $detail->quantity,
                            Sale::class,
                            $sale->id,
                            "Anulación venta #{$sale->number}",
                            $sale->branch_id
                        );
                    }
                }
            }

            $sale->update([
                'status'       => Sale::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'notes'        => $sale->notes . "\nAnulada: {$reason}",
            ]);

            return $sale->fresh();
        });
    }

    // ── Helpers privados ──────────────────────────────────────────────

    private function syncDetails(Sale $sale, array $items): void
    {
        $subtotal   = 0;
        $taxAmount  = 0;
        $taxRate    = $sale->tax ? ($sale->tax->rate / 100) : 0;

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $serviceId = $item['service_id'] ?? null;

            $qty      = (float) $item['quantity'];
            $price    = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $lineNet  = round(($price * $qty) - $discount, 2);

            if ($productId) {
                $product  = Product::findOrFail($productId);
                SaleDetail::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'service_id' => null,
                    'quantity'   => $qty,
                    'unit_price' => $price,
                    'cost_price' => $product->cost,
                    'discount'   => $discount,
                    'subtotal'   => $lineNet,
                ]);
            } elseif ($serviceId) {
                $service  = \App\Modules\Service\Models\Service::findOrFail($serviceId);
                SaleDetail::create([
                    'sale_id'    => $sale->id,
                    'product_id' => null,
                    'service_id' => $service->id,
                    'quantity'   => $qty,
                    'unit_price' => $price,
                    'cost_price' => $service->cost ?? 0,
                    'discount'   => $discount,
                    'subtotal'   => $lineNet,
                ]);
            }

            $subtotal += $lineNet;
        }

        $taxAmount = round($subtotal * $taxRate, 2);
        $total     = $subtotal + $taxAmount - ($sale->discount ?? 0);

        $sale->update([
            'subtotal'        => $subtotal,
            'tax_amount'      => $taxAmount,
            'total'           => $total,
            'pending_balance' => $total - $sale->paid_amount,
        ]);
    }

    /**
     * Genera el próximo número de serie para ventas.
     */
    public static function nextNumber(string $series = 'F001'): string
    {
        $last = Sale::withoutGlobalScope('branch_scope')->where('series', $series)->max('number');
        if (!$last) return $series . '-00000001';
        $seq  = (int) substr($last, -8);
        return $series . '-' . str_pad($seq + 1, 8, '0', STR_PAD_LEFT);
    }
}
