<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Empresa — FlexDash</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('build/assets/FlexDash.jpg') }}">

    {{-- Bloqueo de parpadeo de tema --}}
    <script>
        (function() {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'brand-blue':    '#0A7EA5',
                        'brand-teal':    '#0A7EA5',
                        'brand-yellow':  '#F2A900',
                        'brand-orange':  '#E35205',
                        'brand-magenta': '#A41D6A',
                        'brand-dark':    '#0D1E36',
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --primary:        #0A7EA5;
            --primary-dark:   #075f7d;
            --primary-light:  rgba(10,126,165,0.09);
            --cta:            #E35205;
            --cta-dark:       #b83f04;
            --bg:             #F8F9FA;
            --surface:        #FFFFFF;
            --text-main:      #0D1E36;
            --text-secondary: #374151;
            --text-tertiary:  #6B7280;
            --border:         #E5E7EB;
            --border-light:   #F3F4F6;
            --shadow-sm: 0 2px 4px rgba(13,30,54,0.04), 0 4px 12px rgba(13,30,54,0.06);
            --shadow-md: 0 4px 12px rgba(13,30,54,0.08), 0 12px 24px rgba(13,30,54,0.12);
        }
        html.dark {
            --primary:        #1aa3d4;
            --primary-dark:   #0A7EA5;
            --primary-light:  rgba(26,163,212,0.13);
            --cta:            #f06030;
            --cta-dark:       #E35205;
            --bg:             #0D1E36;
            --surface:        #162538;
            --text-main:      #F9FAFB;
            --text-secondary: #D1D5DB;
            --text-tertiary:  #9CA3AF;
            --border:         #1e3352;
            --border-light:   #172944;
            --shadow-sm: 0 4px 6px rgba(0,0,0,0.4);
            --shadow-md: 0 10px 20px rgba(0,0,0,0.5);
        }

        * { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; box-sizing: border-box; }

        body {
            background-color: var(--bg);
            color: var(--text-secondary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            transition: background-color 0.3s, color 0.3s;
        }

        .input-solid {
            background-color: var(--bg);
            border: 1px solid var(--border);
            color: var(--text-main);
            border-radius: 8px;
            padding: 10px 14px;
            width: 100%;
            font-size: 0.875rem;
            font-weight: 500;
            outline: none;
            transition: all 0.2s;
        }
        .input-solid:focus {
            border-color: var(--primary);
            background-color: var(--surface);
            box-shadow: 0 0 0 3px var(--primary-light);
        }
        .input-solid::placeholder { color: var(--text-tertiary); }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .btn-primary {
            background-color: var(--cta); color: #fff;
            padding: 10px 20px; border-radius: 8px; font-weight: 700;
            font-size: 0.875rem; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary:hover { background-color: var(--cta-dark); transform: translateY(-1px); }

        .step-dot {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.8rem; flex-shrink: 0;
            transition: all 0.3s;
        }
        .step-dot.done    { background-color: var(--primary); color: #fff; }
        .step-dot.active  { background-color: var(--primary); color: #fff; box-shadow: 0 0 0 4px var(--primary-light); }
        .step-dot.pending { background-color: rgba(255,255,255,0.12); color: rgba(255,255,255,0.5); border: 1px solid rgba(255,255,255,0.15); }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
    </style>

    @stack('wizard-styles')
</head>
<body>

@php
    /* Map $step (string) → numeric position for sidebar highlights */
    $stepMap   = ['type' => 1, 'account' => 2, 'entity' => 3, 'review' => 4, 'verify' => 5];
    $stepNames = [
        1 => 'Tipo de Empresa',
        2 => 'Datos de Cuenta',
        3 => 'Datos de Entidad',
        4 => 'Revisar y Confirmar',
        5 => 'Verificación OTP',
    ];
    $currentStep = $stepMap[$step ?? 'type'] ?? 1;
    $totalSteps  = 5;
@endphp

<div class="w-full max-w-5xl flex flex-col md:flex-row overflow-hidden rounded-2xl"
     style="box-shadow: var(--shadow-md); min-height: 480px;">

    {{-- ── Panel Izquierdo: Steps ──────────────────────────── --}}
    <div class="md:w-72 flex-shrink-0 flex flex-col justify-between p-8"
         style="background-color: #0D1E36;"
         data-brand-classes="bg-brand-blue bg-brand-yellow bg-brand-orange bg-brand-magenta">

        {{-- Logo --}}
        <div>
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl overflow-hidden bg-white p-0.5 flex-shrink-0">
                    <img src="{{ asset('build/assets/FlexDash.jpg') }}" class="w-full h-full object-cover rounded-lg" alt="FlexDash">
                </div>
                <div>
                    <h1 class="text-white font-bold text-base leading-none">FlexDash</h1>
                    <p class="text-xs mt-0.5" style="color: rgba(255,255,255,0.40);">Registro de Empresa</p>
                </div>
            </div>

            {{-- Steps List --}}
            <div class="space-y-1">
                @foreach($stepNames as $num => $label)
                    @php
                        $dotClass = $num < $currentStep ? 'done' : ($num === $currentStep ? 'active' : 'pending');
                    @endphp
                    <div class="flex items-center gap-3 py-2">
                        <div class="step-dot {{ $dotClass }}">
                            @if($num < $currentStep)
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest" style="color: rgba(255,255,255,0.30);">Paso {{ $num }}</p>
                            <p class="text-sm font-semibold transition-opacity {{ $num === $currentStep ? 'text-white' : 'text-white opacity-40' }}">
                                {{ $label }}
                            </p>
                        </div>
                    </div>
                    @if($num < $totalSteps)
                        <div class="w-0.5 h-5 ml-4" style="background: rgba(255,255,255,0.08);"></div>
                    @endif
                @endforeach
            </div>
        </div>

        <p class="text-xs mt-8" style="color: rgba(255,255,255,0.20);">
            &copy; {{ date('Y') }} FlexDash POS. Todos los derechos reservados.
        </p>
    </div>

    {{-- ── Panel Derecho: Formulario del Step ─────────────── --}}
    <div class="flex-1 p-8 md:p-10 flex flex-col" style="background-color: var(--surface);">

        {{-- Alerts --}}
        @if(session('status'))
            <div class="mb-4 p-4 rounded-xl text-sm font-medium"
                 style="background-color: rgba(10,126,165,0.08); border: 1px solid rgba(10,126,165,0.25); color: var(--primary);">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-5 p-4 rounded-xl text-sm font-medium"
                 style="background-color: rgba(220,38,38,0.08); border: 1px solid rgba(220,38,38,0.25); color: #DC2626;">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Step Content --}}
        <div class="flex-1">
            @include('registration::steps.' . ($step ?? 'type'))
        </div>

        {{-- Back link (except step 1) --}}
        @if($currentStep > 1)
            <div class="mt-6 pt-5" style="border-top: 1px solid var(--border-light);">
                @php
                    $backRoutes = [
                        2 => 'registration.type',
                        3 => 'registration.account.show',
                        4 => 'registration.entity.show',
                        5 => 'registration.review.show',
                    ];
                @endphp
                <a href="{{ route($backRoutes[$currentStep] ?? 'registration.type') }}"
                   class="text-sm font-semibold transition-colors inline-flex items-center gap-1"
                   style="color: var(--text-tertiary);">
                    ← Volver al paso anterior
                </a>
            </div>
        @endif
    </div>
</div>

@stack('wizard-scripts')
</body>
</html>
