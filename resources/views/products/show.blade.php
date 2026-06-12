@extends('layouts.app')

@section('title', 'Detalle de Producto — ' . $product->code)
@section('page-title', 'Ficha del Producto')
@section('page-subtitle', 'Información detallada, Kardex y control de existencias')

@section('header-actions')
    <a href="{{ route('products.edit', $product) }}" class="btn-outline">
        <i class="fa-solid fa-pen-to-square"></i>
        Editar Producto
    </a>
@endsection

@section('content')
    <div class="mt-2 space-y-6 page-fade">
        <div class="mb-4">
            <a href="{{ route('products.index') }}" class="btn-outline">
                <i class="fa-solid fa-arrow-left"></i>
                Volver al listado
            </a>
        </div>

        {{-- Cuadrícula superior: Ficha Técnica --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Información Principal e Imagen --}}
            <div class="card-panel p-6 flex flex-col items-center text-center">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                    class="w-32 h-32 rounded-2xl object-cover border shadow-sm mb-4" style="border-color: var(--border);"
                    onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($product->name) }}&size=128&background=0a7ea5&color=ffffff'">

                <h2 class="text-xl font-bold" style="color: var(--text-main);">{{ $product->name }}</h2>
                <p class="text-sm font-mono mt-1" style="color: var(--text-tertiary);">{{ $product->code }}</p>
                <div class="mt-3">
                    @if ($product->is_active)
                        <span class="badge badge-success">Producto Activo</span>
                    @else
                        <span class="badge badge-danger">Producto Inactivo</span>
                    @endif
                </div>

                @if ($product->description)
                    <p class="text-xs mt-4 text-justify w-full border-t pt-4"
                        style="color: var(--text-secondary); border-color: var(--border-light);">
                        <strong>Descripción:</strong> {{ $product->description }}
                    </p>
                @endif
            </div>

            {{-- Existencias & Stock --}}
            <div class="card-panel p-6 space-y-6">
                <h3 class="text-xs font-bold uppercase tracking-wider border-b pb-2"
                    style="color: var(--text-main); border-color: var(--border-light);">Control de Existencias</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl border"
                        style="background-color: var(--bg); border-color: var(--border-light);">
                        <p class="text-xs font-medium" style="color: var(--text-tertiary);">Stock Actual</p>
                        <p class="text-2xl font-bold mt-1"
                            style="color: {{ $product->stock <= $product->minimum_stock ? 'var(--danger)' : 'var(--text-main)' }};">
                            {{ number_format($product->stock, 2) }}
                        </p>
                        <p class="text-xs mt-0.5" style="color: var(--text-tertiary);">{{ $product->unit }}</p>
                    </div>

                    <div class="p-4 rounded-xl border"
                        style="background-color: var(--bg); border-color: var(--border-light);">
                        <p class="text-xs font-medium" style="color: var(--text-tertiary);">Stock Mínimo</p>
                        <p class="text-2xl font-bold mt-1" style="color: var(--text-secondary);">
                            {{ number_format($product->minimum_stock, 2) }}
                        </p>
                        <p class="text-xs mt-0.5" style="color: var(--text-tertiary);">{{ $product->unit }}</p>
                    </div>
                </div>

                <div class="border-t pt-4 space-y-3" style="border-color: var(--border-light);">
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--text-tertiary);">Estado del Stock:</span>
                        @if ($product->stock <= 0)
                            <span class="font-bold" style="color: var(--danger);">Agotado</span>
                        @elseif($product->stock <= $product->minimum_stock)
                            <span class="font-bold" style="color: var(--warning);">Stock Crítico</span>
                        @else
                            <span class="font-bold" style="color: var(--success);">Suficiente</span>
                        @endif
                    </div>
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--text-tertiary);">Unidad Base:</span>
                        <span class="font-bold" style="color: var(--text-main);">{{ $product->unit }}</span>
                    </div>
                </div>
            </div>

            {{-- Información Financiera --}}
            <div class="card-panel p-6 space-y-6">
                <h3 class="text-xs font-bold uppercase tracking-wider border-b pb-2"
                    style="color: var(--text-main); border-color: var(--border-light);">Análisis Financiero</h3>

                <div class="space-y-4">
                    <div class="flex justify-between items-center text-sm">
                        <span style="color: var(--text-tertiary);">Costo Promedio Unitario:</span>
                        <span class="font-semibold" style="color: var(--text-secondary);">S/
                            {{ number_format($product->cost, 2) }}</span>
                    </div>

                    <div class="flex justify-between items-center text-sm">
                        <span style="color: var(--text-tertiary);">Precio de Venta Base:</span>
                        <span class="font-bold" style="color: var(--text-main);">S/
                            {{ number_format($product->price, 2) }}</span>
                    </div>

                    <div class="flex justify-between items-center text-sm">
                        <span style="color: var(--text-tertiary);">Impuesto ({{ $product->tax->name }}):</span>
                        <span class="font-semibold"
                            style="color: var(--text-secondary);">{{ number_format($product->tax->rate, 0) }}%</span>
                    </div>

                    @php
                        $margin = $product->price - $product->cost;
                        $marginPercent = $product->cost > 0 ? ($margin / $product->cost) * 100 : 0;
                    @endphp
                    <div class="border-t pt-4" style="border-color: var(--border-light);">
                        <div class="flex justify-between items-center">
                            <span class="text-sm" style="color: var(--text-tertiary);">Margen de Ganancia:</span>
                            <div class="text-right">
                                <span class="font-bold text-base" style="color: var(--success);">S/
                                    {{ number_format($margin, 2) }}</span>
                                <span class="text-xs block"
                                    style="color: var(--text-tertiary);">+{{ number_format($marginPercent, 1) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Movimientos del Kardex Recientes --}}
        <div class="card-panel overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between" style="border-color: var(--border-light);">
                <h3 class="font-bold text-sm" style="color: var(--text-main);">Historial del Kardex (Últimos 20 movimientos)
                </h3>
                <a href="{{ route('inventory.index', ['product_id' => $product->id]) }}" class="text-xs font-bold"
                    style="color: var(--primary);">Ver Kardex completo →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Fecha</th>
                            <th class="table-header">Tipo</th>
                            <th class="table-header text-right">Cantidad</th>
                            <th class="table-header text-right">Stock Anterior</th>
                            <th class="table-header text-right">Stock Posterior</th>
                            <th class="table-header text-right">Costo Mov.</th>
                            <th class="table-header">Doc. Referencia</th>
                            <th class="table-header">Usuario</th>
                            <th class="table-header">Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($product->inventoryMovements as $mov)
                            <tr>
                                <td class="table-cell whitespace-nowrap text-xs font-mono"
                                    style="color: var(--text-tertiary);">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                                <td class="table-cell">
                                    @switch($mov->type)
                                        @case('entrada')
                                            <span class="badge badge-success"><i class="fa-solid fa-arrow-down-long"></i>
                                                Entrada</span>
                                        @break

                                        @case('salida')
                                            <span class="badge badge-danger"><i class="fa-solid fa-arrow-up-long"></i>
                                                Salida</span>
                                        @break

                                        @case('ajuste')
                                            <span class="badge badge-warning"><i class="fa-solid fa-sliders"></i> Ajuste</span>
                                        @break

                                        @case('devolucion')
                                            <span class="badge badge-info"><i class="fa-solid fa-rotate-left"></i>
                                                Devolución</span>
                                        @break
                                    @endswitch
                                </td>
                                <td class="table-cell text-right font-bold font-mono"
                                    style="color: {{ in_array($mov->type, ['entrada', 'devolucion']) ? 'var(--success)' : ($mov->type === 'salida' ? 'var(--danger)' : 'var(--warning)') }};">
                                    {{ $mov->type === 'salida' ? '-' : '+' }}{{ number_format($mov->quantity, 2) }}
                                </td>
                                <td class="table-cell text-right font-mono text-xs" style="color: var(--text-tertiary);">
                                    {{ number_format($mov->stock_before, 2) }}</td>
                                <td class="table-cell text-right font-mono font-bold" style="color: var(--text-main);">
                                    {{ number_format($mov->stock_after, 2) }}</td>
                                <td class="table-cell text-right font-semibold font-mono"
                                    style="color: var(--text-secondary);">S/ {{ number_format($mov->unit_cost, 2) }}</td>
                                <td class="table-cell">
                                    @if ($mov->reference_type && $mov->reference_id)
                                        @php
                                            $route =
                                                $mov->reference_type === 'App\\Models\\Sale'
                                                    ? 'sales.show'
                                                    : 'purchases.show';
                                            $label =
                                                $mov->reference_type === 'App\\Models\\Sale' ? 'Venta #' : 'Compra #';
                                            $ref = $mov->reference;
                                            $number = $ref ? $ref->number : $mov->reference_id;
                                        @endphp
                                        <a href="{{ route($route, $mov->reference_id) }}"
                                            class="font-bold transition-colors text-sm" style="color: var(--primary);">
                                            {{ $label }}{{ $number }}
                                        </a>
                                    @else
                                        <span style="color: var(--text-tertiary);">Ajuste Manual</span>
                                    @endif
                                </td>
                                <td class="table-cell" style="color: var(--text-secondary);">
                                    {{ $mov->user->name ?? 'Sistema' }}</td>
                                <td class="table-cell text-xs max-w-xs truncate" style="color: var(--text-tertiary);"
                                    title="{{ $mov->notes }}">{{ $mov->notes }}</td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-8 text-center text-slate-400">
                                        Sin movimientos registrados en el Kardex para este producto.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endsection
