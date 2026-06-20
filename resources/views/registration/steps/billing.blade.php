{{-- Step 4 — Planes y Pago --}}
<div>
    <h2 class="text-xl font-bold mb-1" style="color: var(--text-main);">Planes y Pago</h2>
    <p class="text-sm mb-6" style="color: var(--text-tertiary);">Selecciona el plan que se adapte a tu negocio y registra los datos de tu depósito o transferencia bancaria.</p>

    <form action="{{ route('registration.billing') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf

        {{-- 1. Selección de Plan --}}
        <h3 class="text-sm font-bold uppercase tracking-wider mb-3 text-brand-blue" style="color: var(--primary);">1. Selecciona tu Plan</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            {{-- Plan Basic --}}
            <label for="plan_basic"
                   class="relative flex flex-col items-start gap-2 p-5 rounded-xl cursor-pointer transition-all"
                   style="border: 2px solid var(--border);"
                   onmouseover="this.style.borderColor='var(--primary)'"
                   onmouseout="if(!document.getElementById('plan_basic').checked) this.style.borderColor='var(--border)'">
                <input type="radio" id="plan_basic" name="subscription_plan" value="basic"
                       class="sr-only peer"
                       {{ old('subscription_plan', 'basic') === 'basic' ? 'checked' : '' }}
                       onchange="highlightPlanCard(this)">
                <span class="flex items-center justify-between w-full">
                    <span class="block text-sm font-bold" style="color: var(--text-main);">Plan Basic</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500">Popular</span>
                </span>
                <span class="block text-xs leading-relaxed" style="color: var(--text-tertiary);">
                    • 1 Administrador<br>
                    • Hasta 2 Vendedores
                </span>
                <span class="absolute top-3 right-3 hidden peer-checked:flex items-center justify-center w-5 h-5 rounded-full"
                      style="background-color: var(--primary);">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="white" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            </label>

            {{-- Plan Standard --}}
            <label for="plan_standard"
                   class="relative flex flex-col items-start gap-2 p-5 rounded-xl cursor-pointer transition-all"
                   style="border: 2px solid var(--border);"
                   onmouseover="this.style.borderColor='var(--primary)'"
                   onmouseout="if(!document.getElementById('plan_standard').checked) this.style.borderColor='var(--border)'">
                <input type="radio" id="plan_standard" name="subscription_plan" value="standard"
                       class="sr-only peer"
                       {{ old('subscription_plan') === 'standard' ? 'checked' : '' }}
                       onchange="highlightPlanCard(this)">
                <span class="flex items-center justify-between w-full">
                    <span class="block text-sm font-bold" style="color: var(--text-main);">Plan Standard</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-500">Negocios</span>
                </span>
                <span class="block text-xs leading-relaxed" style="color: var(--text-tertiary);">
                    • Hasta 2 Administradores<br>
                    • Hasta 10 Vendedores
                </span>
                <span class="absolute top-3 right-3 hidden peer-checked:flex items-center justify-center w-5 h-5 rounded-full"
                      style="background-color: var(--primary);">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="white" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            </label>
        </div>

        {{-- 2. Cuentas de Destino --}}
        <h3 class="text-sm font-bold uppercase tracking-wider mb-3 text-brand-blue" style="color: var(--primary);">2. Cuentas de Destino autorizadas</h3>
        <div class="p-4 rounded-xl mb-6 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
            <p class="font-medium text-slate-700 dark:text-slate-300">Realiza tu transferencia o depósito a cualquiera de las siguientes cuentas:</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                <div class="p-3 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    <span class="font-bold block">Banco Guayaquil</span>
                    <span>Cuenta de Ahorros #123456789</span><br>
                    <span class="opacity-60">FlexDash S.A. | RUC: 0999999999001</span>
                </div>
                <div class="p-3 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    <span class="font-bold block">Banco Pichincha</span>
                    <span>Cuenta Corriente #987654321</span><br>
                    <span class="opacity-60">FlexDash S.A. | RUC: 0999999999001</span>
                </div>
            </div>
        </div>

        {{-- 3. Información del Depósito --}}
        <h3 class="text-sm font-bold uppercase tracking-wider mb-3 text-brand-blue" style="color: var(--primary);">3. Datos del Pago</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="bank_origin" class="form-label">Banco de Origen</label>
                <input type="text" id="bank_origin" name="bank_origin" value="{{ old('bank_origin') }}"
                       placeholder="Ej. Banco de Guayaquil, Pichincha, etc."
                       class="input-solid" required>
            </div>

            <div>
                <label for="account_destination" class="form-label">Cuenta de Destino Seleccionada</label>
                <select id="account_destination" name="account_destination" class="input-solid" required>
                    <option value="" disabled {{ old('account_destination') ? '' : 'selected' }}>-- Seleccione una cuenta --</option>
                    <option value="Banco Guayaquil - Ahorros #123456789" {{ old('account_destination') === 'Banco Guayaquil - Ahorros #123456789' ? 'selected' : '' }}>Banco Guayaquil - Ahorros #123456789</option>
                    <option value="Banco Pichincha - Corriente #987654321" {{ old('account_destination') === 'Banco Pichincha - Corriente #987654321' ? 'selected' : '' }}>Banco Pichincha - Corriente #987654321</option>
                </select>
            </div>
        </div>

        <div class="mb-6">
            <label for="payment_receipt" class="form-label">Comprobante de Pago (Imagen/Captura)</label>
            <input type="file" id="payment_receipt" name="payment_receipt" accept="image/*"
                   class="input-solid w-full" style="padding-top: 8px; padding-bottom: 8px;" required>
            <p class="text-xs mt-1" style="color: var(--text-tertiary);">Formatos permitidos: JPEG, PNG. Tamaño máximo: 4MB.</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">
                Continuar →
            </button>
        </div>
    </form>
</div>

<script>
function highlightPlanCard(radio) {
    document.querySelectorAll('label[for^="plan_"]').forEach(label => {
        label.style.borderColor = 'var(--border)';
    });
    if (radio.id) {
        var label = document.querySelector('label[for="' + radio.id + '"]');
        if (label) label.style.borderColor = 'var(--primary)';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="subscription_plan"]:checked').forEach(function(r) { highlightPlanCard(r); });
});
</script>
