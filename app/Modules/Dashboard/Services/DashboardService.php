<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleDetail;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseDetail;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Category;
use App\Modules\Partner\Models\Partner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Devuelve un resumen de KPIs para el período especificado.
     */
    public function getKpiSummary(int $month, int $year): array
    {
        $salesQuery = Sale::whereMonth('issue_date', $month)
            ->whereYear('issue_date', $year)
            ->whereNotIn('status', [Sale::STATUS_CANCELLED]);

        $totalRevenue     = (clone $salesQuery)->sum('total');
        $transactionCount = (clone $salesQuery)->count();
        $averageTicket    = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0;

        $totalPurchases = Purchase::whereMonth('issue_date', $month)
            ->whereYear('issue_date', $year)
            ->whereNotIn('status', [Purchase::STATUS_CANCELLED])
            ->sum('total');

        $accountsReceivable = Sale::where('status', Sale::STATUS_APPROVED)
            ->where('pending_balance', '>', 0)
            ->sum('pending_balance');

        $accountsPayable = Purchase::where('status', Purchase::STATUS_APPROVED)
            ->where('pending_balance', '>', 0)
            ->sum('pending_balance');

        $estimatedProfit = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->whereMonth('s.issue_date', $month)
            ->whereYear('s.issue_date', $year)
            ->whereNotIn('s.status', [Sale::STATUS_CANCELLED])
            ->select(DB::raw('SUM((sd.unit_price - sd.cost_price) * sd.quantity) as profit'))
            ->value('profit') ?? 0;

        return [
            'total_revenue'       => round((float) $totalRevenue, 2),
            'transaction_count'   => (int) $transactionCount,
            'average_ticket'      => round((float) $averageTicket, 2),
            'total_purchases'     => round((float) $totalPurchases, 2),
            'accounts_receivable' => round((float) $accountsReceivable, 2),
            'accounts_payable'    => round((float) $accountsPayable, 2),
            'estimated_profit'    => round((float) $estimatedProfit, 2),
        ];
    }

    /**
     * Ingresos agrupados por día para un mes/año específico.
     */
    public function getRevenueByDay(int $month, int $year): Collection
    {
        return Sale::whereMonth('issue_date', $month)
            ->whereYear('issue_date', $year)
            ->whereNotIn('status', [Sale::STATUS_CANCELLED])
            ->select(
                DB::raw('date(issue_date) as fecha'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();
    }

    /**
     * Ingresos agrupados por semana ISO para un mes/año específico.
     */
    public function getRevenueByWeek(int $month, int $year): Collection
    {
        return Sale::whereMonth('issue_date', $month)
            ->whereYear('issue_date', $year)
            ->whereNotIn('status', [Sale::STATUS_CANCELLED])
            ->select(
                DB::raw("strftime('%W', issue_date) as semana"),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('semana')
            ->orderBy('semana')
            ->get();
    }

    /**
     * Ingresos agrupados por mes para un año completo.
     */
    public function getRevenueByMonth(int $year): Collection
    {
        return Sale::whereYear('issue_date', $year)
            ->whereNotIn('status', [Sale::STATUS_CANCELLED])
            ->select(
                DB::raw("CAST(strftime('%m', issue_date) AS INTEGER) as mes"),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
    }

    /**
     * Top productos vendidos por cantidad.
     */
    public function getTopSoldProductsByQuantity(int $month, int $year, int $limit = 10): Collection
    {
        return DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->join('products as p', 'p.id', '=', 'sd.product_id')
            ->whereMonth('s.issue_date', $month)
            ->whereYear('s.issue_date', $year)
            ->whereNotIn('s.status', [Sale::STATUS_CANCELLED])
            ->whereNull('s.deleted_at')
            ->select(
                'p.id',
                'p.name',
                'p.code',
                DB::raw('SUM(sd.quantity) as total_quantity'),
                DB::raw('SUM(sd.subtotal) as total_revenue')
            )
            ->groupBy('p.id', 'p.name', 'p.code')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Top productos vendidos por ingreso.
     */
    public function getTopSoldProductsByRevenue(int $month, int $year, int $limit = 10): Collection
    {
        return DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->join('products as p', 'p.id', '=', 'sd.product_id')
            ->whereMonth('s.issue_date', $month)
            ->whereYear('s.issue_date', $year)
            ->whereNotIn('s.status', [Sale::STATUS_CANCELLED])
            ->whereNull('s.deleted_at')
            ->select(
                'p.id',
                'p.name',
                'p.code',
                DB::raw('SUM(sd.quantity) as total_quantity'),
                DB::raw('SUM(sd.subtotal) as total_revenue')
            )
            ->groupBy('p.id', 'p.name', 'p.code')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Top productos comprados por cantidad.
     */
    public function getTopPurchasedProducts(int $month, int $year, int $limit = 10): Collection
    {
        return DB::table('purchase_details as pd')
            ->join('purchases as pu', 'pu.id', '=', 'pd.purchase_id')
            ->join('products as p', 'p.id', '=', 'pd.product_id')
            ->whereMonth('pu.issue_date', $month)
            ->whereYear('pu.issue_date', $year)
            ->whereNotIn('pu.status', [Purchase::STATUS_CANCELLED])
            ->whereNull('pu.deleted_at')
            ->select(
                'p.id',
                'p.name',
                'p.code',
                DB::raw('SUM(pd.quantity) as total_quantity'),
                DB::raw('SUM(pd.subtotal) as total_cost')
            )
            ->groupBy('p.id', 'p.name', 'p.code')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Categorías más vendidas por ingreso.
     */
    public function getTopSellingCategories(int $month, int $year): Collection
    {
        $results = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->join('products as p', 'p.id', '=', 'sd.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->whereMonth('s.issue_date', $month)
            ->whereYear('s.issue_date', $year)
            ->whereNotIn('s.status', [Sale::STATUS_CANCELLED])
            ->whereNull('s.deleted_at')
            ->select(
                'c.id',
                'c.name',
                DB::raw('SUM(sd.subtotal) as total_revenue')
            )
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total_revenue')
            ->get();

        $grandTotal = $results->sum('total_revenue');

        return $results->map(function ($row) use ($grandTotal) {
            $row->percentage = $grandTotal > 0
                ? round(($row->total_revenue / $grandTotal) * 100, 1)
                : 0;
            return $row;
        });
    }

    /**
     * Clientes más frecuentes por número de transacciones.
     */
    public function getTopFrequentCustomers(int $month, int $year, int $limit = 10): Collection
    {
        return DB::table('sales as s')
            ->join('partners as pa', 'pa.id', '=', 's.partner_id')
            ->whereMonth('s.issue_date', $month)
            ->whereYear('s.issue_date', $year)
            ->whereNotIn('s.status', [Sale::STATUS_CANCELLED])
            ->whereNull('s.deleted_at')
            ->whereIn('pa.type', ['cliente', 'ambos'])
            ->select(
                'pa.id',
                DB::raw("COALESCE(pa.trade_name, pa.business_name) as display_name"),
                DB::raw('COUNT(s.id) as transaction_count'),
                DB::raw('SUM(s.total) as total_amount')
            )
            ->groupBy('pa.id', 'pa.trade_name', 'pa.business_name')
            ->orderByDesc('transaction_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Montos totales por cliente para el período.
     */
    public function getAmountsByClient(int $month, int $year, int $limit = 10): Collection
    {
        return DB::table('sales as s')
            ->join('partners as pa', 'pa.id', '=', 's.partner_id')
            ->whereMonth('s.issue_date', $month)
            ->whereYear('s.issue_date', $year)
            ->whereNotIn('s.status', [Sale::STATUS_CANCELLED])
            ->whereNull('s.deleted_at')
            ->whereIn('pa.type', ['cliente', 'ambos'])
            ->select(
                'pa.id',
                DB::raw("COALESCE(pa.trade_name, pa.business_name) as display_name"),
                DB::raw('SUM(s.total) as total_amount'),
                DB::raw('SUM(s.paid_amount) as paid_amount'),
                DB::raw('SUM(s.pending_balance) as pending_balance')
            )
            ->groupBy('pa.id', 'pa.trade_name', 'pa.business_name')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();
    }

    /**
     * Montos totales por proveedor para el período.
     */
    public function getAmountsBySupplier(int $month, int $year, int $limit = 10): Collection
    {
        return DB::table('purchases as pu')
            ->join('partners as pa', 'pa.id', '=', 'pu.partner_id')
            ->whereMonth('pu.issue_date', $month)
            ->whereYear('pu.issue_date', $year)
            ->whereNotIn('pu.status', [Purchase::STATUS_CANCELLED])
            ->whereNull('pu.deleted_at')
            ->whereIn('pa.type', ['proveedor', 'ambos'])
            ->select(
                'pa.id',
                DB::raw("COALESCE(pa.trade_name, pa.business_name) as display_name"),
                DB::raw('SUM(pu.total) as total_amount'),
                DB::raw('SUM(pu.paid_amount) as paid_amount'),
                DB::raw('SUM(pu.pending_balance) as pending_balance')
            )
            ->groupBy('pa.id', 'pa.trade_name', 'pa.business_name')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();
    }

    /**
     * Las ventas más recientes.
     */
    public function getRecentSales(int $limit = 10): Collection
    {
        return Sale::with(['partner', 'details'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
