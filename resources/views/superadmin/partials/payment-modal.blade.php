{{-- =========================================================
     Superadmin: Payment Detail Modal (Partial)
     Comunicación via Alpine store: $store.paymentModal.payment
     ========================================================= --}}
<div x-data="{ get payment() { return $store.paymentModal.payment } }"
     x-show="payment !== null"
     x-cloak
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
     @click.self="$store.paymentModal.payment = null"
     @keydown.escape.window="$store.paymentModal.payment = null"
     style="display: none;">

    <div class="relative max-w-4xl w-full bg-white dark:bg-slate-800 rounded-2xl shadow-2xl flex flex-col max-h-[90vh]"
         @click.stop>

        {{-- Header --}}
        <div class="flex-shrink-0 flex justify-between items-center p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-circle-info" style="color: var(--primary);"></i>
                Detalle de Pago y Comprobante
            </h3>
            <button @click="$store.paymentModal.payment = null"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors text-xl font-bold">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="overflow-y-auto p-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">

                {{-- Left: Payment Details --}}
                <div class="md:col-span-5 space-y-4">
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-xl border border-slate-200 dark:border-slate-700 space-y-4">

                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Empresa</span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="payment.company_name"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Plan</span>
                                <span class="text-xs font-semibold capitalize text-slate-800 dark:text-slate-200" x-text="payment.plan"></span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Tipo</span>
                                <span class="text-xs font-bold uppercase text-slate-500" x-text="payment.type"></span>
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Fecha de Envío</span>
                            <span class="text-xs text-slate-600 dark:text-slate-400" x-text="payment.formatted_date"></span>
                        </div>

                        <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Banco de Origen</span>
                            <span class="text-xs text-slate-700 dark:text-slate-300 font-semibold" x-text="payment.bank_origin"></span>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Cuenta de Destino</span>
                            <span class="text-[11px] text-slate-600 dark:text-slate-400 font-mono" x-text="payment.account_destination"></span>
                        </div>

                        <div class="border-t border-slate-200 dark:border-slate-700 pt-3 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Estado</span>
                            <template x-if="payment.status === 'approved'">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-500/20">Aprobado</span>
                            </template>
                            <template x-if="payment.status === 'rejected'">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400 border border-rose-500/20">Rechazado</span>
                            </template>
                            <template x-if="payment.status === 'pending'">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-500/20">Pendiente</span>
                            </template>
                        </div>

                        <template x-if="payment.status === 'rejected' && payment.rejection_reason">
                            <div class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-lg">
                                <span class="font-bold block uppercase tracking-wider text-[9px] text-rose-500 mb-1">Motivo de Rechazo:</span>
                                <span class="text-xs text-rose-600 dark:text-rose-400" x-text="payment.rejection_reason"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Actions for pending payments --}}
                    <div x-show="payment && payment.status === 'pending'">
                        <div class="flex items-center gap-3 w-full bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                            <form :action="payment ? payment.approve_url : ''" method="POST" class="flex-1">
                                @csrf
                                <input type="hidden" name="payment_id" :value="payment ? payment.id : ''">
                                <button type="submit" class="w-full py-2.5 px-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-xs transition-colors">
                                    <i class="fa-solid fa-check mr-1"></i> Aprobar Pago
                                </button>
                            </form>
                            <form :action="payment ? payment.reject_url : ''" method="POST" class="flex-1"
                                  onsubmit="const reason = prompt('Motivo de rechazo del pago:'); if (reason === null || reason.trim() === '') return false; this.reason.value = reason;">
                                @csrf
                                <input type="hidden" name="payment_id" :value="payment ? payment.id : ''">
                                <input type="hidden" name="reason" value="">
                                <button type="submit" class="w-full py-2.5 px-3 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-lg text-xs transition-colors">
                                    <i class="fa-solid fa-xmark mr-1"></i> Rechazar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Right: Receipt Image --}}
                <div class="md:col-span-7 flex flex-col items-center w-full">
                    <div class="w-full border border-slate-200 dark:border-slate-700/80 rounded-xl bg-slate-900/40 p-3 flex items-center justify-center min-h-[200px]">
                        <template x-if="payment.receipt_url">
                            <a :href="payment.receipt_url" target="_blank" title="Ver en tamaño completo"
                               class="relative group block w-full text-center">
                                <img :src="payment.receipt_url"
                                     alt="Comprobante de Pago"
                                     class="max-h-[55vh] rounded-lg object-contain shadow-md hover:opacity-95 transition-opacity inline-block">
                                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity rounded-lg">
                                    <span class="text-xs text-white bg-slate-900/80 px-3 py-1.5 rounded-full font-semibold border border-white/20">
                                        <i class="fa-solid fa-magnifying-glass-plus mr-1"></i> Expandir Imagen
                                    </span>
                                </div>
                            </a>
                        </template>
                        <template x-if="!payment.receipt_url">
                            <div class="flex flex-col items-center gap-2 text-slate-400 py-10">
                                <i class="fa-solid fa-file-circle-question text-4xl opacity-40"></i>
                                <span class="text-sm">Sin imagen disponible</span>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
