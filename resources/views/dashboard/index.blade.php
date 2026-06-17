@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Resumen general del comercio · ' . now()->format('d/m/Y'))

@section('header-actions')
<a href="{{ route('sales.create') }}" class="btn-primary text-sm">
    <i class="fa-solid fa-plus"></i> Nueva Venta
</a>
@endsection

@section('content')
<div class="space-y-6 page-fade" x-data="dashboardApp()">

    {{-- ── Filter Bar ────────────────────────────────────── --}}
    <div class="card-panel p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-filter text-sm" style="color: var(--primary);"></i>
            <span class="text-sm font-bold" style="color: var(--text-main);">Período de análisis</span>
        </div>
        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <select name="month" class="input-solid text-sm" style="width: auto; min-width: 140px;">
                    @foreach([
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                    ] as $num => $name)
                        <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="year" class="input-solid text-sm" style="width: auto; min-width: 100px;">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="btn-primary text-sm">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
        </form>
    </div>

    {{-- ── Welcome Banner ───────────────────────────────────── --}}
    <div class="card-panel p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold" style="color: var(--text-main);">
                ¡Bienvenido, {{ Auth::user()->name }}! 👋
            </h2>
            <p class="text-sm mt-1" style="color: var(--text-tertiary);">
                Analíticas de
                <strong style="color: var(--primary);">
                    {{ [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'][$month] }}
                    {{ $year }}
                </strong>
            </p>
        </div>
        @if(Auth::user()->currentEnterprise ?? null)
        <div class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold"
             style="background-color: var(--primary-light); color: var(--primary); border: 1px solid rgba(10,126,165,0.15);">
            <i class="fa-solid fa-circle text-xs" style="color: #16A34A;"></i>
            {{ Auth::user()->currentEnterprise->name }}
        </div>
        @endif
    </div>

    {{-- ── KPI Cards ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        {{-- Ventas del Período --}}
        <div class="stat-card" style="border-left: 4px solid var(--primary); background-color: var(--surface); border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm); border-top: 1px solid var(--border-light); border-right: 1px solid var(--border-light); border-bottom: 1px solid var(--border-light);">
            <div class="flex justify-between items-start mb-3">
                <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-tertiary);">Ventas del Período</span>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: var(--primary-light); color: var(--primary);">
                    <i class="fa-solid fa-cart-shopping text-sm"></i>
                </div>
            </div>
            <div>
                <div class="text-2xl font-bold font-mono" style="color: var(--text-main);">S/ {{ number_format($kpis['total_revenue'], 2) }}</div>
            </div>
        </div>

        {{-- Transacciones --}}
        <div class="stat-card" style="border-left: 4px solid #F2A900; background-color: var(--surface); border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm); border-top: 1px solid var(--border-light); border-right: 1px solid var(--border-light); border-bottom: 1px solid var(--border-light);">
            <div class="flex justify-between items-start mb-3">
                <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-tertiary);">Transacciones</span>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: rgba(242,169,0,0.12); color: #D97706;">
                    <i class="fa-solid fa-receipt text-sm"></i>
                </div>
            </div>
            <div>
                <div class="text-2xl font-bold font-mono" style="color: var(--text-main);">{{ $kpis['transaction_count'] }}</div>
            </div>
        </div>

        {{-- Ticket Promedio --}}
        <div class="stat-card" style="border-left: 4px solid #E35205; background-color: var(--surface); border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm); border-top: 1px solid var(--border-light); border-right: 1px solid var(--border-light); border-bottom: 1px solid var(--border-light);">
            <div class="flex justify-between items-start mb-3">
                <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-tertiary);">Ticket Promedio</span>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: rgba(227,82,5,0.10); color: #E35205;">
                    <i class="fa-solid fa-calculator text-sm"></i>
                </div>
            </div>
            <div>
                <div class="text-2xl font-bold font-mono" style="color: var(--text-main);">S/ {{ number_format($kpis['average_ticket'], 2) }}</div>
            </div>
        </div>

        {{-- Ganancia Estimada --}}
        <div class="stat-card" style="border-left: 4px solid #A41D6A; background-color: var(--surface); border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm); border-top: 1px solid var(--border-light); border-right: 1px solid var(--border-light); border-bottom: 1px solid var(--border-light);">
            <div class="flex justify-between items-start mb-3">
                <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-tertiary);">Ganancia Estimada</span>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: rgba(164,29,106,0.10); color: #A41D6A;">
                    <i class="fa-solid fa-building-columns text-sm"></i>
                </div>
            </div>
            <div>
                <div class="text-2xl font-bold font-mono" style="color: var(--text-main);">S/ {{ number_format($kpis['estimated_profit'], 2) }}</div>
            </div>
        </div>

    </div>

    {{-- ── Secondary KPIs ────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="card-panel p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background-color: var(--success-light); color: var(--success);">
                <i class="fa-solid fa-arrow-down text-base"></i>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-tertiary);">Cuentas por Cobrar</p>
                <p class="text-lg font-bold font-mono mt-0.5" style="color: var(--success);">S/ {{ number_format($kpis['accounts_receivable'], 2) }}</p>
            </div>
        </div>
        <div class="card-panel p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background-color: var(--danger-light); color: var(--danger);">
                <i class="fa-solid fa-arrow-up text-base"></i>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-tertiary);">Cuentas por Pagar</p>
                <p class="text-lg font-bold font-mono mt-0.5" style="color: var(--danger);">S/ {{ number_format($kpis['accounts_payable'], 2) }}</p>
            </div>
        </div>
        <div class="card-panel p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background-color: var(--warning-light); color: var(--warning);">
                <i class="fa-solid fa-truck text-base"></i>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-tertiary);">Compras del Período</p>
                <p class="text-lg font-bold font-mono mt-0.5" style="color: var(--warning);">S/ {{ number_format($kpis['total_purchases'], 2) }}</p>
            </div>
        </div>
    </div>

    {{-- ── Charts + Categorías ──────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Gráfico de Ingresos --}}
        <div class="card-panel p-6 lg:col-span-2 flex flex-col">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h4 class="font-bold text-base" style="color: var(--text-main);">Analítica de Ingresos</h4>
                    <p class="text-xs mt-0.5" style="color: var(--text-tertiary);">Tendencia de ventas — <span x-text="chartModeLabel"></span></p>
                </div>
                <div class="flex gap-1 p-1 rounded-xl" style="background-color: var(--bg); border: 1px solid var(--border);">
                    <button @click="setChartMode('day')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :style="chartMode === 'day' ? 'background-color: var(--surface); color: var(--text-main); box-shadow: var(--shadow-sm);' : 'color: var(--text-tertiary);'">Día</button>
                    <button @click="setChartMode('week')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :style="chartMode === 'week' ? 'background-color: var(--surface); color: var(--text-main); box-shadow: var(--shadow-sm);' : 'color: var(--text-tertiary);'">Semana</button>
                    <button @click="setChartMode('month')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :style="chartMode === 'month' ? 'background-color: var(--surface); color: var(--text-main); box-shadow: var(--shadow-sm);' : 'color: var(--text-tertiary);'">Mes</button>
                </div>
            </div>

            <div class="flex-1 relative rounded-xl overflow-hidden" style="min-height: 260px; background-color: var(--bg); border: 1px solid var(--border-light); padding: 16px;">
                <canvas id="revenueChart" style="width: 100%; height: 100%;"></canvas>
            </div>
        </div>

        {{-- Categorías Populares --}}
        <div class="card-panel p-6 flex flex-col justify-between">
            <div>
                <h4 class="font-bold text-base mb-1" style="color: var(--text-main);">Categorías</h4>
                <p class="text-xs mb-5" style="color: var(--text-tertiary);">Distribución de ventas</p>

                @if($topCategories->isEmpty())
                    <div class="text-center py-8">
                        <i class="fa-solid fa-chart-pie text-3xl mb-2" style="color: var(--border);"></i>
                        <p class="text-sm" style="color: var(--text-tertiary);">Sin datos para este período</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @php
                            $catColors = ['#0A7EA5', '#F2A900', '#E35205', '#A41D6A', '#16A34A', '#6366F1', '#EC4899', '#14B8A6'];
                        @endphp
                        @foreach($topCategories->take(6) as $i => $cat)
                        <div>
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="font-semibold" style="color: var(--text-main);">{{ $cat->name }}</span>
                                <span style="color: var(--text-tertiary);">{{ $cat->percentage }}%</span>
                            </div>
                            <div class="w-full h-2.5 rounded-full overflow-hidden" style="background-color: var(--border-light);">
                                <div class="h-full rounded-full transition-all" style="width: {{ $cat->percentage }}%; background-color: {{ $catColors[$i % count($catColors)] }};"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="pt-5 mt-5 flex justify-between items-center text-xs" style="border-top: 1px solid var(--border-light); color: var(--text-tertiary);">
                <span>Datos del período seleccionado</span>
                <i class="fa-solid fa-chart-bar" style="color: var(--primary);"></i>
            </div>
        </div>

    </div>

    {{-- ── Productos Más Vendidos + Más Comprados ────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Productos Más Vendidos --}}
        <div class="card-panel overflow-hidden" x-data="{ soldTab: 'qty' }">
            <div class="px-6 py-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-light);">
                <div>
                    <h4 class="font-bold text-base" style="color: var(--text-main);">Productos Más Vendidos</h4>
                    <p class="text-xs mt-0.5" style="color: var(--text-tertiary);">Top 10 del período</p>
                </div>
                <div class="flex gap-1 p-1 rounded-xl" style="background-color: var(--bg); border: 1px solid var(--border);">
                    <button @click="soldTab = 'qty'" class="px-3 py-1 rounded-lg text-xs font-bold transition-all"
                            :style="soldTab === 'qty' ? 'background-color: var(--surface); color: var(--text-main); box-shadow: var(--shadow-sm);' : 'color: var(--text-tertiary);'">
                        Por Cantidad
                    </button>
                    <button @click="soldTab = 'revenue'" class="px-3 py-1 rounded-lg text-xs font-bold transition-all"
                            :style="soldTab === 'revenue' ? 'background-color: var(--surface); color: var(--text-main); box-shadow: var(--shadow-sm);' : 'color: var(--text-tertiary);'">
                        Por Ingreso
                    </button>
                </div>
            </div>

            {{-- By Quantity --}}
            <div x-show="soldTab === 'qty'" class="overflow-x-auto">
                @if($topSoldByQty->isEmpty())
                    <div class="text-center py-10">
                        <i class="fa-solid fa-box-open text-3xl mb-2" style="color: var(--border);"></i>
                        <p class="text-sm" style="color: var(--text-tertiary);">Sin datos para este período</p>
                    </div>
                @else
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="table-header">#</th>
                            <th class="table-header">Producto</th>
                            <th class="table-header text-right">Cantidad</th>
                            <th class="table-header text-right">Ingreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topSoldByQty as $i => $prod)
                        <tr>
                            <td class="table-cell font-mono text-xs font-bold" style="color: var(--text-tertiary);">{{ $i + 1 }}</td>
                            <td class="table-cell">
                                <span class="font-semibold text-sm" style="color: var(--text-main);">{{ $prod->name }}</span>
                                <span class="text-xs block" style="color: var(--text-tertiary);">{{ $prod->code }}</span>
                            </td>
                            <td class="table-cell text-right font-mono font-bold text-sm" style="color: var(--primary);">{{ number_format($prod->total_quantity, 0) }}</td>
                            <td class="table-cell text-right font-mono text-sm" style="color: var(--text-secondary);">S/ {{ number_format($prod->total_revenue, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

            {{-- By Revenue --}}
            <div x-show="soldTab === 'revenue'" x-cloak class="overflow-x-auto">
                @if($topSoldByRevenue->isEmpty())
                    <div class="text-center py-10">
                        <i class="fa-solid fa-box-open text-3xl mb-2" style="color: var(--border);"></i>
                        <p class="text-sm" style="color: var(--text-tertiary);">Sin datos para este período</p>
                    </div>
                @else
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="table-header">#</th>
                            <th class="table-header">Producto</th>
                            <th class="table-header text-right">Ingreso</th>
                            <th class="table-header text-right">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topSoldByRevenue as $i => $prod)
                        <tr>
                            <td class="table-cell font-mono text-xs font-bold" style="color: var(--text-tertiary);">{{ $i + 1 }}</td>
                            <td class="table-cell">
                                <span class="font-semibold text-sm" style="color: var(--text-main);">{{ $prod->name }}</span>
                                <span class="text-xs block" style="color: var(--text-tertiary);">{{ $prod->code }}</span>
                            </td>
                            <td class="table-cell text-right font-mono font-bold text-sm" style="color: var(--success);">S/ {{ number_format($prod->total_revenue, 2) }}</td>
                            <td class="table-cell text-right font-mono text-sm" style="color: var(--text-secondary);">{{ number_format($prod->total_quantity, 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

        {{-- Productos Más Comprados --}}
        <div class="card-panel overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-light);">
                <div>
                    <h4 class="font-bold text-base" style="color: var(--text-main);">Productos Más Comprados</h4>
                    <p class="text-xs mt-0.5" style="color: var(--text-tertiary);">Top 10 por volumen de compra</p>
                </div>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: var(--warning-light); color: var(--warning);">
                    <i class="fa-solid fa-truck text-sm"></i>
                </div>
            </div>
            <div class="overflow-x-auto">
                @if($topPurchasedProducts->isEmpty())
                    <div class="text-center py-10">
                        <i class="fa-solid fa-truck text-3xl mb-2" style="color: var(--border);"></i>
                        <p class="text-sm" style="color: var(--text-tertiary);">Sin compras para este período</p>
                    </div>
                @else
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="table-header">#</th>
                            <th class="table-header">Producto</th>
                            <th class="table-header text-right">Cantidad</th>
                            <th class="table-header text-right">Costo Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topPurchasedProducts as $i => $prod)
                        <tr>
                            <td class="table-cell font-mono text-xs font-bold" style="color: var(--text-tertiary);">{{ $i + 1 }}</td>
                            <td class="table-cell">
                                <span class="font-semibold text-sm" style="color: var(--text-main);">{{ $prod->name }}</span>
                                <span class="text-xs block" style="color: var(--text-tertiary);">{{ $prod->code }}</span>
                            </td>
                            <td class="table-cell text-right font-mono font-bold text-sm" style="color: var(--warning);">{{ number_format($prod->total_quantity, 0) }}</td>
                            <td class="table-cell text-right font-mono text-sm" style="color: var(--text-secondary);">S/ {{ number_format($prod->total_cost, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Últimas Ventas + Clientes Frecuentes ──────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Últimas Ventas --}}
        <div class="card-panel overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-light);">
                <div>
                    <h4 class="font-bold text-base" style="color: var(--text-main);">Últimas Ventas</h4>
                    <p class="text-xs mt-0.5" style="color: var(--text-tertiary);">Transacciones recientes</p>
                </div>
                <a href="{{ route('sales.index') }}" class="text-xs font-bold transition-colors" style="color: var(--primary);">Ver todas →</a>
            </div>
            <div class="overflow-x-auto">
                @if($recentSales->isEmpty())
                    <div class="text-center py-10">
                        <i class="fa-solid fa-receipt text-3xl mb-2" style="color: var(--border);"></i>
                        <p class="text-sm" style="color: var(--text-tertiary);">No hay ventas registradas</p>
                    </div>
                @else
                <table class="w-full" style="border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr>
                            <th class="table-header">Nro Venta</th>
                            <th class="table-header">Cliente</th>
                            <th class="table-header">Items</th>
                            <th class="table-header">Total</th>
                            <th class="table-header">Estado</th>
                            <th class="table-header">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSales as $sale)
                        <tr>
                            <td class="table-cell">
                                <a href="{{ route('sales.show', $sale->id) }}" class="font-bold font-mono text-xs transition-colors" style="color: var(--primary);">
                                    {{ $sale->series }}-{{ $sale->number }}
                                </a>
                            </td>
                            <td class="table-cell text-sm" style="color: var(--text-secondary);">{{ $sale->partner->display_name ?? '—' }}</td>
                            <td class="table-cell text-sm" style="color: var(--text-tertiary);">{{ $sale->details->count() }} items</td>
                            <td class="table-cell font-bold font-mono text-sm" style="color: var(--text-main);">S/ {{ number_format($sale->total, 2) }}</td>
                            <td class="table-cell">
                                @php
                                    $statusMap = [
                                        'BORRADOR' => 'draft',
                                        'APROBADO' => 'approved',
                                        'PAGADO'   => 'paid',
                                        'ANULADO'  => 'cancelled',
                                    ];
                                    $badgeClass = $statusMap[$sale->status] ?? 'draft';
                                @endphp
                                <span class="badge badge-{{ $badgeClass }}">{{ $sale->status }}</span>
                            </td>
                            <td class="table-cell text-xs font-mono" style="color: var(--text-tertiary);">{{ $sale->issue_date->format('d/m/Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

        {{-- Clientes Frecuentes --}}
        <div class="card-panel p-6 flex flex-col justify-between">
            <div>
                <h4 class="font-bold text-base mb-1" style="color: var(--text-main);">Clientes Frecuentes</h4>
                <p class="text-xs mb-5" style="color: var(--text-tertiary);">Mayores compradores del período</p>

                @if($topCustomers->isEmpty())
                    <div class="text-center py-8">
                        <i class="fa-solid fa-users text-3xl mb-2" style="color: var(--border);"></i>
                        <p class="text-sm" style="color: var(--text-tertiary);">Sin datos para este período</p>
                    </div>
                @else
                    @php
                        $customerColors = ['var(--primary)', '#F2A900', '#A41D6A', '#E35205', '#16A34A'];
                    @endphp
                    <div class="space-y-4">
                        @foreach($topCustomers->take(5) as $i => $c)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0"
                                     style="background-color: var(--primary-light); color: {{ $customerColors[$i % count($customerColors)] }}; border: 2px solid rgba(10,126,165,0.15);">
                                    {{ strtoupper(substr($c->display_name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold" style="color: var(--text-main);">{{ Str::limit($c->display_name, 20) }}</p>
                                    <p class="text-xs" style="color: var(--text-tertiary);">{{ $c->transaction_count }} {{ $c->transaction_count === 1 ? 'compra' : 'compras' }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-bold font-mono" style="color: var(--text-main);">S/ {{ number_format($c->total_amount, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="pt-5 mt-5" style="border-top: 1px solid var(--border-light);">
                <a href="{{ route('partners.index') }}?type=cliente" class="w-full py-2.5 rounded-xl text-xs font-bold transition-all btn-outline block text-center">
                    Ver todos los clientes
                </a>
            </div>
        </div>

    </div>

    {{-- ── Montos por Cliente + Proveedor ────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Montos por Cliente --}}
        <div class="card-panel overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-light);">
                <div>
                    <h4 class="font-bold text-base" style="color: var(--text-main);">Montos por Cliente</h4>
                    <p class="text-xs mt-0.5" style="color: var(--text-tertiary);">Resumen financiero del período</p>
                </div>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: var(--primary-light); color: var(--primary);">
                    <i class="fa-solid fa-users text-sm"></i>
                </div>
            </div>
            <div class="overflow-x-auto">
                @if($amountsByClient->isEmpty())
                    <div class="text-center py-10">
                        <i class="fa-solid fa-users text-3xl mb-2" style="color: var(--border);"></i>
                        <p class="text-sm" style="color: var(--text-tertiary);">Sin ventas para este período</p>
                    </div>
                @else
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Cliente</th>
                            <th class="table-header text-right">Total</th>
                            <th class="table-header text-right">Pagado</th>
                            <th class="table-header text-right">Pendiente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($amountsByClient as $client)
                        <tr>
                            <td class="table-cell font-semibold text-sm" style="color: var(--text-main);">{{ Str::limit($client->display_name, 25) }}</td>
                            <td class="table-cell text-right font-mono text-sm" style="color: var(--text-main);">S/ {{ number_format($client->total_amount, 2) }}</td>
                            <td class="table-cell text-right font-mono text-sm" style="color: var(--success);">S/ {{ number_format($client->paid_amount, 2) }}</td>
                            <td class="table-cell text-right font-mono text-sm font-bold" style="color: {{ $client->pending_balance > 0 ? 'var(--danger)' : 'var(--text-tertiary)' }};">S/ {{ number_format($client->pending_balance, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

        {{-- Montos por Proveedor --}}
        <div class="card-panel overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-light);">
                <div>
                    <h4 class="font-bold text-base" style="color: var(--text-main);">Montos por Proveedor</h4>
                    <p class="text-xs mt-0.5" style="color: var(--text-tertiary);">Resumen financiero del período</p>
                </div>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: rgba(227,82,5,0.10); color: #E35205;">
                    <i class="fa-solid fa-building text-sm"></i>
                </div>
            </div>
            <div class="overflow-x-auto">
                @if($amountsBySupplier->isEmpty())
                    <div class="text-center py-10">
                        <i class="fa-solid fa-building text-3xl mb-2" style="color: var(--border);"></i>
                        <p class="text-sm" style="color: var(--text-tertiary);">Sin compras para este período</p>
                    </div>
                @else
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Proveedor</th>
                            <th class="table-header text-right">Total</th>
                            <th class="table-header text-right">Pagado</th>
                            <th class="table-header text-right">Pendiente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($amountsBySupplier as $sup)
                        <tr>
                            <td class="table-cell font-semibold text-sm" style="color: var(--text-main);">{{ Str::limit($sup->display_name, 25) }}</td>
                            <td class="table-cell text-right font-mono text-sm" style="color: var(--text-main);">S/ {{ number_format($sup->total_amount, 2) }}</td>
                            <td class="table-cell text-right font-mono text-sm" style="color: var(--success);">S/ {{ number_format($sup->paid_amount, 2) }}</td>
                            <td class="table-cell text-right font-mono text-sm font-bold" style="color: {{ $sup->pending_balance > 0 ? 'var(--danger)' : 'var(--text-tertiary)' }};">S/ {{ number_format($sup->pending_balance, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<script>
    // ── Chart Data from Backend ───────────────────────────────────────
    const revenueByDay   = @json($revenueByDay);
    const revenueByWeek  = @json($revenueByWeek);
    const revenueByMonth = @json($revenueByMonth);

    const monthNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    let revenueChart = null;

    function buildChart(labels, data, label) {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        if (revenueChart) {
            revenueChart.destroy();
        }

        // Detect dark mode
        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        const textColor = isDark ? '#9CA3AF' : '#6B7280';

        revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    backgroundColor: 'rgba(10,126,165,0.7)',
                    borderColor: '#0A7EA5',
                    borderWidth: 2,
                    borderRadius: 6,
                    hoverBackgroundColor: '#0A7EA5',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#162538' : '#0D1E36',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'S/ ' + Number(context.parsed.y).toLocaleString('es-PE', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 11, weight: 600 } }
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            font: { size: 11 },
                            callback: function(value) {
                                return 'S/ ' + (value >= 1000 ? (value/1000).toFixed(1) + 'k' : value);
                            }
                        }
                    }
                }
            }
        });
    }

    function dashboardApp() {
        return {
            chartMode: 'day',
            chartModeLabel: 'por día',

            init() {
                this.$nextTick(() => this.renderChart());
            },

            setChartMode(mode) {
                this.chartMode = mode;
                this.renderChart();
            },

            renderChart() {
                if (this.chartMode === 'day') {
                    this.chartModeLabel = 'por día';
                    const labels = revenueByDay.map(r => {
                        const d = new Date(r.fecha + 'T00:00:00');
                        return d.getDate() + '/' + (d.getMonth() + 1);
                    });
                    const data = revenueByDay.map(r => parseFloat(r.total));
                    buildChart(labels, data, 'Ingresos diarios');
                } else if (this.chartMode === 'week') {
                    this.chartModeLabel = 'por semana';
                    const labels = revenueByWeek.map(r => 'Sem ' + r.semana);
                    const data = revenueByWeek.map(r => parseFloat(r.total));
                    buildChart(labels, data, 'Ingresos semanales');
                } else {
                    this.chartModeLabel = 'por mes';
                    const labels = revenueByMonth.map(r => monthNames[parseInt(r.mes) - 1]);
                    const data = revenueByMonth.map(r => parseFloat(r.total));
                    buildChart(labels, data, 'Ingresos mensuales');
                }
            }
        };
    }
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
@endpush
