@extends('layouts.app')

@section('title', 'Registro de Cobro/Pago Masivo')
@section('page-title', 'Cobro/Pago Masivo de Documentos')
@section('page-subtitle', 'Liquide múltiples facturas pendientes para un mismo cliente o proveedor')

@section('content')
<div class="page-fade max-w-4xl mx-auto" x-data="batchPaymentHandler()">
    <div class="card-panel p-6 space-y-6">
        <div>
            <h2 class="text-base font-bold" style="color: var(--text-main);">Parámetros del Pago / Cobro</h2>
            <p class="text-xs" style="color: var(--text-tertiary);">Seleccione el partner y especifique el importe a distribuir.</p>
        </div>

        <form method="POST" action="{{ route('cashbox.batch-payment.store') }}" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="form-label">Tipo de Proceso</label>
                    <select name="partner_type" x-model="partnerType" @change="onPartnerChange()" class="input-solid" required>
                        <option value="cliente">Cobro a Cliente (Ventas)</option>
                        <option value="proveedor">Pago a Proveedor (Compras)</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Cliente / Proveedor</label>
                    <select name="partner_id" x-model="partnerId" @change="onPartnerChange()" class="input-solid" required>
                        <option value="">-- Seleccione un Partner --</option>
                        @foreach ($partners as $p)
                            {{-- Show client option if partnerType is cliente or both --}}
                            <template x-if="partnerType === 'cliente' && ('{{ $p->type }}' === 'cliente' || '{{ $p->type }}' === 'ambos')">
                                <option value="{{ $p->id }}">{{ $p->business_name }} ({{ $p->document_number }})</option>
                            </template>
                            {{-- Show provider option if partnerType is proveedor or both --}}
                            <template x-if="partnerType === 'proveedor' && ('{{ $p->type }}' === 'proveedor' || '{{ $p->type }}' === 'ambos')">
                                <option value="{{ $p->id }}">{{ $p->business_name }} ({{ $p->document_number }})</option>
                            </template>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Método de Pago</label>
                    <select name="payment_method_id" class="input-solid" required>
                        @foreach ($paymentMethods as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="form-label">Importe Total a Pagar</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-coins"></i>
                        <input type="number" step="0.01" name="amount" x-model.number="amount" class="input-solid" placeholder="0.00" min="0.01" required>
                    </div>
                </div>

                <div>
                    <label class="form-label">Fecha de Pago</label>
                    <input type="date" name="payment_date" class="input-solid" required value="{{ date('Y-m-d') }}">
                </div>

                <div>
                    <label class="form-label">Referencia / Operación</label>
                    <input type="text" name="reference" class="input-solid" placeholder="Nro Transferencia, Cheque, etc.">
                </div>
            </div>

            <div>
                <label class="form-label">Notas / Comentarios</label>
                <input type="text" name="notes" class="input-solid" placeholder="Notas adicionales de la transacción...">
            </div>

            {{-- Pending documents loading spinner or list --}}
            <div class="border-t pt-6 space-y-4" x-show="partnerId !== ''">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-wider" style="color: var(--text-main);">Documentos Pendientes Encontrados</h3>
                    <template x-if="loading">
                        <span class="text-xs font-semibold" style="color: var(--primary);">
                            <i class="fa-solid fa-spinner fa-spin"></i> Cargando...
                        </span>
                    </template>
                </div>

                <template x-if="!loading && documents.length === 0">
                    <div class="p-6 text-center border rounded-xl" style="color: var(--text-tertiary); background-color: var(--bg);">
                        No se encontraron facturas o comprobantes aprobados con saldo pendiente.
                    </div>
                </template>

                <template x-if="!loading && documents.length > 0">
                    <div class="space-y-4">
                        <div class="overflow-x-auto border rounded-xl">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="table-header w-12 text-center">
                                            <input type="checkbox" @change="toggleSelectAll($el.checked)" class="rounded">
                                        </th>
                                        <th class="table-header">Nro Documento</th>
                                        <th class="table-header">Fecha Emisión</th>
                                        <th class="table-header text-right">Total</th>
                                        <th class="table-header text-right">Saldo Pendiente</th>
                                        <th class="table-header text-right">Monto a Aplicar</th>
                                        <th class="table-header text-right">Nuevo Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="doc in documents" :key="doc.id">
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                            <td class="table-cell text-center">
                                                <input type="checkbox" name="document_ids[]" :value="doc.id" 
                                                    x-model="selectedIds" class="rounded">
                                            </td>
                                            <td class="table-cell font-bold" x-text="doc.number"></td>
                                            <td class="table-cell font-mono text-xs" x-text="doc.issue_date"></td>
                                            <td class="table-cell text-right font-mono" x-text="'S/ ' + parseFloat(doc.total).toFixed(2)"></td>
                                            <td class="table-cell text-right font-mono font-semibold" x-text="'S/ ' + parseFloat(doc.pending_balance).toFixed(2)"></td>
                                            
                                            {{-- Payment Distribution dynamic simulation --}}
                                            <td class="table-cell text-right font-mono font-bold text-green-600">
                                                S/ <span x-text="calculateAppliedAmount(doc.id).toFixed(2)"></span>
                                            </td>
                                            <td class="table-cell text-right font-mono" :class="calculateNewBalance(doc).toFixed(2) == 0 ? 'text-slate-400 dark:text-slate-500' : 'font-semibold text-sky-600'">
                                                S/ <span x-text="calculateNewBalance(doc).toFixed(2)"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- Distribution Summary Info --}}
                        <div class="flex flex-col md:flex-row justify-between p-4 rounded-xl border bg-slate-50 dark:bg-slate-800/40 text-xs gap-4">
                            <div>
                                <span style="color: var(--text-tertiary);">Total Seleccionado:</span>
                                <span class="font-bold text-sm block" style="color: var(--text-main);">
                                    S/ <span x-text="calculateSelectedTotal().toFixed(2)"></span>
                                </span>
                            </div>
                            <div>
                                <span style="color: var(--text-tertiary);">Importe a Distribuir:</span>
                                <span class="font-bold text-sm text-green-600 block">
                                    S/ <span x-text="amount ? amount.toFixed(2) : '0.00'"></span>
                                </span>
                            </div>
                            <div>
                                <span style="color: var(--text-tertiary);">Exceso de Pago (Sin aplicar):</span>
                                <span class="font-bold text-sm block" :class="calculateExcess() > 0 ? 'text-amber-500' : 'text-slate-400'">
                                    S/ <span x-text="calculateExcess().toFixed(2)"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex gap-3 justify-end border-t pt-6">
                <a href="{{ route('cashbox.index') }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary" :disabled="selectedIds.length === 0 || !amount || amount <= 0">
                    <i class="fa-solid fa-floppy-disk"></i> Registrar Transacción
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function batchPaymentHandler() {
        return {
            partnerType: 'cliente',
            partnerId: '',
            loading: false,
            amount: '',
            documents: [],
            selectedIds: [],

            onPartnerChange() {
                this.documents = [];
                this.selectedIds = [];
                if (this.partnerId === '') return;

                this.loading = true;
                fetch(`/cashbox/pending-docs/${this.partnerId}?mode=${this.partnerType}`)
                    .then(res => res.json())
                    .then(data => {
                        this.documents = data;
                        this.loading = false;
                    })
                    .catch(err => {
                        console.error('Error fetching pending documents', err);
                        this.loading = false;
                    });
            },

            toggleSelectAll(checked) {
                if (checked) {
                    this.selectedIds = this.documents.map(d => d.id.toString());
                } else {
                    this.selectedIds = [];
                }
            },

            calculateSelectedTotal() {
                return this.documents
                    .filter(d => this.selectedIds.includes(d.id.toString()))
                    .reduce((sum, d) => sum + parseFloat(d.pending_balance), 0);
            },

            calculateAppliedAmount(docId) {
                if (!this.selectedIds.includes(docId.toString())) return 0;
                
                // We distribute the amount sequentially (FIFO based on issue date order)
                // Filter the selected documents and order by ID (already returned sorted chronologically from server)
                let remaining = parseFloat(this.amount || 0);
                let applied = 0;

                for (let i = 0; i < this.documents.length; i++) {
                    let doc = this.documents[i];
                    if (this.selectedIds.includes(doc.id.toString())) {
                        let docBalance = parseFloat(doc.pending_balance);
                        let docApplied = Math.min(remaining, docBalance);
                        
                        if (doc.id === docId) {
                            applied = docApplied;
                            break;
                        }
                        
                        remaining -= docApplied;
                        if (remaining <= 0) break;
                    }
                }

                return applied;
            },

            calculateNewBalance(doc) {
                let applied = this.calculateAppliedAmount(doc.id);
                return parseFloat(doc.pending_balance) - applied;
            },

            calculateExcess() {
                let totalSelectedBalance = this.calculateSelectedTotal();
                let entered = parseFloat(this.amount || 0);
                return Math.max(0, entered - totalSelectedBalance);
            }
        }
    }
</script>
@endpush
