<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Partner\Models\Partner;
use App\Modules\Product\Models\Product;
use App\Modules\Sale\Models\Sale;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search partners (clients/suppliers) by name or document number.
     */
    public function partners(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = Partner::where('company_id', auth()->user()->company_id)
            ->when($type, fn($q) => $q->where('type', $type))
            ->where(function ($q) use ($query) {
                $q->where('business_name', 'like', "%{$query}%")
                  ->orWhere('document_number', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'business_name', 'document_number', 'type']);

        return response()->json($results);
    }

    /**
     * Search products by name or code.
     */
    public function products(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Eloquent queries on Product will automatically scope by tenant (company_id)
        $results = Product::with('tax')
            ->where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->limit(15)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'price' => (float) $product->price,
                    'cost' => (float) $product->cost,
                    'tax_id' => $product->tax_id,
                    'tax_rate' => $product->tax ? (float) $product->tax->rate : 0.0,
                    'stock' => (float) $product->stockInBranch(session('active_branch_id') ?? auth()->user()->branch_id),
                ];
            });

        return response()->json($results);
    }

    /**
     * Search documents (sales/invoices) by sequential number.
     */
    public function documents(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Sale has BelongsToBranch, so it's auto-scoped to the active branch of the session.
        $results = Sale::with('partner')
            ->where(function ($q) use ($query) {
                $q->where('number', 'like', "%{$query}%")
                  ->orWhere('series', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'number' => $sale->number,
                    'series' => $sale->series,
                    'total' => $sale->total,
                    'pending_balance' => $sale->pending_balance,
                    'partner_name' => $sale->partner ? $sale->partner->business_name : 'Cliente General',
                ];
            });

        return response()->json($results);
    }

    /**
     * Search services by name or code.
     */
    public function services(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = \App\Modules\Service\Models\Service::where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->limit(15)
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'code' => $service->code,
                    'price' => (float) $service->price,
                    'cost' => (float) ($service->cost ?? 0),
                    'tax_id' => $service->tax_id,
                    'tax_rate' => $service->tax ? (float) $service->tax->rate : 0.0,
                ];
            });

        return response()->json($results);
    }
}
