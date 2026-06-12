<?php

namespace App\Modules\Dashboard\Controllers;
use App\Http\Controllers\Controller;

use App\Modules\Product\Models\Product;
use App\Modules\Sale\Models\Sale;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // KPI 1: Total vendido hoy
        $totalVentasHoy = Sale::today()
            ->whereNotIn('status', [Sale::STATUS_CANCELLED])
            ->sum('total');

        // KPI 2: Cuentas por cobrar (ventas aprobadas con saldo pendiente)
        $cuentasPorCobrar = Sale::approved()
            ->pending()
            ->sum('pending_balance');

        // KPI 3: Cuentas por pagar (compras aprobadas con saldo pendiente)
        $cuentasPorPagar = Purchase::approved()
            ->pending()
            ->sum('pending_balance');

        // KPI 4: Productos con stock crítico
        $productosStockCritico = Product::lowStock()
            ->with('category')
            ->orderBy('stock')
            ->limit(10)
            ->get();

        $totalStockCritico = Product::lowStock()->count();

        // KPI 5: Ganancia estimada del mes
        $gananciaEstimadaMes = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->join('products as p', 'p.id', '=', 'sd.product_id')
            ->whereMonth('s.issue_date', now()->month)
            ->whereYear('s.issue_date', now()->year)
            ->whereNotIn('s.status', [Sale::STATUS_CANCELLED])
            ->select(DB::raw('SUM((sd.unit_price - sd.cost_price) * sd.quantity) as ganancia'))
            ->value('ganancia') ?? 0;

        // KPI 6: Total comprado este mes
        $totalComprasMes = Purchase::thisMonth()
            ->whereNotIn('status', [Purchase::STATUS_CANCELLED])
            ->sum('total');

        // KPI 7: Ventas del mes
        $totalVentasMes = Sale::thisMonth()
            ->whereNotIn('status', [Sale::STATUS_CANCELLED])
            ->sum('total');

        // Últimas 5 ventas
        $ultimasVentas = Sale::with('partner')
            ->latest()
            ->limit(5)
            ->get();

        // Últimas 5 actividades del audit log
        $ultimasActividades = AuditLog::with('user')
            ->latest()
            ->limit(8)
            ->get();

        // Ventas de los últimos 30 días (para gráfico)
        $ventasPorDia = Sale::whereNotIn('status', [Sale::STATUS_CANCELLED])
            ->where('issue_date', '>=', now()->subDays(29)->startOfDay())
            ->select(DB::raw('date(issue_date) as fecha'), DB::raw('SUM(total) as total'))
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->keyBy('fecha');

        return view('dashboard.index', compact(
            'totalVentasHoy',
            'cuentasPorCobrar',
            'cuentasPorPagar',
            'productosStockCritico',
            'totalStockCritico',
            'gananciaEstimadaMes',
            'totalComprasMes',
            'totalVentasMes',
            'ultimasVentas',
            'ultimasActividades',
            'ventasPorDia',
        ));
    }
}
