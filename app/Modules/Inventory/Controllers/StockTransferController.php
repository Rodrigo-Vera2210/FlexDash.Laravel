<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Branch\Models\Branch;
use App\Modules\Product\Models\Product;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Inventory\Requests\StoreStockTransferRequest;
use App\Modules\Inventory\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends Controller
{
    public function __construct(
        protected StockTransferService $transferService
    ) {}

    public function index()
    {
        $companyId = Auth::user()->company_id;

        $transfers = StockTransfer::where('company_id', $companyId)
            ->with(['originBranch', 'destinationBranch', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $canTransfer = Auth::user()->company->max_branches > 1;

        return view('inventory.transfers.index', compact('transfers', 'canTransfer'));
    }

    public function create()
    {
        $company = Auth::user()->company;

        if ($company->max_branches <= 1) {
            abort(403, 'Tu plan actual no admite multibodegas/traslados entre bodegas. Por favor, sube de nivel tu plan.');
        }

        $branches = Branch::where('company_id', $company->id)->get();
        $products = Product::where('company_id', $company->id)->orderBy('name')->get();

        return view('inventory.transfers.create', compact('branches', 'products'));
    }

    public function store(StoreStockTransferRequest $request)
    {
        $company = Auth::user()->company;

        if ($company->max_branches <= 1) {
            abort(403, 'Tu plan actual no admite multibodegas/traslados entre bodegas. Por favor, sube de nivel tu plan.');
        }

        try {
            $this->transferService->transferStock($request->user(), $request->validated());

            return redirect()->route('inventory.transfers.index')
                ->with('status', 'Traslado de mercancía realizado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['form' => $e->getMessage()])->withInput();
        }
    }
}
