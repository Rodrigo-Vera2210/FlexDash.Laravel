<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción Inactiva — FlexDash</title>
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-blue':    '#0A7EA5',
                        'brand-dark':    '#0D1E36',
                        'brand-orange':  '#E35205',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#0D1E36] min-h-screen flex items-center justify-center p-6 text-slate-100">
    @php
        $user = auth()->user();
        $company = $user?->company;
        $status = $company?->subscription_status ?? 'inactive';
    @endphp

    <div class="w-full @if($status === 'pending_approval') max-w-md @else max-w-5xl @endif transition-all duration-300">
        {{-- Status Flash Alert --}}
        @if (session('status'))
            <div class="mb-6 p-4 rounded-xl text-sm font-medium bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-lg"></i>
                <div>{{ session('status') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 rounded-xl text-sm font-medium bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl text-sm font-medium bg-rose-500/10 border border-rose-500/20 text-rose-400">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($status === 'pending_approval')
            {{-- Compact Card for Pending Verification --}}
            <div class="bg-[#162538] p-8 rounded-2xl border border-slate-800 shadow-2xl text-center">
                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 rounded-2xl overflow-hidden bg-white p-1 flex-shrink-0 flex items-center justify-center">
                        <img src="{{ asset('build/assets/FlexDash.jpg') }}" class="w-full h-full object-cover rounded-xl" alt="FlexDash">
                    </div>
                </div>

                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-500 mb-4 border border-amber-500/20">
                    <i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Pendiente de Aprobación
                </span>
                <h1 class="text-2xl font-bold text-slate-100 mb-3">Verificación en Proceso</h1>
                <p class="text-slate-400 text-sm leading-relaxed mb-6">
                    Estamos revisando tu comprobante de pago para la empresa <strong>{{ $company?->name }}</strong>. Este proceso suele tardar unos minutos en horario laboral.
                </p>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold rounded-lg text-sm transition-colors border border-slate-700">
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        @else
            {{-- Two-column Grid for Inactive/Suspended/Rejected with Plan Selection Form --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                {{-- Left Column: Status, Reason and Bank Info --}}
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-[#162538] p-8 rounded-2xl border border-slate-800 shadow-2xl">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-xl overflow-hidden bg-white p-1 flex-shrink-0 flex items-center justify-center">
                                <img src="{{ asset('build/assets/FlexDash.jpg') }}" class="w-full h-full object-cover rounded-lg" alt="FlexDash">
                            </div>
                            <div class="text-left">
                                <h2 class="text-lg font-bold text-slate-100">FlexDash POS</h2>
                                <p class="text-xs text-slate-400">{{ $company?->name }}</p>
                            </div>
                        </div>

                        @if($status === 'rejected')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-500 mb-4 border border-red-500/20">
                                <i class="fa-solid fa-circle-xmark mr-1.5"></i> Pago Rechazado
                            </span>
                            <h1 class="text-2xl font-bold text-slate-100 mb-3 text-left">Comprobante No Válido</h1>
                            <p class="text-slate-400 text-sm leading-relaxed mb-4 text-left">
                                El comprobante de transferencia bancaria registrado fue rechazado por nuestro equipo administrativo. Por favor, cargue un nuevo comprobante para reactivar su suscripción.
                            </p>
                            @if($company?->suspension_reason)
                                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs text-left">
                                    <strong class="block mb-1 font-semibold uppercase tracking-wider text-[10px] text-red-300">Motivo del rechazo:</strong>
                                    {{ $company->suspension_reason }}
                                </div>
                            @endif
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-500 mb-4 border border-red-500/20">
                                <i class="fa-solid fa-circle-exclamation mr-1.5"></i> Suscripción Suspendida
                            </span>
                            <h1 class="text-2xl font-bold text-slate-100 mb-3 text-left">Acceso Restringido</h1>
                            <p class="text-slate-400 text-sm leading-relaxed mb-4 text-left">
                                La suscripción de su cuenta no se encuentra activa o ha vencido. Regularice su estado para poder seguir utilizando los módulos del sistema.
                            </p>
                            @if($company?->suspension_reason)
                                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs text-left">
                                    <strong class="block mb-1 font-semibold uppercase tracking-wider text-[10px] text-red-300">Motivo de la suspensión:</strong>
                                    {{ $company->suspension_reason }}
                                </div>
                            @endif
                        @endif

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-2.5 px-5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold rounded-lg text-sm transition-colors border border-slate-700">
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>

                    {{-- Bank Info Card --}}
                    <div class="bg-[#162538] p-6 rounded-2xl border border-slate-800 shadow-2xl">
                        <h3 class="font-bold text-sm text-slate-200 mb-4 uppercase tracking-wider text-left border-b border-slate-800 pb-2">
                            Cuentas Bancarias de Destino
                        </h3>
                        <div class="space-y-4">
                            <div class="p-4 rounded-xl border border-slate-800/80 bg-[#0d1e36]/50 text-left text-xs">
                                <span class="font-bold block text-sm text-brand-blue">Banco Guayaquil</span>
                                <span class="opacity-80 block mt-1">Cuenta de Ahorros #123456789</span>
                                <span class="opacity-50 block mt-0.5">FlexDash S.A. | RUC: 0999999999001</span>
                            </div>
                            <div class="p-4 rounded-xl border border-slate-800/80 bg-[#0d1e36]/50 text-left text-xs">
                                <span class="font-bold block text-sm text-brand-blue">Banco Pichincha</span>
                                <span class="opacity-80 block mt-1">Cuenta Corriente #987654321</span>
                                <span class="opacity-50 block mt-0.5">FlexDash S.A. | RUC: 0999999999001</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Payment Form --}}
                <div class="lg:col-span-7 bg-[#162538] p-8 rounded-2xl border border-slate-800 shadow-2xl text-left">
                    <h2 class="text-xl font-bold text-slate-100 mb-2">Reactivar Servicio</h2>
                    <p class="text-xs text-slate-400 mb-6">Seleccione su plan preferido, realice el pago por transferencia/depósito y cargue el comprobante a continuación.</p>

                    <form action="{{ route('subscription.suspended.payment') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        {{-- Plan Selection Cards --}}
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-3 text-slate-400">Seleccione un Plan</label>
                            <input type="hidden" name="plan" id="selected-plan" value="{{ old('plan', $company->subscription_plan ?? 'basic') }}">
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- Plan Basic Card --}}
                                <div id="card-basic" onclick="selectPlan('basic')" 
                                     class="cursor-pointer p-5 rounded-xl border-2 transition-all duration-200 flex flex-col justify-between hover:border-brand-blue/50 bg-[#0d1e36]/30
                                            @if(old('plan', $company->subscription_plan ?? 'basic') === 'basic') border-brand-blue ring-1 ring-brand-blue @else border-slate-800 @endif">
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <h4 class="font-bold text-base text-slate-200">Plan Basic</h4>
                                            <span class="text-xs text-slate-400 font-semibold">$29/mes</span>
                                        </div>
                                        <ul class="text-[11px] text-slate-400 space-y-1.5 mt-3">
                                            <li><i class="fa-solid fa-check text-brand-blue mr-1.5"></i> 1 Administrador</li>
                                            <li><i class="fa-solid fa-check text-brand-blue mr-1.5"></i> 2 Vendedores</li>
                                            <li><i class="fa-solid fa-check text-brand-blue mr-1.5"></i> 100 Transacciones/mes</li>
                                            <li class="opacity-60"><i class="fa-solid fa-xmark text-rose-500/70 mr-1.5"></i> Sin Compras/Proveedores</li>
                                        </ul>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-end text-xs font-bold text-brand-blue">
                                        <span class="select-indicator">
                                            @if(old('plan', $company->subscription_plan ?? 'basic') === 'basic') Seleccionado <i class="fa-solid fa-circle-check ml-1 text-emerald-500"></i> @else Elegir @endif
                                        </span>
                                    </div>
                                </div>

                                {{-- Plan Standard Card --}}
                                <div id="card-standard" onclick="selectPlan('standard')" 
                                     class="cursor-pointer p-5 rounded-xl border-2 transition-all duration-200 flex flex-col justify-between hover:border-brand-blue/50 bg-[#0d1e36]/30
                                            @if(old('plan', $company->subscription_plan) === 'standard') border-brand-blue ring-1 ring-brand-blue @else border-slate-800 @endif">
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <h4 class="font-bold text-base text-slate-200">Plan Standard</h4>
                                            <span class="text-xs text-slate-400 font-semibold">$59/mes</span>
                                        </div>
                                        <ul class="text-[11px] text-slate-400 space-y-1.5 mt-3">
                                            <li><i class="fa-solid fa-check text-brand-blue mr-1.5"></i> 2 Administradores</li>
                                            <li><i class="fa-solid fa-check text-brand-blue mr-1.5"></i> 10 Vendedores</li>
                                            <li><i class="fa-solid fa-check text-brand-blue mr-1.5"></i> 500 Transacciones/mes</li>
                                            <li><i class="fa-solid fa-check text-brand-blue mr-1.5"></i> Módulo Compras y Proveedores</li>
                                        </ul>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-end text-xs font-bold text-brand-blue">
                                        <span class="select-indicator">
                                            @if(old('plan', $company->subscription_plan) === 'standard') Seleccionado <i class="fa-solid fa-circle-check ml-1 text-emerald-500"></i> @else Elegir @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Bank fields --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="bank_origin" class="block text-xs font-bold uppercase tracking-wider mb-2 text-slate-400">Banco de Origen</label>
                                <input type="text" id="bank_origin" name="bank_origin" value="{{ old('bank_origin') }}"
                                       class="w-full px-4 py-2.5 bg-[#0d1e36]/50 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue text-sm transition-all"
                                       placeholder="Ej. Banco Guayaquil, Pichincha, etc." required>
                            </div>

                            <div>
                                <label for="account_destination" class="block text-xs font-bold uppercase tracking-wider mb-2 text-slate-400">Cuenta de Destino</label>
                                <select id="account_destination" name="account_destination" 
                                        class="w-full px-4 py-2.5 bg-[#0d1e36]/70 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue text-sm transition-all" required>
                                    <option value="Banco Guayaquil - Ahorros #123456789">Banco Guayaquil - Ahorros #123456789</option>
                                    <option value="Banco Pichincha - Corriente #987654321">Banco Pichincha - Corriente #987654321</option>
                                </select>
                            </div>
                        </div>

                        {{-- File Receipt Upload --}}
                        <div>
                            <label for="payment_receipt" class="block text-xs font-bold uppercase tracking-wider mb-2 text-slate-400">Comprobante de Transferencia / Depósito</label>
                            <div class="relative flex items-center justify-center w-full">
                                <label for="payment_receipt" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-800 border-dashed rounded-xl cursor-pointer bg-[#0d1e36]/20 hover:bg-[#0d1e36]/40 hover:border-slate-700 transition-colors">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <i class="fa-solid fa-image text-slate-500 text-2xl mb-2"></i>
                                        <p id="file-upload-text" class="text-xs text-slate-400"><span class="font-bold text-brand-blue">Haz clic para subir</span> o arrastra tu archivo</p>
                                        <p class="text-[10px] text-slate-500 mt-1">Soporta formatos PNG, JPG, JPEG (Max 4MB)</p>
                                    </div>
                                    <input type="file" id="payment_receipt" name="payment_receipt" accept="image/*" class="hidden" required onchange="displayFileName(this)">
                                </label>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <div class="pt-2">
                            <button type="submit" class="w-full py-3 px-6 bg-brand-orange hover:bg-brand-orange/95 text-white font-bold rounded-lg text-sm transition-all shadow-lg hover:shadow-orange-500/10 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-circle-check"></i> Registrar Pago y Reactivar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <script>
        function selectPlan(plan) {
            document.getElementById('selected-plan').value = plan;
            
            const cardBasic = document.getElementById('card-basic');
            const cardStandard = document.getElementById('card-standard');
            
            if (plan === 'basic') {
                cardBasic.className = "cursor-pointer p-5 rounded-xl border-2 transition-all duration-200 flex flex-col justify-between hover:border-brand-blue/50 bg-[#0d1e36]/30 border-brand-blue ring-1 ring-brand-blue";
                cardStandard.className = "cursor-pointer p-5 rounded-xl border-2 transition-all duration-200 flex flex-col justify-between hover:border-brand-blue/50 bg-[#0d1e36]/30 border-slate-800";
                
                cardBasic.querySelector('.select-indicator').innerHTML = 'Seleccionado <i class="fa-solid fa-circle-check ml-1 text-emerald-500"></i>';
                cardStandard.querySelector('.select-indicator').innerHTML = 'Elegir';
            } else {
                cardBasic.className = "cursor-pointer p-5 rounded-xl border-2 transition-all duration-200 flex flex-col justify-between hover:border-brand-blue/50 bg-[#0d1e36]/30 border-slate-800";
                cardStandard.className = "cursor-pointer p-5 rounded-xl border-2 transition-all duration-200 flex flex-col justify-between hover:border-brand-blue/50 bg-[#0d1e36]/30 border-brand-blue ring-1 ring-brand-blue";
                
                cardBasic.querySelector('.select-indicator').innerHTML = 'Elegir';
                cardStandard.querySelector('.select-indicator').innerHTML = 'Seleccionado <i class="fa-solid fa-circle-check ml-1 text-emerald-500"></i>';
            }
        }

        function displayFileName(input) {
            const uploadText = document.getElementById('file-upload-text');
            if (input.files && input.files[0]) {
                const name = input.files[0].name;
                uploadText.innerHTML = `<span class="font-bold text-emerald-400">Archivo seleccionado:</span> ${name}`;
            } else {
                uploadText.innerHTML = `<span class="font-bold text-brand-blue">Haz clic para subir</span> o arrastra tu archivo`;
            }
        }
    </script>
</body>
</html>
