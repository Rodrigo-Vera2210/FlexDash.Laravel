<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Product\Models\Product;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service
    ) {}

    public function index(Request $request): View
    {
        $month = (int) $request->query('month', now()->month);
        $year  = (int) $request->query('year', now()->year);

        // ── KPIs ─────────────────────────────────────────────────
        $kpis = $this->service->getKpiSummary($month, $year);

        // ── Revenue Chart Data ───────────────────────────────────
        $revenueByDay   = $this->service->getRevenueByDay($month, $year);
        $revenueByWeek  = $this->service->getRevenueByWeek($month, $year);
        $revenueByMonth = $this->service->getRevenueByMonth($year);

        // ── Rankings ─────────────────────────────────────────────
        $topSoldByQty         = $this->service->getTopSoldProductsByQuantity($month, $year);
        $topSoldByRevenue     = $this->service->getTopSoldProductsByRevenue($month, $year);
        $topPurchasedProducts = $this->service->getTopPurchasedProducts($month, $year);
        $topCategories        = $this->service->getTopSellingCategories($month, $year);
        $topCustomers         = $this->service->getTopFrequentCustomers($month, $year);

        // ── Financial Summaries ──────────────────────────────────
        $amountsByClient   = $this->service->getAmountsByClient($month, $year);
        $amountsBySupplier = $this->service->getAmountsBySupplier($month, $year);

        // ── Recent Data ──────────────────────────────────────────
        $recentSales = $this->service->getRecentSales();

        // ── Preserved: Low stock & audit log ─────────────────────
        $productosStockCritico = Product::lowStock()
            ->with('category')
            ->orderBy('stock')
            ->limit(10)
            ->get();

        $totalStockCritico = Product::lowStock()->count();

        $ultimasActividades = AuditLog::with('user')
            ->latest()
            ->limit(8)
            ->get();

        return view('dashboard.index', compact(
            'month', 'year', 'kpis',
            'revenueByDay', 'revenueByWeek', 'revenueByMonth',
            'topSoldByQty', 'topSoldByRevenue',
            'topPurchasedProducts', 'topCategories', 'topCustomers',
            'amountsByClient', 'amountsBySupplier', 'recentSales',
            'productosStockCritico', 'totalStockCritico',
            'ultimasActividades',
        ));
    }
}
