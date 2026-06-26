{{-- Step 4 — Planes y Pago --}}
@php
    $plans = $plans ?? \App\Models\Plan::where('is_active', true)->orderBy('price')->get();
    $planFeatures = [
        'basic'    => ['1 Administrador', 'Hasta 2 Vendedores', '1 Local', 'Facturación básica'],
        'standard' => ['Hasta 2 Administradores', 'Hasta 10 Vendedores', 'Hasta 3 Locales', 'Facturación electrónica'],
        'premium'  => ['Administradores ilimitados', 'Vendedores ilimitados', 'Locales ilimitados', 'Facturación electrónica + soporte'],
    ];
    $discountMap = [3 => 5, 6 => 10, 12 => 15, 24 => 20, 36 => 25];
    $plansJson = $plans->map(fn ($p) => [
        'code'  => $p->code,
        'name'  => $p->name,
        'price' => (float) $p->price,
    ])->values()->toJson();
@endphp

<div>
    <h2 class="text-xl font-bold mb-1" style="color: var(--text-main);">Planes y Pago</h2>
    <p class="text-sm mb-6" style="color: var(--text-tertiary);">Selecciona tu plan, el periodo de contratación y registra los datos de tu depósito o transferencia.</p>

    <form action="{{ route('registration.billing') }}" method="POST" enctype="multipart/form-data" novalidate id="billing-form">
        @csrf

        {{-- Period selector --}}
        <h3 class="text-sm font-bold uppercase tracking-wider mb-3" style="color: var(--primary);">1. Periodo de Contratación</h3>
        <div class="flex flex-wrap gap-2 mb-6" id="duration-selector">
            @foreach ([1, 3, 6, 12, 24, 36] as $months)
                <button type="button"
                        data-months="{{ $months }}"
                        class="duration-btn px-4 py-2 rounded-lg text-sm font-bold transition-all border-2"
                        style="border-color: var(--border); color: var(--text-secondary);">
                    {{ $months }} {{ $months === 1 ? 'mes' : 'meses' }}
                </button>
            @endforeach
        </div>
        <input type="hidden" name="subscription_duration_months" id="subscription_duration_months"
               value="{{ old('subscription_duration_months', 12) }}">
        <input type="hidden" name="subscription_amount" id="subscription_amount"
               value="{{ old('subscription_amount', 0) }}">
        <input type="hidden" name="subscription_discount_percentage" id="subscription_discount_percentage"
               value="{{ old('subscription_discount_percentage', 15) }}">

        {{-- Plan cards --}}
        <h3 class="text-sm font-bold uppercase tracking-wider mb-3" style="color: var(--primary);">2. Selecciona tu Plan</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6" id="plan-cards">
            @foreach ($plans as $plan)
                @php
                    $isStandard = $plan->code === 'standard';
                    $features = $planFeatures[$plan->code] ?? ['Plan personalizado'];
                @endphp
                <label for="plan_{{ $plan->code }}"
                       class="plan-card relative flex flex-col p-5 rounded-2xl cursor-pointer transition-all overflow-hidden"
                       style="border: 2px solid var(--border); background: linear-gradient(145deg, var(--surface) 0%, var(--bg) 100%);"
                       data-plan-code="{{ $plan->code }}"
                       data-base-price="{{ $plan->price }}">
                    <input type="radio" id="plan_{{ $plan->code }}" name="subscription_plan" value="{{ $plan->code }}"
                           class="sr-only plan-radio"
                           {{ old('subscription_plan', 'standard') === $plan->code ? 'checked' : '' }}>

                    @if ($isStandard)
                        <span class="absolute top-0 right-0 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white rounded-bl-xl"
                              style="background: linear-gradient(135deg, var(--cta), var(--primary));">
                            MÁS VENDIDO
                        </span>
                    @endif

                    <span class="text-lg font-bold mb-1" style="color: var(--text-main);">{{ $plan->name }}</span>

                    <div class="my-3">
                        <span class="discount-badge hidden text-xs font-bold px-2 py-0.5 rounded-full text-emerald-600 bg-emerald-500/10 mb-1 inline-block"></span>
                        <div class="flex items-baseline gap-2">
                            <span class="original-price text-sm line-through hidden" style="color: var(--text-tertiary);"></span>
                            <span class="monthly-price text-3xl font-extrabold" style="color: var(--primary);"></span>
                            <span class="text-xs font-medium" style="color: var(--text-tertiary);">/mes</span>
                        </div>
                        <p class="total-text text-xs font-semibold mt-2" style="color: var(--cta);"></p>
                    </div>

                    <ul class="space-y-1.5 flex-1">
                        @foreach ($features as $feature)
                            <li class="text-xs flex items-start gap-2" style="color: var(--text-tertiary);">
                                <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" style="color: var(--primary);" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    <span class="plan-check absolute top-4 left-4 hidden items-center justify-center w-5 h-5 rounded-full"
                          style="background-color: var(--primary);">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="white" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                </label>
            @endforeach
        </div>

        {{-- Bank accounts --}}
        <h3 class="text-sm font-bold uppercase tracking-wider mb-3" style="color: var(--primary);">3. Cuentas de Destino autorizadas</h3>
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

        {{-- Payment data --}}
        <h3 class="text-sm font-bold uppercase tracking-wider mb-3" style="color: var(--primary);">4. Datos del Pago</h3>
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
                    <option value="Banco Guayaquil - Ahorros #123456789">Banco Guayaquil - Ahorros #123456789</option>
                    <option value="Banco Pichincha - Corriente #987654321">Banco Pichincha - Corriente #987654321</option>
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
            <button type="submit" class="btn-primary">Continuar →</button>
        </div>
    </form>
