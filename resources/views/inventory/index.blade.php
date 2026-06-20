@extends('layouts.app')

@section('title', 'Kardex de Inventario')
@section('page-title', 'Kardex General de Inventario')
@section('page-subtitle', 'Consulta los movimientos de entrada, salida y realiza ajustes de inventario')

@section('header-actions')
    <button onclick="openAdjustModal()" class="btn-primary">
        <i class="fa-solid fa-sliders"></i>
        Ajuste Manual de Stock
    </button>
@endsection

@section('content')
<div class="mt-2 space-y-5 page-fade">

    {{-- Filtros --}}
    <div class="card-panel p-6">
        <form method="GET" action="{{ route('inventory.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label for="product_id" class="form-label">Filtrar por Producto</label>
                <select name="product_id" id="product_id" class="form-input">
                    <option value="">Todos los productos</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}" {{ $productId == $prod->id ? 'selected' : '' }}>
                            [{{ $prod->code }}] {{ $prod->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="type" class="form-label">Tipo Movimiento</label>
                <select name="type" id="type" class="form-input">
                    <option value="">Todos los tipos</option>
                    <option value="entrada"    {{ $type === 'entrada'    ? 'selected' : '' }}>Entrada</option>
                    <option value="salida"     {{ $type === 'salida'     ? 'selected' : '' }}>Salida</option>
                    <option value="ajuste"     {{ $type === 'ajuste'     ? 'selected' : '' }}>Ajuste</option>
                    <option value="devolucion" {{ $type === 'devolucion' ? 'selected' : '' }}>Devolución</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fa-solid fa-filter"></i> Filtrar Kardex
                </button>
                <a href="{{ route('inventory.index') }}" class="btn-secondary justify-center">
                    <i class="fa-solid fa-xmark"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Tabla de Kardex --}}
    <div class="card-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full" style="border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr>
                        <th class="table-header">Fecha / Hora</th>
                        <th class="table-header">Producto</th>
                        <th class="table-header">Tipo</th>
                        <th class="table-header text-right">Cantidad</th>
                        <th class="table-header text-right">Stock Anterior</th>
                        <th class="table-header text-right">Stock Posterior</th>
                        <th class="table-header text-right">Costo Unit.</th>
                        <th class="table-header">Referencia</th>
                        <th class="table-header">Operador</th>
                        <th class="table-header">Detalle / Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $mov)
                        <tr style="transition: background-color 0.15s;"
                            onmouseover="this.style.backgroundColor='var(--primary-light)'"
                            onmouseout="this.style.backgroundColor=''">
                            <td class="table-cell whitespace-nowrap font-medium" style="color: var(--text-tertiary);">
                                {{ $mov->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="table-cell">
                                <a href="{{ route('products.show', $mov->product_id) }}"
                                   class="font-bold transition-colors"
                                   style="color: var(--text-main);"
                                   onmouseover="this.style.color='var(--primary)'"
                                   onmouseout="this.style.color='var(--text-main)'">
                                    {{ $mov->product->name }}
                                </a>
                                <span class="block text-xs font-mono mt-0.5" style="color: var(--text-tertiary);">
                                    {{ $mov->product->code }}
                                </span>
                            </td>
                            <td class="table-cell">
                                @switch($mov->type)
                                    @case('entrada')    <span class="badge badge-success">Entrada</span>    @break
                                    @case('salida')     <span class="badge badge-danger">Salida</span>      @break
                                    @case('ajuste')     <span class="badge badge-warning">Ajuste</span>     @break
                                    @case('devolucion') <span class="badge badge-info">Devolución</span>    @break
                                @endswitch
                            </td>
                            <td class="table-cell text-right font-bold font-mono"
                                style="color: {{ in_array($mov->type, ['entrada', 'devolucion']) ? 'var(--success)' : ($mov->type === 'salida' ? 'var(--danger)' : 'var(--warning)') }};">
                                {{ $mov->type === 'salida' ? '-' : '+' }}{{ number_format($mov->quantity, 2) }}
                            </td>
                            <td class="table-cell text-right font-mono" style="color: var(--text-tertiary);">
                                {{ number_format($mov->stock_before, 2) }}
                            </td>
                            <td class="table-cell text-right font-mono font-bold" style="color: var(--text-main);">
                                {{ number_format($mov->stock_after, 2) }}
                            </td>
                            <td class="table-cell text-right font-medium" style="color: var(--text-secondary);">
                                S/ {{ number_format($mov->unit_cost, 2) }}
                            </td>
                            <td class="table-cell">
                                @if($mov->reference_type && $mov->reference_id)
                                    @php
                                        $route = $mov->reference_type === 'App\\Models\\Sale' ? 'sales.show' : 'purchases.show';
                                        $label = $mov->reference_type === 'App\\Models\\Sale' ? 'Venta #' : 'Compra #';
                                    @endphp
                                    <a href="{{ route($route, $mov->reference_id) }}"
                                       class="font-bold text-sm transition-colors"
                                       style="color: var(--primary);">
                                        {{ $label }}{{ $mov->reference_id }}
                                    </a>
                                @else
                                    <span style="color: var(--text-tertiary);">Ajuste Manual</span>
                                @endif
                            </td>
                            <td class="table-cell" style="color: var(--text-secondary);">
                                {{ $mov->user->name ?? 'Sistema' }}
                            </td>
                            <td class="table-cell text-xs max-w-xs truncate" style="color: var(--text-tertiary);"
                                title="{{ $mov->notes }}">
                                {{ $mov->notes }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                         style="background-color: var(--primary-light);">
                                        <i class="fa-solid fa-warehouse text-xl" style="color: var(--primary);"></i>
                                    </div>
                                    <p class="text-sm font-medium" style="color: var(--text-tertiary);">
                                        No se encontraron registros de Kardex con los filtros aplicados.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
            <div class="px-6 py-4" style="border-top: 1px solid var(--border-light); background-color: var(--bg);">
                {{ $movements->links() }}
            </div>
        @endif
    </div>

</div>

{{-- MODAL AJUSTE MANUAL --}}
<div id="adjust-modal"
     class="fixed inset-0 hidden flex items-center justify-center z-50"
     style="background-color: rgba(13,30,54,0.65); backdrop-filter: blur(4px);"
     onclick="if(event.target===this) closeAdjustModal()">
    <div class="card-panel w-full max-w-md overflow-hidden"
         style="border-radius: 20px; padding: 0;">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-5"
             style="border-bottom: 1px solid var(--border-light);">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background-color: var(--cta-light, rgba(227,82,5,0.10)); color: var(--cta);">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <h3 class="text-base font-bold" style="color: var(--text-main);">Ajuste Manual de Inventario</h3>
            </div>
            <button onclick="closeAdjustModal()" class="btn-icon">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Modal Body --}}
        <form method="POST" action="{{ route('inventory.adjust') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label for="modal_product_id" class="form-label">Producto a Ajustar</label>
                <select name="product_id" id="modal_product_id" class="form-input" required onchange="updateCurrentStockDisplay()">
                    <option value="">Seleccionar producto...</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}" data-stock="{{ $prod->stock }}" data-unit="{{ $prod->unit }}">
                            [{{ $prod->code }}] {{ $prod->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs mt-1" style="color: var(--text-tertiary);">
                    Stock actual en sistema:
                    <span id="current-stock-span" class="font-bold" style="color: var(--text-main);">0.00</span>
                </p>
            </div>

            <div>
                <label for="new_stock" class="form-label">Nuevo Stock Físico (Cantidad Real)</label>
                <input type="number" name="new_stock" id="new_stock" step="0.01" min="0"
                       class="form-input font-bold" required placeholder="0.00">
                <p class="text-xs mt-1" style="color: var(--text-tertiary);">
                    El sistema creará un movimiento de ajuste positivo o negativo automáticamente.
                </p>
            </div>

            <div>
                <label for="notes" class="form-label">Motivo del Ajuste (Notas)</label>
                <textarea name="notes" id="notes" rows="3" class="form-input"
                          placeholder="Ej: Ajuste por inventario anual, pérdida de material, etc." required></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-2" style="border-top: 1px solid var(--border-light); padding-top: 16px; margin-top: 4px;">
                <button type="button" onclick="closeAdjustModal()" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-check"></i> Registrar Ajuste
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openAdjustModal() {
        document.getElementById('adjust-modal').classList.remove('hidden');
    }
    function closeAdjustModal() {
        document.getElementById('adjust-modal').classList.add('hidden');
    }
    function updateCurrentStockDisplay() {
        const select = document.getElementById('modal_product_id');
        const selectedOpt = select.options[select.selectedIndex];
        if (selectedOpt && selectedOpt.value) {
            const stock = parseFloat(selectedOpt.dataset.stock) || 0;
            const unit = selectedOpt.dataset.unit || '';
            document.getElementById('current-stock-span').textContent = stock.toFixed(2) + ' ' + unit;
        } else {
            document.getElementById('current-stock-span').textContent = '0.00';
        }
    }
</script>
@endpush
