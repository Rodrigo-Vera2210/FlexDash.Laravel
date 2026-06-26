<?php

namespace App\Modules\Inventory\Services;

use App\Exceptions\InsufficientStockException;
use App\Modules\Branch\Models\Branch;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function exit(
        Product $product,
        float   $quantity,
        string  $referenceType,
        int     $referenceId,
        ?string $notes = null,
        ?int    $branchId = null
    ): InventoryMovement {
        $branchId = $this->resolveBranchId($branchId);

        return DB::transaction(function () use ($product, $quantity, $referenceType, $referenceId, $notes, $branchId) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $stockBefore = $this->getBranchStock($product, $branchId);

            if ($stockBefore < $quantity) {
                throw new InsufficientStockException($product->name, $stockBefore, $quantity);
            }

            $stockAfter = $stockBefore - $quantity;
            $this->updateBranchStock($product, $branchId, $stockAfter);
            $this->syncGlobalStock($product);

            return InventoryMovement::create([
                'product_id'     => $product->id,
                'branch_id'      => $branchId,
                'user_id'        => auth()->id(),
                'type'           => 'salida',
                'quantity'       => $quantity,
                'stock_before'   => $stockBefore,
                'stock_after'    => $stockAfter,
                'unit_cost'      => $product->cost,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'notes'          => $notes,
            ]);
        });
    }

    public function entry(
        Product $product,
        float   $quantity,
        float   $unitCost,
        string  $referenceType,
        int     $referenceId,
        ?string $notes = null,
        ?int    $branchId = null
    ): InventoryMovement {
        $branchId = $this->resolveBranchId($branchId);

        return DB::transaction(function () use ($product, $quantity, $unitCost, $referenceType, $referenceId, $notes, $branchId) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $stockBefore = $this->getBranchStock($product, $branchId);
            $stockAfter  = $stockBefore + $quantity;
            $globalStock = (float) $product->stock;

            if ($globalStock > 0) {
                $newCost = (($product->cost * $globalStock) + ($unitCost * $quantity)) / ($globalStock + $quantity);
                $product->update(['cost' => round($newCost, 4)]);
            } else {
                $product->update(['cost' => $unitCost]);
            }

            $this->updateBranchStock($product, $branchId, $stockAfter);
            $this->syncGlobalStock($product);

            return InventoryMovement::create([
                'product_id'     => $product->id,
                'branch_id'      => $branchId,
                'user_id'        => auth()->id(),
                'type'           => 'entrada',
                'quantity'       => $quantity,
                'stock_before'   => $stockBefore,
                'stock_after'    => $stockAfter,
                'unit_cost'      => $unitCost,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'notes'          => $notes,
            ]);
        });
    }

    public function adjust(Product $product, float $newStock, ?string $notes = null, ?int $branchId = null): InventoryMovement
    {
        $branchId = $this->resolveBranchId($branchId);

        return DB::transaction(function () use ($product, $newStock, $notes, $branchId) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $stockBefore = $this->getBranchStock($product, $branchId);
            $diff = $newStock - $stockBefore;

            $this->updateBranchStock($product, $branchId, $newStock);
            $this->syncGlobalStock($product);

            return InventoryMovement::create([
                'product_id'   => $product->id,
                'branch_id'    => $branchId,
                'user_id'      => auth()->id(),
                'type'         => 'ajuste',
                'quantity'     => abs($diff),
                'stock_before' => $stockBefore,
                'stock_after'  => $newStock,
                'unit_cost'    => $product->cost,
                'notes'        => $notes,
            ]);
        });
    }

    public function return(
        Product $product,
        float   $quantity,
        string  $referenceType,
        int     $referenceId,
        ?string $notes = null,
        ?int    $branchId = null
    ): InventoryMovement {
        $branchId = $this->resolveBranchId($branchId);

        return DB::transaction(function () use ($product, $quantity, $referenceType, $referenceId, $notes, $branchId) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $stockBefore = $this->getBranchStock($product, $branchId);
            $stockAfter  = $stockBefore + $quantity;

            $this->updateBranchStock($product, $branchId, $stockAfter);
            $this->syncGlobalStock($product);

            return InventoryMovement::create([
                'product_id'     => $product->id,
                'branch_id'      => $branchId,
                'user_id'        => auth()->id(),
                'type'           => 'devolucion',
                'quantity'       => $quantity,
                'stock_before'   => $stockBefore,
                'stock_after'    => $stockAfter,
                'unit_cost'      => $product->cost,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'notes'          => $notes,
            ]);
        });
    }

    private function resolveBranchId(?int $branchId): int
    {
        if ($branchId) {
            return $branchId;
        }

        $userBranch = auth()->user()?->branch_id;
        if ($userBranch) {
            return (int) $userBranch;
        }

        $default = Branch::active()->value('id');
        if (!$default) {
            throw new InvalidArgumentException('No hay sucursal activa para registrar el movimiento de inventario.');
        }

        return (int) $default;
    }

    private function getBranchStock(Product $product, int $branchId): float
    {
        $pivot = $product->branches()->where('branches.id', $branchId)->first();

        return $pivot ? (float) $pivot->pivot->stock : 0.0;
    }

    private function updateBranchStock(Product $product, int $branchId, float $newStock): void
    {
        $product->branches()->syncWithoutDetaching([
            $branchId => ['stock' => $newStock],
        ]);
    }

    private function syncGlobalStock(Product $product): void
    {
        $total = (float) DB::table('branch_product')
            ->where('product_id', $product->id)
            ->sum('stock');

        $product->update(['stock' => $total]);
    }
}
