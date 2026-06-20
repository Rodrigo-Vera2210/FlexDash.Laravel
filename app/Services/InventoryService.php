<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Registra una SALIDA de stock (venta).
     * Lanza InsufficientStockException si el stock resultante sería negativo.
     */
    public function exit(
        Product $product,
        float   $quantity,
        string  $referenceType,
        int     $referenceId,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $quantity, $referenceType, $referenceId, $notes) {
            // Bloquear fila para evitar race conditions
            $product = Product::lockForUpdate()->findOrFail($product->id);

            if ($product->stock < $quantity) {
                throw new InsufficientStockException($product->name, $product->stock, $quantity);
            }

            $stockBefore = $product->stock;
            $stockAfter  = $stockBefore - $quantity;

            $product->update(['stock' => $stockAfter]);

            return InventoryMovement::create([
                'product_id'     => $product->id,
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

    /**
     * Registra una ENTRADA de stock (compra o ajuste).
     */
    public function entry(
        Product $product,
        float   $quantity,
        float   $unitCost,
        string  $referenceType,
        int     $referenceId,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $quantity, $unitCost, $referenceType, $referenceId, $notes) {
            $product = Product::lockForUpdate()->findOrFail($product->id);

            $stockBefore = $product->stock;
            $stockAfter  = $stockBefore + $quantity;

            // Actualizar costo promedio ponderado
            if ($stockBefore > 0) {
                $newCost = (($product->cost * $stockBefore) + ($unitCost * $quantity)) / $stockAfter;
                $product->update(['stock' => $stockAfter, 'cost' => round($newCost, 4)]);
            } else {
                $product->update(['stock' => $stockAfter, 'cost' => $unitCost]);
            }

            return InventoryMovement::create([
                'product_id'     => $product->id,
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

    /**
     * Ajuste manual de stock (con razón).
     */
    public function adjust(Product $product, float $newStock, ?string $notes = null): InventoryMovement
    {
        return DB::transaction(function () use ($product, $newStock, $notes) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $stockBefore = $product->stock;
            $diff = $newStock - $stockBefore;

            $product->update(['stock' => $newStock]);

            return InventoryMovement::create([
                'product_id'   => $product->id,
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

    /**
     * Devuelve stock (por anulación de venta).
     */
    public function return(
        Product $product,
        float   $quantity,
        string  $referenceType,
        int     $referenceId,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $quantity, $referenceType, $referenceId, $notes) {
            $product = Product::lockForUpdate()->findOrFail($product->id);

            $stockBefore = $product->stock;
            $stockAfter  = $stockBefore + $quantity;

            $product->update(['stock' => $stockAfter]);

            return InventoryMovement::create([
                'product_id'     => $product->id,
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
}