</div>

@push('wizard-scripts')
<script>
(function() {
    const discountMap = @json($discountMap);
    let selectedMonths = parseInt(document.getElementById('subscription_duration_months').value) || 12;

    function getDiscount(months) {
        return discountMap[months] || 0;
    }

    function formatMoney(amount) {
        return 'S/ ' + amount.toFixed(2);
    }

    function updatePricing() {
        const discount = getDiscount(selectedMonths);

        document.getElementById('subscription_discount_percentage').value = discount;

        document.querySelectorAll('.plan-card').forEach(function(card) {
            const basePrice = parseFloat(card.dataset.basePrice);
            const discountedMonthly = basePrice * (1 - discount / 100);
            const total = discountedMonthly * selectedMonths;

            const badge = card.querySelector('.discount-badge');
            const original = card.querySelector('.original-price');
            const monthly = card.querySelector('.monthly-price');
            const totalText = card.querySelector('.total-text');

            if (discount > 0) {
                badge.textContent = '-' + discount + '%';
                badge.classList.remove('hidden');
                original.textContent = formatMoney(basePrice);
                original.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
                original.classList.add('hidden');
            }

            monthly.textContent = formatMoney(discountedMonthly);
            totalText.textContent = selectedMonths === 1
                ? 'Pago mensual: ' + formatMoney(total)
                : 'Obtén ' + selectedMonths + ' meses por ' + formatMoney(total);
        });

        const selectedPlan = document.querySelector('.plan-radio:checked');
        if (selectedPlan) {
            const card = selectedPlan.closest('.plan-card');
            const basePrice = parseFloat(card.dataset.basePrice);
            const total = basePrice * (1 - discount / 100) * selectedMonths;
            document.getElementById('subscription_amount').value = total.toFixed(2);
        }

        highlightSelectedPlan();
    }

    function highlightSelectedPlan() {
        document.querySelectorAll('.plan-card').forEach(function(card) {
            const radio = card.querySelector('.plan-radio');
            const check = card.querySelector('.plan-check');
            if (radio.checked) {
                card.style.borderColor = 'var(--primary)';
                card.style.boxShadow = '0 8px 24px rgba(10,126,165,0.15)';
                check.classList.remove('hidden');
                check.classList.add('flex');
            } else {
                card.style.borderColor = 'var(--border)';
                card.style.boxShadow = 'none';
                check.classList.add('hidden');
                check.classList.remove('flex');
            }
        });
    }

    document.querySelectorAll('.duration-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            selectedMonths = parseInt(this.dataset.months);
            document.getElementById('subscription_duration_months').value = selectedMonths;

            document.querySelectorAll('.duration-btn').forEach(function(b) {
                if (parseInt(b.dataset.months) === selectedMonths) {
                    b.style.borderColor = 'var(--primary)';
                    b.style.backgroundColor = 'var(--primary-light)';
                    b.style.color = 'var(--primary)';
                } else {
                    b.style.borderColor = 'var(--border)';
                    b.style.backgroundColor = 'transparent';
                    b.style.color = 'var(--text-secondary)';
                }
            });

            updatePricing();
        });
    });

    document.querySelectorAll('.plan-radio').forEach(function(radio) {
        radio.addEventListener('change', updatePricing);
    });

    document.querySelectorAll('.duration-btn').forEach(function(btn) {
        if (parseInt(btn.dataset.months) === selectedMonths) {
            btn.click();
        }
    });
    updatePricing();
})();
</script>
@endpush
