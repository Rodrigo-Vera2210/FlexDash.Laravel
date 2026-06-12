@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Resumen general del comercio · ' . now()->format('d/m/Y'))

@section('header-actions')
<button class="btn-primary text-sm">
    <i class="fa-solid fa-plus"></i> Nueva Venta
</button>
@endsection

@section('content')
<div class="space-y-6 page-fade">

    {{-- ── Welcome Banner ───────────────────────────────────── --}}
    <div class="card-panel p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold" style="color: var(--text-main);">
                ¡Bienvenido, {{ Auth::user()->name }}! 👋
            </h2>
            <p class="text-sm mt-1" style="color: var(--text-tertiary);">
                Este es el resumen general de tu comercio para hoy.
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

        {{-- Ventas del Día --}}
        <div class="stat-card" style="border-left-color: var(--primary);">
            <div class="flex justify-between items-start mb-3">
                <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-tertiary);">Ventas del Día</span>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: var(--primary-light); color: var(--primary);">
                    <i class="fa-solid fa-cart-shopping text-sm"></i>
                </div>
            </div>
            <div>
                <div class="text-2xl font-bold font-mono" style="color: var(--text-main);">$1,248.50</div>
                <div class="text-xs mt-1.5 flex items-center gap-1 font-semibold" style="color: var(--success);">
                    <i class="fa-solid fa-arrow-up text-xs"></i> +12.5%
                    <span class="font-normal" style="color: var(--text-tertiary);">vs ayer</span>
                </div>
            </div>
        </div>

        {{-- Transacciones --}}
        <div class="stat-card" style="border-left-color: #F2A900;">
            <div class="flex justify-between items-start mb-3">
                <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-tertiary);">Transacciones</span>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: rgba(242,169,0,0.12); color: #D97706;">
                    <i class="fa-solid fa-receipt text-sm"></i>
                </div>
            </div>
            <div>
                <div class="text-2xl font-bold font-mono" style="color: var(--text-main);">48</div>
                <div class="text-xs mt-1.5 flex items-center gap-1 font-semibold" style="color: var(--success);">
                    <i class="fa-solid fa-arrow-up text-xs"></i> +8.3%
                    <span class="font-normal" style="color: var(--text-tertiary);">vs ayer</span>
                </div>
            </div>
        </div>

        {{-- Ticket Promedio --}}
        <div class="stat-card" style="border-left-color: #E35205;">
            <div class="flex justify-between items-start mb-3">
                <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-tertiary);">Ticket Promedio</span>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: rgba(227,82,5,0.10); color: #E35205;">
                    <i class="fa-solid fa-calculator text-sm"></i>
                </div>
            </div>
            <div>
                <div class="text-2xl font-bold font-mono" style="color: var(--text-main);">$26.01</div>
                <div class="text-xs mt-1.5 flex items-center gap-1 font-semibold" style="color: var(--danger);">
                    <i class="fa-solid fa-arrow-down text-xs"></i> -1.2%
                    <span class="font-normal" style="color: var(--text-tertiary);">vs ayer</span>
                </div>
            </div>
        </div>

        {{-- Ingreso Mensual --}}
        <div class="stat-card" style="border-left-color: #A41D6A;">
            <div class="flex justify-between items-start mb-3">
                <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-tertiary);">Ingreso Mensual</span>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: rgba(164,29,106,0.10); color: #A41D6A;">
                    <i class="fa-solid fa-building-columns text-sm"></i>
                </div>
            </div>
            <div>
                <div class="text-2xl font-bold font-mono" style="color: var(--text-main);">$48,291</div>
                <div class="text-xs mt-1.5 flex items-center gap-1 font-semibold" style="color: var(--success);">
                    <i class="fa-solid fa-arrow-up text-xs"></i> +24.1%
                    <span class="font-normal" style="color: var(--text-tertiary);">vs mes ant.</span>
                </div>
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
                    <p class="text-xs mt-0.5" style="color: var(--text-tertiary);">Tendencia de ventas — última semana</p>
                </div>
                <div class="flex gap-1 p-1 rounded-xl" style="background-color: var(--bg); border: 1px solid var(--border);">
                    <button class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            style="background-color: var(--surface); color: var(--text-main); box-shadow: var(--shadow-sm);">Semana</button>
                    <button class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                            style="color: var(--text-tertiary);">Mes</button>
                </div>
            </div>

            {{-- SVG Spline Chart --}}
            <div class="flex-1 relative rounded-xl overflow-hidden flex flex-col justify-end" style="min-height: 200px; background-color: var(--bg); border: 1px solid var(--border-light); padding: 16px 12px 8px;">
                <svg viewBox="0 0 700 180" class="absolute inset-0 w-full h-full overflow-visible" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="teal-grad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#0A7EA5" stop-opacity="0.25"/>
                            <stop offset="100%" stop-color="#0A7EA5" stop-opacity="0"/>
                        </linearGradient>
                        <filter id="line-glow">
                            <feGaussianBlur stdDeviation="3" result="blur"/>
                            <feComposite in="SourceGraphic" in2="blur" operator="over"/>
                        </filter>
                    </defs>
                    {{-- Líneas de grilla --}}
                    <line x1="0" y1="36" x2="700" y2="36" stroke="currentColor" stroke-opacity="0.07" stroke-width="1" stroke-dasharray="5 4"/>
                    <line x1="0" y1="80" x2="700" y2="80" stroke="currentColor" stroke-opacity="0.07" stroke-width="1" stroke-dasharray="5 4"/>
                    <line x1="0" y1="124" x2="700" y2="124" stroke="currentColor" stroke-opacity="0.07" stroke-width="1" stroke-dasharray="5 4"/>
                    {{-- Área de gradiente --}}
                    <path d="M 10 155 C 100 130, 120 155, 200 115 C 280 85, 320 60, 400 95 C 480 130, 520 48, 600 42 C 650 36, 665 22, 690 12 L 690 180 L 10 180 Z"
                          fill="url(#teal-grad)"/>
                    {{-- Línea spline --}}
                    <path d="M 10 155 C 100 130, 120 155, 200 115 C 280 85, 320 60, 400 95 C 480 130, 520 48, 600 42 C 650 36, 665 22, 690 12"
                          fill="none" stroke="#0A7EA5" stroke-width="3" stroke-linecap="round" filter="url(#line-glow)"/>
                    {{-- Puntos de datos --}}
                    <circle cx="10"  cy="155" r="5" fill="#0A7EA5" stroke="white" stroke-width="2.5"/>
                    <circle cx="200" cy="115" r="5" fill="#0A7EA5" stroke="white" stroke-width="2.5"/>
                    <circle cx="400" cy="95"  r="5" fill="#0A7EA5" stroke="white" stroke-width="2.5"/>
                    <circle cx="600" cy="42"  r="5" fill="#0A7EA5" stroke="white" stroke-width="2.5"/>
                    <circle cx="690" cy="12"  r="6" fill="#F2A900" stroke="white" stroke-width="2.5"/>
                </svg>
                {{-- Etiquetas de eje X --}}
                <div class="relative z-10 flex justify-between pt-2 mt-auto">
                    @foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $day)
                    <span class="text-xs font-semibold" style="color: var(--text-tertiary);">{{ $day }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Categorías Populares --}}
        <div class="card-panel p-6 flex flex-col justify-between">
            <div>
                <h4 class="font-bold text-base mb-1" style="color: var(--text-main);">Categorías</h4>
                <p class="text-xs mb-5" style="color: var(--text-tertiary);">Distribución de ventas</p>

                <div class="space-y-4">
                    @foreach([
                        ['name'=>'Electrónica',         'pct'=>45, 'color'=>'#0A7EA5'],
                        ['name'=>'Alimentos',            'pct'=>30, 'color'=>'#F2A900'],
                        ['name'=>'Ropa y Calzado',       'pct'=>15, 'color'=>'#E35205'],
                        ['name'=>'Otros',                'pct'=>10, 'color'=>'#A41D6A'],
                    ] as $cat)
                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="font-semibold" style="color: var(--text-main);">{{ $cat['name'] }}</span>
                            <span style="color: var(--text-tertiary);">{{ $cat['pct'] }}%</span>
                        </div>
                        <div class="w-full h-2.5 rounded-full overflow-hidden" style="background-color: var(--border-light);">
                            <div class="h-full rounded-full transition-all" style="width: {{ $cat['pct'] }}%; background-color: {{ $cat['color'] }};"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-5 mt-5 flex justify-between items-center text-xs" style="border-top: 1px solid var(--border-light); color: var(--text-tertiary);">
                <span>Datos en tiempo real</span>
                <i class="fa-solid fa-arrows-rotate animate-spin" style="color: var(--primary);"></i>
            </div>
        </div>

    </div>

    {{-- ── Tabla de Ventas + Clientes ───────────────────────── --}}
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
                <table class="w-full" style="border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr>
                            <th class="table-header">ID Venta</th>
                            <th class="table-header">Cliente</th>
                            <th class="table-header">Items</th>
                            <th class="table-header">Total</th>
                            <th class="table-header">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            ['id'=>'#TRX-9081','client'=>'Carlos Pérez',  'items'=>3,'total'=>'$54.00',   'status'=>'Completada','st'=>'success'],
                            ['id'=>'#TRX-9080','client'=>'María Gómez',   'items'=>1,'total'=>'$12.50',   'status'=>'Completada','st'=>'success'],
                            ['id'=>'#TRX-9079','client'=>'Juan Minaya',   'items'=>6,'total'=>'$120.90',  'status'=>'Pendiente', 'st'=>'warning'],
                            ['id'=>'#TRX-9078','client'=>'Nadia Kusuma',  'items'=>2,'total'=>'$38.00',   'status'=>'Cancelada', 'st'=>'danger'],
                        ] as $row)
                        <tr>
                            <td class="table-cell font-bold font-mono text-xs" style="color: var(--text-main);">{{ $row['id'] }}</td>
                            <td class="table-cell" style="color: var(--text-secondary);">{{ $row['client'] }}</td>
                            <td class="table-cell" style="color: var(--text-tertiary);">{{ $row['items'] }} items</td>
                            <td class="table-cell font-bold" style="color: var(--text-main);">{{ $row['total'] }}</td>
                            <td class="table-cell">
                                <span class="badge badge-{{ $row['st'] }}">{{ $row['status'] }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Clientes Frecuentes --}}
        <div class="card-panel p-6 flex flex-col justify-between">
            <div>
                <h4 class="font-bold text-base mb-1" style="color: var(--text-main);">Clientes Frecuentes</h4>
                <p class="text-xs mb-5" style="color: var(--text-tertiary);">Mayores compradores del mes</p>

                <div class="space-y-4">
                    @foreach([
                        ['init'=>'CP','name'=>'Carlos Pérez', 'buys'=>12,'total'=>'$340.50','color'=>'var(--primary)'],
                        ['init'=>'MG','name'=>'María Gómez',  'buys'=>8, 'total'=>'$210.00','color'=>'#F2A900'],
                        ['init'=>'JM','name'=>'Juan Minaya',  'buys'=>5, 'total'=>'$185.20','color'=>'#A41D6A'],
                    ] as $c)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0"
                                 style="background-color: var(--primary-light); color: {{ $c['color'] }}; border: 2px solid rgba(10,126,165,0.15);">
                                {{ $c['init'] }}
                            </div>
                            <div>
                                <p class="text-sm font-bold" style="color: var(--text-main);">{{ $c['name'] }}</p>
                                <p class="text-xs" style="color: var(--text-tertiary);">{{ $c['buys'] }} compras</p>
                            </div>
                        </div>
                        <span class="text-sm font-bold font-mono" style="color: var(--text-main);">{{ $c['total'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-5 mt-5" style="border-top: 1px solid var(--border-light);">
                <button class="w-full py-2.5 rounded-xl text-xs font-bold transition-all btn-outline">
                    Ver ranking completo
                </button>
            </div>
        </div>

    </div>

</div>
@endsection
