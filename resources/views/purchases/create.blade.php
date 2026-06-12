@extends('layouts.app')

@section('title', 'Nueva Compra')
@section('page-title', 'Registrar Orden de Compra / Factura de Proveedor')
@section('page-subtitle', 'Registra una compra en borrador. El stock ingresará al inventario cuando la apruebes.')

@section('content')
<div class="mt-2 page-fade">
    <div class="mb-4">
        <a href="{{ route('purchases.index') }}" class="btn-outline">
            <i class="fa-solid fa-arrow-left"></i>
            Volver al listado
        </a>
    </div>

    <form method="POST" action="{{ route('purchases.store') }}" id="purchase-form" class="space-y-6">
        @csrf

        {{-- Cabecera de la Compra --}}
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider border-b pb-3 mb-4" style="color: var(--text-main); border-color: var(--border-light);">Datos del Documento</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="number_display" class="form-label">Número Interno (Autogenerado)</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-barcode"></i>
                        <input type="text" id="number_display" value="{{ $nextNum }}" class="input-solid cursor-not-allowed font-mono font-bold" style="opacity: 0.6;" readonly>
                    </div>
                </div>

                <div>
                    <label for="partner_id" class="form-label">Proveedor <span class="text-red-500">*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-truck"></i>
                        <select name="partner_id" id="partner_id" class="input-solid" required>
                            <option value="">Seleccionar Proveedor...</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ old('partner_id') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->business_name }} ({{ $partner->document_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="supplier_invoice" class="form-label">Nro Factura del Proveedor</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <input type="text" name="supplier_invoice" id="supplier_invoice" value="{{ old('supplier_invoice') }}" class="input-solid font-mono font-bold" placeholder="Ej: F002-9218">
                    </div>
                </div>

                <div>
                    <label for="issue_date" class="form-label">Fecha Emisión <span class="text-red-500">*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-calendar"></i>
                        <input type="date" name="issue_date" id="issue_date" value="{{ old('issue_date', now()->format('Y-m-d')) }}" class="input-solid" required>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                <div>
                    <label for="due_date" class="form-label">Fecha Vencimiento</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-calendar-check"></i>
                        <input type="date" name="due_date" id="due_date" value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}" class="input-solid">
                    </div>
                </div>

                <div>
                    <label for="tax_id" class="form-label">Impuesto Aplicado <span class="text-red-500">*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-percent"></i>
                        <select name="tax_id" id="tax_id" class="input-solid" required>
                            @foreach($taxes as $tax)
                                <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" {{ old('tax_id') == $tax->id ? 'selected' : '' }}>
                                    {{ $tax->name }} ({{ number_format($tax->rate, 0) }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label for="discount" class="form-label">Descuento Global (S/)</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-tags"></i>
                        <input type="number" name="discount" id="discount" step="0.01" min="0" value="{{ old('discount', '0.00') }}" class="input-solid">
                    </div>
                </div>
                <div>
                    <label for="notes" class="form-label">Notas / Observaciones</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-comment"></i>
                        <input type="text" name="notes" id="notes" value="{{ old('notes') }}" class="input-solid" placeholder="Ej: Pago a 30 días, entrega en almacén principal, etc.">
                    </div>
                </div>
            </div>
        </div>

        {{-- Detalle de la Compra (Líneas de Producto) --}}
        <div class="card-panel overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between" style="border-color: var(--border-light);">
                <h3 class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-main);">Artículos Comprados</h3>
                <button type="button" id="add-row-btn" class="btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Agregar Producto
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table-custom" id="items-table">
                    <thead>
                        <tr>
                            <th class="table-header w-4/12">Producto</th>
                            <th class="table-header text-right w-1/12">Stock Actual</th>
                            <th class="table-header text-right w-2/12">Cantidad</th>
                            <th class="table-header text-right w-2/12">Costo Unitario Factura</th>
                            <th class="table-header text-right w-2/12">Desc. Línea (S/)</th>
                            <th class="table-header text-right w-1/12">Subtotal</th>
                            <th class="table-header text-center w-1/12">Quitar</th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                        {{-- Inserción dinámica por JS --}}
                    </tbody>
                </table>
            </div>

            {{-- Resumen de la Compra --}}
            <div class="border-t p-6 flex justify-end" style="border-color: var(--border-light); background-color: var(--bg);">
                <div class="w-80 space-y-3 text-sm" style="color: var(--text-secondary);">
                    <div class="flex justify-between">
                        <span>Subtotal Compra:</span>
                        <span class="font-semibold" id="summary-subtotal" style="color: var(--text-main);">S/ 0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Impuesto (<span id="summary-tax-name">IGV 18%</span>):</span>
                        <span class="font-semibold" id="summary-tax" style="color: var(--text-main);">S/ 0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Descuento global:</span>
                        <span class="font-semibold" id="summary-discount" style="color: var(--danger);">-S/ 0.00</span>
                    </div>
                    <div class="flex justify-between border-t pt-3 text-base font-bold" style="border-color: var(--border-light);">
                        <span style="color: var(--text-main);">Total Compra (CxP):</span>
                        <span id="summary-total" style="color: var(--primary);">S/ 0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('purchases.index') }}" class="btn-outline">Cancelar</a>
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                Crear Compra (Borrador)
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const products = @json($products);
    let rowIndex = 0;

    document.addEventListener('DOMContentLoaded', function() {
        const addRowBtn = document.getElementById('add-row-btn');
        const itemsBody = document.getElementById('items-body');
        const taxSelect = document.getElementById('tax_id');
        const discountInput = document.getElementById('discount');

        // Agregar fila inicial
        addRow();

        addRowBtn.addEventListener('click', addRow);
        taxSelect.addEventListener('change', calculateTotals);
        discountInput.addEventListener('input', calculateTotals);

        function addRow() {
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.dataset.index = rowIndex;

            let productOptions = '<option value="">Seleccionar artículo...</option>';
            products.forEach(p => {
                productOptions += `<option value="${p.id}">${p.code} - ${p.name}</option>`;
            });

            tr.innerHTML = `
                <td class="table-cell">
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-box"></i>
                        <select name="items[${rowIndex}][product_id]" class="input-solid product-select" required>
                            ${productOptions}
                        </select>
                    </div>
                </td>
                <td class="table-cell text-right font-mono font-bold product-stock" style="color: var(--text-tertiary);">0.00</td>
                <td class="table-cell">
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-cubes"></i>
                        <input type="number" name="items[${rowIndex}][quantity]" class="input-solid text-right quantity-input" step="0.01" min="0.01" value="1.00" required disabled>
                    </div>
                </td>
                <td class="table-cell">
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-money-bill-1-wave"></i>
                        <input type="number" name="items[${rowIndex}][unit_cost]" class="input-solid text-right cost-input" step="0.01" min="0" value="0.00" required disabled>
                    </div>
                </td>
                <td class="table-cell">
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-tag"></i>
                        <input type="number" name="items[${rowIndex}][discount]" class="input-solid text-right discount-input" step="0.01" min="0" value="0.00" disabled>
                    </div>
                </td>
                <td class="table-cell text-right font-bold font-mono line-subtotal" style="color: var(--text-main);">S/ 0.00</td>
                <td class="table-cell text-center">
                    <button type="button" class="btn-icon remove-row-btn" style="color: var(--danger);">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </td>
            `;

            itemsBody.appendChild(tr);

            const select = tr.querySelector('.product-select');
            const qtyInput = tr.querySelector('.quantity-input');
            const costInput = tr.querySelector('.cost-input');
            const discInput = tr.querySelector('.discount-input');
            const removeBtn = tr.querySelector('.remove-row-btn');

            select.addEventListener('change', function() {
                const prodId = this.value;
                const prod = products.find(p => p.id == prodId);

                if (prod) {
                    tr.querySelector('.product-stock').textContent = parseFloat(prod.stock).toFixed(2) + ' ' + prod.unit;
                    costInput.value = parseFloat(prod.cost).toFixed(2); // Se sugiere el costo promedio actual del catálogo
                    qtyInput.value = "1.00";
                    discInput.value = "0.00";

                    // Habilitar campos
                    qtyInput.disabled = false;
                    costInput.disabled = false;
                    discInput.disabled = false;
                } else {
                    tr.querySelector('.product-stock').textContent = '0.00';
                    costInput.value = '0.00';
                    qtyInput.value = '1.00';
                    discInput.value = '0.00';

                    qtyInput.disabled = true;
                    costInput.disabled = true;
                    discInput.disabled = true;
                }
                calculateRowSubtotal(tr);
            });

            qtyInput.addEventListener('input', () => calculateRowSubtotal(tr));
            costInput.addEventListener('input', () => calculateRowSubtotal(tr));
            discInput.addEventListener('input', () => calculateRowSubtotal(tr));

            removeBtn.addEventListener('click', function() {
                if (document.querySelectorAll('.item-row').length > 1) {
                    tr.remove();
                    calculateTotals();
                } else {
                    alert('Debe tener al menos un artículo en la compra.');
                }
            });

            rowIndex++;
        }

        function calculateRowSubtotal(tr) {
            const qty = parseFloat(tr.querySelector('.quantity-input').value) || 0;
            const cost = parseFloat(tr.querySelector('.cost-input').value) || 0;
            const disc = parseFloat(tr.querySelector('.discount-input').value) || 0;

            const subtotal = Math.max(0, (qty * cost) - disc);
            tr.querySelector('.line-subtotal').textContent = 'S/ ' + subtotal.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            tr.dataset.subtotal = subtotal;

            calculateTotals();
        }

        function calculateTotals() {
            let netSubtotal = 0;
            document.querySelectorAll('.item-row').forEach(tr => {
                netSubtotal += parseFloat(tr.dataset.subtotal) || 0;
            });

            // Impuestos
            const selectedOpt = taxSelect.options[taxSelect.selectedIndex];
            const taxRate = parseFloat(selectedOpt.dataset.rate) || 0;
            const taxName = selectedOpt.textContent.trim();

            const taxVal = parseFloat((netSubtotal * (taxRate / 100)).toFixed(2));
            const discVal = parseFloat(discountInput.value) || 0;
            const totalVal = Math.max(0, netSubtotal + taxVal - discVal);

            // Actualizar resumen
            document.getElementById('summary-subtotal').textContent = 'S/ ' + netSubtotal.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('summary-tax-name').textContent = taxName;
            document.getElementById('summary-tax').textContent = 'S/ ' + taxVal.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('summary-discount').textContent = '-S/ ' + discVal.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('summary-total').textContent = 'S/ ' + totalVal.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });
</script>
@endpush
@endsection
