<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Product\Models\Product;
use App\Modules\Branch\Models\Branch;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        $productId = $request->get('product_id');
        $type      = $request->get('type');

        $movements = InventoryMovement::with(['product', 'user'])
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->when($type,      fn($q) => $q->where('type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $products = Product::active()->with('branches')->orderBy('name')->get();
        $branches = Branch::active()->orderBy('establishment_code')->get();

        return view('inventory.index', compact('movements', 'products', 'productId', 'type', 'branches'));
    }

    public function adjust(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id'  => 'required|exists:branches,id',
            'new_stock'  => 'required|numeric|min:0',
            'notes'      => 'required|string|max:255',
        ]);

        $product = Product::findOrFail($request->product_id);

        try {
            $this->inventoryService->adjust($product, $request->new_stock, $request->notes, (int) $request->branch_id);
            return redirect()->back()->with('success', "Stock de '{$product->name}' ajustado a {$request->new_stock}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
