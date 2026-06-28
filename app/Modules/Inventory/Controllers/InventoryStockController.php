<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Branch\Models\Branch;
use App\Modules\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryStockController extends Controller
{
    public function stockIndex(Request $request)
    {
        $companyId = Auth::user()->company_id;
        
        $branches = Branch::where('company_id', $companyId)->get();
        
        $selectedBranchId = $request->query('branch_id');
        
        $query = Product::where('company_id', $companyId)->orderBy('name');
        
        if ($selectedBranchId) {
            $products = $query->with(['branches' => function ($q) use ($selectedBranchId) {
                $q->where('branches.id', $selectedBranchId);
            }])->get();
        } else {
            $products = $query->with('branches')->get();
        }

        return view('inventory.stock', compact('products', 'branches', 'selectedBranchId'));
    }
}
