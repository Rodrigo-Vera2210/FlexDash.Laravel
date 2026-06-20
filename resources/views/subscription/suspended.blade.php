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
<body class="bg-[#0D1E36] min-height-screen flex items-center justify-center p-6 text-slate-100 min-h-screen">
    @php
        $user = auth()->user();
        $company = $user?->company;
        $status = $company?->subscription_status ?? 'inactive';
    @endphp

    <div class="max-w-md w-full bg-[#162538] p-8 rounded-2xl border border-slate-800 shadow-2xl text-center">
        {{-- Icon & Header --}}
        <div class="flex justify-center mb-6">
            <div class="w-16 h-16 rounded-2xl overflow-hidden bg-white p-1 flex-shrink-0 flex items-center justify-center">
                <img src="{{ asset('build/assets/FlexDash.jpg') }}" class="w-full h-full object-cover rounded-xl" alt="FlexDash">
            </div>
        </div>

        @if($status === 'pending_approval')
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-500 mb-4 border border-amber-500/20">
                Pendiente de Aprobación
            </span>
            <h1 class="text-2xl font-bold text-slate-100 mb-3">Verificación en Proceso</h1>
            <p class="text-slate-400 text-sm leading-relaxed mb-6">
                Estamos revisando tu comprobante de pago para la empresa <strong>{{ $company?->name }}</strong>. Este proceso suele tardar unos minutos en horario laboral.
            </p>
        @elseif($status === 'rejected')
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-500 mb-4 border border-red-500/20">
                Pago Rechazado
            </span>
            <h1 class="text-2xl font-bold text-slate-100 mb-3">Pago No Verificado</h1>
            <p class="text-slate-400 text-sm leading-relaxed mb-6">
                El comprobante de transferencia bancaria registrado fue rechazado por nuestro equipo de administración. Por favor, contáctanos para más detalles.
            </p>
        @else
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-500 mb-4 border border-red-500/20">
                Suscripción Suspendida
            </span>
            <h1 class="text-2xl font-bold text-slate-100 mb-3">Acceso Restringido</h1>
            <p class="text-slate-400 text-sm leading-relaxed mb-6">
                La suscripción para la empresa <strong>{{ $company?->name }}</strong> no está activa o ha expirado. Por favor, regularice su estado de cuenta para continuar operando.
            </p>
        @endif

        {{-- Actions --}}
        <div class="space-y-3">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full py-2.5 px-5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold rounded-lg text-sm transition-colors border border-slate-700">
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </div>
</body>
</html>
