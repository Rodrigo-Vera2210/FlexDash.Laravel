<?php

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Inventory\Models\StockTransferDetail;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Branch\Models\Branch;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    /**
     * Perform an inter-branch stock transfer.
     */
    public function transferStock(User $user, array $data): StockTransfer
    {
        // 1. Plan check
        if ($user->company->max_branches <= 1) {
            throw new \Exception("Tu plan actual no admite multibodegas/traslados entre bodegas. Por favor, sube de nivel tu plan.");
        }

        return DB::transaction(function () use ($user, $data) {
            $originBranchId = $data['origin_branch_id'];
            $destinationBranchId = $data['destination_branch_id'];

            if ($originBranchId === $destinationBranchId) {
                throw new \Exception("El local de destino debe ser diferente al de origen.");
            }

            // Create transfer header
            $transfer = StockTransfer::create([
                'company_id'            => $user->company_id,
                'origin_branch_id'      => $originBranchId,
                'destination_branch_id' => $destinationBranchId,
                'user_id'               => $user->id,
            ]);

            foreach ($data['items'] as $item) {
                $productId = $item['product_id'];
                $qty = (int)$item['quantity'];

                if ($qty <= 0) {
                    throw new \Exception("La cantidad debe ser mayor a cero.");
                }

                $product = Product::where('id', $productId)
                    ->where('company_id', $user->company_id)
                    ->firstOrFail();

                // Get current stock in origin branch
                $originPivot = DB::table('branch_product')
                    ->where('branch_id', $originBranchId)
                    ->where('product_id', $productId)
                    ->first();

                $originStock = $originPivot ? (int)$originPivot->stock : 0;

                if ($originStock < $qty) {
                    throw new \Exception("Stock insuficiente en el local de origen para el producto: {$product->name}. Disponible: {$originStock}, Solicitado: {$qty}");
                }

                // Get current stock in destination branch
                $destPivot = DB::table('branch_product')
                    ->where('branch_id', $destinationBranchId)
                    ->where('product_id', $productId)
                    ->first();

                $destStock = $destPivot ? (int)$destPivot->stock : 0;

                // Decrement origin stock
                DB::table('branch_product')
                    ->where('branch_id', $originBranchId)
                    ->where('product_id', $productId)
                    ->update(['stock' => $originStock - $qty]);

                // Increment destination stock
                if ($destPivot) {
                    DB::table('branch_product')
                        ->where('branch_id', $destinationBranchId)
                        ->where('product_id', $productId)
                        ->update(['stock' => $destStock + $qty]);
                } else {
                    DB::table('branch_product')->insert([
                        'branch_id'  => $destinationBranchId,
                        'product_id' => $productId,
                        'stock'      => $qty,
                    ]);
                }

                // Create transfer detail log
                StockTransferDetail::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id'        => $productId,
                    'quantity'          => $qty,
                ]);

                // Record Kardex movements
                // Origin Egreso
                InventoryMovement::create([
                    'company_id'     => $user->company_id,
                    'product_id'     => $productId,
                    'user_id'        => $user->id,
                    'branch_id'      => $originBranchId,
                    'type'           => 'egreso_traslado',
                    'quantity'       => $qty,
                    'stock_before'   => $originStock,
                    'stock_after'    => $originStock - $qty,
                    'unit_cost'      => $product->cost ?? 0,
                    'reference_type' => StockTransfer::class,
                    'reference_id'   => $transfer->id,
                    'notes'          => 'Egreso por traslado inter-bodegas',
                ]);

                // Destination Ingreso
                InventoryMovement::create([
                    'company_id'     => $user->company_id,
                    'product_id'     => $productId,
                    'user_id'        => $user->id,
                    'branch_id'      => $destinationBranchId,
                    'type'           => 'ingreso_traslado',
                    'quantity'       => $qty,
                    'stock_before'   => $destStock,
                    'stock_after'    => $destStock + $qty,
                    'unit_cost'      => $product->cost ?? 0,
                    'reference_type' => StockTransfer::class,
                    'reference_id'   => $transfer->id,
                    'notes'          => 'Ingreso por traslado inter-bodegas',
                ]);
            }

            return $transfer;
        });
    }
}
