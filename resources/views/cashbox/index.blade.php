@extends('layouts.app')

@section('title', 'Caja Chica')
@section('page-title', 'Caja Chica')
@section('page-subtitle', 'Administración y control de movimientos contables cotidianos')

@section('header-actions')
    @if ($activeBox)
        <a href="{{ route('cashbox.batch-payment') }}" class="btn-secondary">
            <i class="fa-solid fa-receipt"></i> Cobro/Pago Masivo
        </a>
        <button x-data @click="$dispatch('open-close-modal')" class="btn-primary">
            <i class="fa-solid fa-lock"></i> Cierre de Caja
        </button>
    @endif
@endsection

@section('content')
<div class="page-fade space-y-6">

    @if (!$activeBox)
        {{-- Caja Cerrada State --}}
        <div class="max-w-md mx-auto my-12">
            <div class="card-panel p-8 space-y-6">
                <div class="text-center space-y-2">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto text-slate-400 dark:text-slate-500">
                        <i class="fa-solid fa-cash-register text-3xl"></i>
                    </div>
                    <h2 class="text-xl font-bold" style="color: var(--text-main);">Caja Chica Cerrada</h2>
                    <p class="text-sm" style="color: var(--text-tertiary);">Abra una nueva sesión de caja para registrar ingresos, egresos y pagos de facturas.</p>
                </div>

                <form method="POST" action="{{ route('cashbox.open') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="opening_balance" class="form-label">Saldo Inicial de Apertura</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-coins"></i>
                            <input type="number" step="0.01" name="opening_balance" id="opening_balance" 
                                class="input-solid" placeholder="0.00" min="0" required value="{{ old('opening_balance', '0.00') }}">
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="form-label">Notas de Apertura</label>
                        <textarea name="notes" id="notes" class="input-solid h-20 resize-none" placeholder="Ej. Turno mañana - Responsable">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center py-2.5">
                        <i class="fa-solid fa-key"></i> Abrir Caja Chica
                    </button>
                </form>
            </div>
        </div>
    @else
        {{-- Caja Abierta State --}}
        
        {{-- KPI Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="kpi-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-slate-100 dark:bg-slate-800" style="color: var(--text-secondary);">
                    <i class="fa-solid fa-door-open"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-tertiary);">Saldo Inicial</p>
                    <h3 class="text-lg font-bold font-mono" style="color: var(--text-main);">S/ {{ number_format($openingBalance, 2) }}</h3>
                </div>
            </div>

            <div class="kpi-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-green-50 dark:bg-green-950/20 text-green-600">
                    <i class="fa-solid fa-arrow-down-long"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-tertiary);">Ingresos del Día</p>
                    <h3 class="text-lg font-bold font-mono text-green-600">S/ {{ number_format($inflows, 2) }}</h3>
                </div>
            </div>

            <div class="kpi-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-red-50 dark:bg-red-950/20 text-red-600">
                    <i class="fa-solid fa-arrow-up-long"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-tertiary);">Egresos del Día</p>
                    <h3 class="text-lg font-bold font-mono text-red-600">S/ {{ number_format($outflows, 2) }}</h3>
                </div>
            </div>

            <div class="kpi-card flex items-center gap-4" style="border-left: 4px solid var(--primary);">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-sky-50 dark:bg-sky-950/20" style="color: var(--primary);">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-tertiary);">Saldo Esperado</p>
                    <h3 class="text-lg font-bold font-mono" style="color: var(--primary);">S/ {{ number_format($expectedBalance, 2) }}</h3>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Manual adjustments form --}}
            <div class="card-panel p-6 space-y-6 h-fit">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider" style="color: var(--text-main);">Ajuste Manual / Movimiento</h3>
                    <p class="text-xs" style="color: var(--text-tertiary);">Registre un ingreso o egreso directo en el flujo de caja chica.</p>
                </div>

                <form method="POST" action="{{ route('cashbox.adjust') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="type" class="form-label">Tipo de Movimiento</label>
                        <select name="type" id="type" class="input-solid" required>
                            <option value="egreso">Egreso (Salida de dinero)</option>
                            <option value="ingreso">Ingreso (Entrada de dinero)</option>
                        </select>
                    </div>

                    <div>
                        <label for="amount" class="form-label">Monto</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-coins"></i>
                            <input type="number" step="0.01" name="amount" id="amount" class="input-solid" placeholder="0.00" min="0.01" required value="{{ old('amount') }}">
                        </div>
                    </div>

                    <div>
                        <label for="concept" class="form-label">Concepto / Detalle</label>
                        <input type="text" name="concept" id="concept" class="input-solid" placeholder="Ej. Almuerzo del personal, pasajes..." required value="{{ old('concept') }}">
                    </div>

                    <button type="submit" class="btn-outline w-full justify-center py-2.5">
                        <i class="fa-solid fa-plus"></i> Registrar Movimiento
                    </button>
                </form>
            </div>

            {{-- Transactions Ledger Table --}}
            <div class="lg:col-span-2 card-panel overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-6 py-4 border-b" style="border-color: var(--border-light);">
                        <h3 class="text-sm font-bold uppercase tracking-wider" style="color: var(--text-main);">Historial de Movimientos de la Caja Activa</h3>
                    </div>
                    @if ($transactions->isEmpty())
                        <div class="p-8 text-center" style="color: var(--text-tertiary);">
                            No hay movimientos registrados en esta sesión.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="table-header">Fecha/Hora</th>
                                        <th class="table-header">Concepto</th>
                                        <th class="table-header">Usuario</th>
                                        <th class="table-header text-center">Tipo</th>
                                        <th class="table-header text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transactions as $tx)
                                        <tr>
                                            <td class="table-cell font-mono text-xs">
                                                {{ $tx->created_at->format('d/m H:i') }}
                                            </td>
                                            <td class="table-cell font-medium">
                                                {{ $tx->concept }}
                                                @if ($tx->payment_id)
                                                    <span class="block text-xs font-mono" style="color: var(--text-tertiary);">Pago Ref #{{ $tx->payment_id }}</span>
                                                @endif
                                            </td>
                                            <td class="table-cell text-xs">
                                                {{ $tx->user->name ?? 'Usuario' }}
                                            </td>
                                            <td class="table-cell text-center">
                                                <span class="badge {{ $tx->type === 'ingreso' ? 'badge-success' : 'badge-danger' }} uppercase">
                                                    {{ $tx->type }}
                                                </span>
                                            </td>
                                            <td class="table-cell text-right font-bold font-mono {{ $tx->type === 'ingreso' ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $tx->type === 'ingreso' ? '+' : '-' }} S/ {{ number_format($tx->amount, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="p-4 border-t" style="border-color: var(--border-light);">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>

        {{-- Cierre de Caja Modal using AlpineJS --}}
        <div x-data="{ open: false }" 
             @open-close-modal.window="open = true"
             x-show="open" 
             class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto" 
             style="display: none;">
            
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="open = false"></div>

            {{-- Modal Box --}}
            <div class="card-panel w-full max-w-md p-6 space-y-6 z-10 mx-4 relative page-fade">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold" style="color: var(--text-main);">Cierre y Reconciliación de Caja</h3>
                    <button class="btn-icon" @click="open = false">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl space-y-2 border" style="border-color: var(--border-light);">
                    <div class="flex justify-between text-xs">
                        <span style="color: var(--text-tertiary);">Saldo Inicial:</span>
                        <span class="font-mono" style="color: var(--text-main);">S/ {{ number_format($openingBalance, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span style="color: var(--text-tertiary);">Total Ingresos (+):</span>
                        <span class="font-mono text-green-600">S/ {{ number_format($inflows, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span style="color: var(--text-tertiary);">Total Egresos (-):</span>
                        <span class="font-mono text-red-600">S/ {{ number_format($outflows, 2) }}</span>
                    </div>
                    <hr style="border-color: var(--border-light);">
                    <div class="flex justify-between text-sm font-bold">
                        <span style="color: var(--text-main);">Saldo Esperado en Caja:</span>
                        <span class="font-mono text-sky-600">S/ {{ number_format($expectedBalance, 2) }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('cashbox.close') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="actual_closing_balance" class="form-label">Efectivo Real Contado</label>
                        <div class="input-icon-wrapper" x-data="{ counted: '' }">
                            <i class="fa-solid fa-cash-register"></i>
                            <input type="number" step="0.01" name="actual_closing_balance" id="actual_closing_balance" 
                                class="input-solid" placeholder="0.00" min="0" required x-model="counted">
                            
                            {{-- Dynamic difference indicator --}}
                            <template x-if="counted !== ''">
                                <div class="mt-2 text-xs font-semibold flex justify-between">
                                    <span style="color: var(--text-tertiary);">Diferencia calculada:</span>
                                    <span :class="parseFloat(counted) - {{ $expectedBalance }} >= 0 ? 'text-green-600' : 'text-red-600'">
                                        S/ <span x-text="(parseFloat(counted) - {{ $expectedBalance }}).toFixed(2)"></span>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label for="close_notes" class="form-label">Notas de Cierre</label>
                        <textarea name="notes" id="close_notes" class="input-solid h-20 resize-none" placeholder="Indique alguna observación sobre el cuadre de caja..."></textarea>
                    </div>

                    <div class="flex gap-3 justify-end pt-2">
                        <button type="button" class="btn-secondary" @click="open = false">Cancelar</button>
                        <button type="submit" class="btn-danger">Cerrar Caja</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
