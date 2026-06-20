@extends('layouts.app')

@section('title', 'Suscripción y Facturación')
@section('page-title', 'Suscripción')
@section('page-subtitle', 'Consulte su plan actual, registre comprobantes de pago de renovación o cambie de plan')

@section('content')
    {{-- Alerts --}}
    @if (session('status'))
        <div class="mb-4 p-4 rounded-xl text-sm font-medium"
             style="background-color: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: var(--success);">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Card 1: Estado del Plan --}}
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Plan Actual</h3>
            <p class="text-2xl font-bold text-brand-blue" style="color: var(--primary);">{{ ucfirst($company->subscription_plan) }}</p>
            <div class="mt-4 flex items-center justify-between text-xs border-t border-slate-100 dark:border-slate-800 pt-3">
                <span class="text-slate-400">Estado de cuenta:</span>
                @if($company->subscription_status === 'active')
                    <span class="font-bold text-emerald-500">Activa</span>
                @else
                    <span class="font-bold text-amber-500">{{ ucfirst(str_replace('_', ' ', $company->subscription_status)) }}</span>
                @endif
            </div>
        </div>

        {{-- Card 2: Vigencia --}}
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Fecha de Vencimiento</h3>
            <p class="text-2xl font-bold text-slate-700 dark:text-slate-300">
                {{ $company->subscription_expires_at ? $company->subscription_expires_at->format('d/m/Y') : 'Ilimitado / Sin Registrar' }}
            </p>
            <div class="mt-4 flex items-center justify-between text-xs border-t border-slate-100 dark:border-slate-800 pt-3">
                <span class="text-slate-400">Tiempo restante:</span>
                <span class="font-semibold text-slate-600 dark:text-slate-400">
                    @if($company->subscription_expires_at)
                        {{ max(0, now()->diffInDays($company->subscription_expires_at, false)) }} días
                    @else
                        Ilimitado
                    @endif
                </span>
            </div>
        </div>

        {{-- Card 3: Resumen de Capacidad --}}
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Límites de Vendedores</h3>
            @php
                $limit = $company->subscription_plan === 'basic' ? 2 : ($company->subscription_plan === 'standard' ? 10 : 'Ilimitado');
            @endphp
            <p class="text-2xl font-bold text-slate-700 dark:text-slate-300">{{ $limit }}</p>
            <div class="mt-4 flex items-center justify-between text-xs border-t border-slate-100 dark:border-slate-800 pt-3">
                <span class="text-slate-400">Plan actual:</span>
                <span class="font-semibold text-slate-600 dark:text-slate-400">{{ ucfirst($company->subscription_plan) }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Formulario para Registrar Pago / Cambio de Plan --}}
        <div class="lg:col-span-2 card-panel p-6">
            <h2 class="font-bold text-lg mb-2 text-slate-800 dark:text-slate-100">Registrar Pago o Cambio de Plan</h2>
            <p class="text-xs text-slate-400 mb-4">Ingrese los datos correspondientes para que el superadministrador los valide.</p>

            <form action="{{ route('settings.subscription.payment.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="plan" class="block text-xs font-semibold mb-1 text-slate-600 dark:text-slate-400">Plan Solicitado</label>
                        <select id="plan" name="plan" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-transparent rounded-lg text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-blue" required>
                            <option value="basic" {{ old('plan', $company->subscription_plan) === 'basic' ? 'selected' : '' }}>Plan Basic (1 Admin, 2 Vendedores)</option>
                            <option value="standard" {{ old('plan', $company->subscription_plan) === 'standard' ? 'selected' : '' }}>Plan Standard (2 Admins, 10 Vendedores)</option>
                        </select>
                    </div>

                    <div>
                        <label for="type" class="block text-xs font-semibold mb-1 text-slate-600 dark:text-slate-400">Tipo de Transacción</label>
                        <select id="type" name="type" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-transparent rounded-lg text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-blue" required>
                            <option value="renewal" {{ old('type') === 'renewal' ? 'selected' : '' }}>Renovación Mensual (Mantener Plan)</option>
                            <option value="upgrade" {{ old('type') === 'upgrade' ? 'selected' : '' }}>Cambio de Plan (Upgrade/Downgrade)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="bank_origin" class="block text-xs font-semibold mb-1 text-slate-600 dark:text-slate-400">Banco de Origen</label>
                        <input type="text" id="bank_origin" name="bank_origin" value="{{ old('bank_origin') }}"
                               class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-transparent rounded-lg text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-blue"
                               placeholder="Ej. Banco Guayaquil" required>
                    </div>

                    <div>
                        <label for="account_destination" class="block text-xs font-semibold mb-1 text-slate-600 dark:text-slate-400">Cuenta de Destino</label>
                        <select id="account_destination" name="account_destination" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-transparent rounded-lg text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-blue" required>
                            <option value="Banco Guayaquil - Ahorros #123456789">Banco Guayaquil - Ahorros #123456789</option>
                            <option value="Banco Pichincha - Corriente #987654321">Banco Pichincha - Corriente #987654321</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="payment_receipt" class="block text-xs font-semibold mb-1 text-slate-600 dark:text-slate-400">Comprobante de Pago (Imagen)</label>
                    <input type="file" id="payment_receipt" name="payment_receipt" accept="image/*"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-transparent rounded-lg text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-blue" required>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Registrar Pago
                    </button>
                </div>
            </form>
        </div>

        {{-- Cuentas Bancarias Info --}}
        <div class="card-panel p-6">
            <h2 class="font-bold text-lg mb-4 text-slate-800 dark:text-slate-100">Cuentas Autorizadas</h2>
            <div class="space-y-4 text-xs">
                <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900">
                    <span class="font-bold block text-sm">Banco Guayaquil</span>
                    <span class="opacity-80">Cuenta de Ahorros #123456789</span><br>
                    <span class="opacity-50">FlexDash S.A. | RUC: 0999999999001</span>
                </div>
                <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900">
                    <span class="font-bold block text-sm">Banco Pichincha</span>
                    <span class="opacity-80">Cuenta Corriente #987654321</span><br>
                    <span class="opacity-50">FlexDash S.A. | RUC: 0999999999001</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Historial de Pagos --}}
    <div class="card-panel overflow-hidden mt-6">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
            <h2 class="font-bold text-slate-800 dark:text-slate-100">Historial de Transacciones</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold text-xs uppercase">
                        <th class="px-6 py-4">Fecha</th>
                        <th class="px-6 py-4">Tipo</th>
                        <th class="px-6 py-4">Plan</th>
                        <th class="px-6 py-4">Banco de Origen</th>
                        <th class="px-6 py-4">Cuenta Destino</th>
                        <th class="px-6 py-4">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-600 dark:text-slate-400">
                                {{ $payment->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 uppercase font-bold text-xs text-slate-500">
                                {{ $payment->type }}
                            </td>
                            <td class="px-6 py-4 capitalize">{{ $payment->plan }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $payment->bank_origin }}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $payment->account_destination }}</td>
                            <td class="px-6 py-4">
                                @if($payment->status === 'approved')
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        Aprobado
                                    </span>
                                @elseif($payment->status === 'rejected')
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                        Rechazado
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                        Pendiente
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                No se registran pagos previos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
