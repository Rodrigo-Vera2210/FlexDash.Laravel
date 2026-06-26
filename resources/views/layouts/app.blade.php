@php
    $user = auth()->user();
    $showExpiryBanner = false;
    $daysRemaining = 0;
    if ($user && $user->role !== 'superadmin' && $user->company) {
        $expiresAt = $user->company->subscription_expires_at;
        if ($expiresAt) {
            $daysRemaining = now()->diffInDays($expiresAt, false);
            if ($daysRemaining >= 0 && $daysRemaining <= 5) {
                $showExpiryBanner = true;

                // Dispatch expiry warning email once per day per company
                $company = $user->company;
                $cacheKey = 'subscription_expiry_email_sent_' . $company->id;
                if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    $owner = $company->owner;
                    if ($owner) {
                        $owner->notify(
                            new \App\Modules\Registration\Notifications\SubscriptionExpiryNotification(
                                $daysRemaining,
                                $expiresAt->format('d/m/Y'),
                            ),
                        );
                    }
                    \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->endOfDay());
                }
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="es" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FlexDash') — Panel de Control</title>
    <meta name="description" content="FlexDash — Sistema de gestión empresarial POS">
    <link rel="icon" type="image/jpeg" href="{{ asset('build/assets/FlexDash.jpg') }}">

    {{-- Bloqueo de parpadeo de tema --}}
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    {{-- Google Fonts: Plus Jakarta Sans + JetBrains Mono --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap"
        rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- AlpineJS --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <style>
        /* =========================================================
           FLEXDASH — Variables de tema (duplicadas aquí para CDN)
           ========================================================= */
        :root {
            --primary: #0A7EA5;
            --primary-dark: #075f7d;
            --primary-light: rgba(10, 126, 165, 0.09);
            --cta: #E35205;
            --cta-dark: #b83f04;
            --accent-gold: #F2A900;
            --accent-magenta: #A41D6A;
            --bg: #F8F9FA;
            --surface: #FFFFFF;
            --text-main: #0D1E36;
            --text-secondary: #374151;
            --text-tertiary: #6B7280;
            --border: #E5E7EB;
            --border-light: #F3F4F6;
            --success: #16A34A;
            --success-light: rgba(22, 163, 74, 0.10);
            --danger: #DC2626;
            --danger-light: rgba(220, 38, 38, 0.10);
            --warning: #D97706;
            --warning-light: rgba(217, 119, 6, 0.12);
            --shadow-sm: 0 2px 4px rgba(13, 30, 54, 0.04), 0 4px 12px rgba(13, 30, 54, 0.06);
            --shadow-md: 0 4px 12px rgba(13, 30, 54, 0.08), 0 12px 24px rgba(13, 30, 54, 0.12);
        }

        html.dark {
            --primary: #1aa3d4;
            --primary-dark: #0A7EA5;
            --primary-light: rgba(26, 163, 212, 0.13);
            --cta: #f06030;
            --cta-dark: #E35205;
            --bg: #0D1E36;
            --surface: #162538;
            --text-main: #F9FAFB;
            --text-secondary: #D1D5DB;
            --text-tertiary: #9CA3AF;
            --border: #1e3352;
            --border-light: #172944;
            --success: #22C55E;
            --success-light: rgba(34, 197, 94, 0.13);
            --danger: #F87171;
            --danger-light: rgba(248, 113, 113, 0.13);
            --warning: #FBBF24;
            --warning-light: rgba(251, 191, 36, 0.13);
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.4);
            --shadow-md: 0 10px 20px rgba(0, 0, 0, 0.5);
        }

        * {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        body {
            background-color: var(--bg);
            color: var(--text-secondary);
            transition: background-color 0.3s, color 0.3s;
        }

        /* Sidebar */
        #main-sidebar {
            background-color: #0D1E36;
            transition: background-color 0.3s;
        }

        html.dark #main-sidebar {
            background-color: #091525;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 2px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.60);
            text-decoration: none;
        }

        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .nav-item.active {
            background-color: var(--primary);
            color: #fff;
            box-shadow: 0 4px 12px rgba(10, 126, 165, 0.35);
        }

        /* Cards */
        .card-panel {
            background-color: var(--surface);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            transition: box-shadow 0.3s;
        }

        /* Topbar */
        #main-topbar {
            background-color: var(--surface);
            border-bottom: 1px solid var(--border);
            transition: background-color 0.3s, border-color 0.3s;
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--cta) !important;
            color: #fff !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            border: none !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            transition: all 0.2s ease !important;
        }

        .btn-primary:hover {
            background-color: var(--cta-dark) !important;
            transform: translateY(-1px) !important;
        }

        .btn-secondary {
            background-color: var(--surface) !important;
            color: var(--text-main) !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            border: 1px solid var(--border) !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            transition: all 0.2s ease !important;
        }

        .btn-secondary:hover {
            border-color: var(--primary) !important;
            color: var(--primary) !important;
            background-color: var(--primary-light) !important;
        }

        .btn-outline {
            background-color: var(--surface) !important;
            color: var(--text-main) !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            border: 1px solid var(--border) !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            transition: all 0.2s ease !important;
        }

        .btn-outline:hover {
            background-color: var(--bg) !important;
            border-color: var(--primary) !important;
            color: var(--primary) !important;
        }

        .btn-danger {
            background-color: var(--danger) !important;
            color: #fff !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            border: none !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            transition: all 0.2s ease !important;
        }

        .btn-danger:hover {
            background-color: var(--danger-light) !important;
            color: var(--danger) !important;
            border: 1px solid var(--danger) !important;
        }

        .btn-success {
            background-color: var(--success) !important;
            color: #fff !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            border: none !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            transition: all 0.2s ease !important;
        }

        .btn-success:hover {
            background-color: var(--success-light) !important;
            color: var(--success) !important;
            border: 1px solid var(--success) !important;
        }

        .btn-icon {
            width: 36px !important;
            height: 36px !important;
            border-radius: 8px !important;
            display: inline-flex !important;
            justify-content: center !important;
            align-items: center !important;
            transition: all 0.2s ease !important;
            color: var(--text-tertiary) !important;
            background: transparent !important;
            border: none !important;
            cursor: pointer !important;
        }

        .btn-icon:hover {
            background-color: var(--primary-light) !important;
            color: var(--primary) !important;
        }

        /* Badges */
        .badge {
            padding: 3px 9px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-success {
            background-color: var(--success-light);
            color: var(--success);
        }

        .badge-danger {
            background-color: var(--danger-light);
            color: var(--danger);
        }

        .badge-warning {
            background-color: var(--warning-light);
            color: var(--warning);
        }

        .badge-draft {
            background-color: var(--border-light);
            color: var(--text-tertiary);
        }

        .badge-approved {
            background-color: rgba(10, 126, 165, 0.10);
            color: var(--primary);
        }

        .badge-paid {
            background-color: var(--success-light);
            color: var(--success);
        }

        .badge-cancelled {
            background-color: var(--danger-light);
            color: var(--danger);
        }

        .badge-info {
            background-color: var(--info-light, rgba(8, 145, 178, 0.10));
            color: var(--info, #0891B2);
        }

        /* Page animation */
        .page-fade {
            animation: pageIn 0.3s ease forwards;
        }

        @keyframes pageIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Forms */
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            background-color: var(--bg);
            color: var(--text-main);
            outline: none;
            transition: all 0.2s;
        }

        .form-input:focus {
            border-color: var(--primary);
            background-color: var(--surface);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .form-input::placeholder {
            color: var(--text-tertiary);
        }

        /* Forms Redesign */
        .input-solid {
            background-color: var(--surface) !important;
            border: 1px solid var(--border) !important;
            color: var(--text-main) !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            width: 100% !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            transition: all 0.2s ease !important;
            outline: none !important;
            box-shadow: var(--shadow-sm) !important;
        }

        .input-solid:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 2px var(--primary-light) !important;
        }

        .input-solid::placeholder {
            color: var(--text-tertiary) !important;
        }

        select.input-solid {
            appearance: none !important;
            background-image: url("data:image/svg+xml;utf8,<svg fill='none' stroke='%236B7280' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5'></path></svg>") !important;
            background-repeat: no-repeat !important;
            background-position: right 12px center !important;
            background-size: 16px !important;
            padding-right: 36px !important;
        }

        html.dark select.input-solid {
            background-image: url("data:image/svg+xml;utf8,<svg fill='none' stroke='%239CA3AF' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5'></path></svg>") !important;
        }

        .input-icon-wrapper {
            position: relative !important;
            width: 100% !important;
            display: block !important;
        }

        .input-icon-wrapper i,
        .input-icon-wrapper svg {
            position: absolute !important;
            top: 50% !important;
            left: 12px !important;
            transform: translateY(-50%) !important;
            color: var(--text-tertiary) !important;
            pointer-events: none !important;
            font-size: 1rem !important;
            z-index: 10 !important;
        }

        .input-icon-wrapper .input-solid {
            padding-left: 36px !important;
        }

        /* Tables */
        .table-header {
            padding: 12px 16px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-tertiary);
            background-color: var(--bg);
            border-bottom: 1px solid var(--border);
        }

        .table-cell {
            padding: 14px 16px;
            font-size: 0.875rem;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }

        /* Alerts */
        .alert-success {
            background-color: var(--success-light);
            border: 1px solid var(--success);
            color: var(--success);
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .alert-danger {
            background-color: var(--danger-light);
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* Legacy compat — card/kpi-card aliases */
        .card {
            background-color: var(--surface);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
        }

        .kpi-card {
            background-color: var(--surface);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            transition: box-shadow 0.3s;
        }

        .kpi-card:hover {
            box-shadow: var(--shadow-md);
        }
    </style>

    @stack('styles')
</head>

<body class="h-screen flex overflow-hidden" style="background-color: var(--bg);">
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-20 hidden lg:hidden" onclick="closeSidebar()">
    </div>
    {{-- ── Sidebar ─────────────────────────────────────────────── --}}
    <aside id="main-sidebar"
        class="w-64 flex-shrink-0 flex flex-col h-full z-30 fixed inset-y-0 left-0 -translate-x-full transition-transform duration-300
              lg:relative lg:translate-x-0"
        style="background-color: #0D1E36; min-height: 100vh;">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 py-5 border-b" style="border-color: rgba(255,255,255,0.08);">
            <div class="w-9 h-9 rounded-xl overflow-hidden flex-shrink-0 bg-white p-0.5">
                <img src="{{ asset('build/assets/FlexDash.jpg') }}" class="w-full h-full object-cover rounded-lg"
                    alt="FlexDash">
            </div>
            <div>
                <p class="text-white font-bold text-sm leading-none tracking-wide">FlexDash</p>
                <p class="text-xs mt-0.5" style="color: rgba(255,255,255,0.40);">Sistema POS v2.0</p>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 py-5 px-3 overflow-y-auto space-y-0.5">
            @if (auth()->user()->role === 'superadmin')
                <p class="px-3 pb-2 text-xs font-bold uppercase tracking-widest" style="color: rgba(255,255,255,0.30);">
                    Administración</p>

                <a href="{{ route('superadmin.dashboard') }}"
                    class="nav-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-crown w-5 text-center text-amber-500"></i>
                    <span>Portal Superadmin</span>
                </a>

                <a href="{{ route('superadmin.payments.index') }}"
                    class="nav-item {{ request()->routeIs('superadmin.payments.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-cash-register w-5 text-center text-teal-400"></i>
                    <span>Caja de Suscripciones</span>
                </a>

                <a href="{{ route('superadmin.plans.index') }}"
                    class="nav-item {{ request()->routeIs('superadmin.plans.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gears w-5 text-center text-indigo-400"></i>
                    <span>Administración de Planes</span>
                </a>

                <a href="{{ route('superadmin.billing.index') }}"
                    class="nav-item {{ request()->routeIs('superadmin.billing.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-invoice-dollar w-5 text-center text-rose-400"></i>
                    <span>Firma de Plataforma</span>
                </a>

                <p class="px-3 pb-2 pt-4 text-xs font-bold uppercase tracking-widest"
                    style="color: rgba(255,255,255,0.30);">Sistema</p>

                <a href="{{ auth()->user()->role === 'superadmin' ? route('superadmin.audits') : route('audit.index') }}"
                    class="nav-item {{ request()->routeIs('superadmin.audits') || request()->routeIs('audit.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-shield w-5 text-center"></i>
                    <span>Auditoría</span>
                </a>
            @elseif(auth()->user()->role === 'vendedor')
                <p class="px-3 pb-2 text-xs font-bold uppercase tracking-widest" style="color: rgba(255,255,255,0.30);">
                    Comercial</p>

                @if (auth()->user()->company?->hasModuleAccess('ventas'))
                    <a href="{{ route('sales.index') }}"
                        class="nav-item {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-receipt w-5 text-center"></i>
                        <span>Ventas</span>
                    </a>
                @endif

                @if (auth()->user()->company?->has_electronic_billing)
                    <a href="{{ route('billing.invoices.index') }}"
                        class="nav-item {{ request()->routeIs('billing.invoices.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-invoice w-5 text-center text-cyan-400"></i>
                        <span>Comprobantes SRI</span>
                    </a>
                @endif

                @if (auth()->user()->company?->hasModuleAccess('clientes'))
                    <a href="{{ route('partners.index') }}?type=cliente"
                        class="nav-item {{ request()->routeIs('partners.*') && request('type') !== 'proveedor' ? 'active' : '' }}">
                        <i class="fa-solid fa-users w-5 text-center"></i>
                        <span>Clientes</span>
                    </a>
                @endif

                @if (auth()->user()->company?->hasModuleAccess('kardex'))
                    <p class="px-3 pb-2 pt-4 text-xs font-bold uppercase tracking-widest"
                        style="color: rgba(255,255,255,0.30);">Inventario</p>

                    <a href="{{ route('inventory.index') }}"
                        class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-warehouse w-5 text-center"></i>
                        <span>Kardex</span>
                    </a>
                @endif

                <a href="{{ route('services.index') }}"
                    class="nav-item {{ request()->routeIs('services.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-screwdriver-wrench w-5 text-center"></i>
                    <span>Servicios</span>
                </a>
            @else
                <p class="px-3 pb-2 text-xs font-bold uppercase tracking-widest" style="color: rgba(255,255,255,0.30);">
                    Principal</p>

                <a href="{{ route('dashboard') }}"
                    class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                    <span>Dashboard</span>
                </a>

                <p class="px-3 pb-2 pt-4 text-xs font-bold uppercase tracking-widest"
                    style="color: rgba(255,255,255,0.30);">Comercial</p>

                @if (auth()->user()->company?->hasModuleAccess('ventas'))
                    <a href="{{ route('sales.index') }}"
                        class="nav-item {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-receipt w-5 text-center"></i>
                        <span>Ventas</span>
                    </a>
                @endif

                @if (auth()->user()->company?->has_electronic_billing)
                    <a href="{{ route('billing.invoices.index') }}"
                        class="nav-item {{ request()->routeIs('billing.invoices.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-invoice w-5 text-center text-cyan-400"></i>
                        <span>Comprobantes SRI</span>
                    </a>
                @endif

                @if (auth()->user()->company?->hasModuleAccess('compras'))
                    <a href="{{ route('purchases.index') }}"
                        class="nav-item {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-cart-shopping w-5 text-center"></i>
                        <span>Compras</span>
                    </a>
                @endif

                @if (auth()->user()->company?->hasModuleAccess('clientes'))
                    <a href="{{ route('partners.index') }}?type=cliente"
                        class="nav-item {{ request()->routeIs('partners.*') && request('type') !== 'proveedor' ? 'active' : '' }}">
                        <i class="fa-solid fa-users w-5 text-center"></i>
                        <span>Clientes</span>
                    </a>
                @endif

                @if (auth()->user()->company?->hasModuleAccess('proveedores'))
                    <a href="{{ route('partners.index') }}?type=proveedor"
                        class="nav-item {{ request()->routeIs('partners.*') && request('type') === 'proveedor' ? 'active' : '' }}">
                        <i class="fa-solid fa-building w-5 text-center"></i>
                        <span>Proveedores</span>
                    </a>
                @endif

                @if (auth()->user()->company?->hasModuleAccess('kardex'))
                    <p class="px-3 pb-2 pt-4 text-xs font-bold uppercase tracking-widest"
                        style="color: rgba(255,255,255,0.30);">Inventario</p>

                    <a href="{{ route('products.index') }}"
                        class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-boxes-stacked w-5 text-center"></i>
                        <span>Productos</span>
                    </a>

                    <a href="{{ route('inventory.index') }}"
                        class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-warehouse w-5 text-center"></i>
                        <span>Kardex</span>
                    </a>
                @endif

                <a href="{{ route('services.index') }}"
                    class="nav-item {{ request()->routeIs('services.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-screwdriver-wrench w-5 text-center"></i>
                    <span>Servicios</span>
                </a>

                @if (auth()->user()->company?->hasModuleAccess('caja_chica'))
                    <p class="px-3 pb-2 pt-4 text-xs font-bold uppercase tracking-widest"
                        style="color: rgba(255,255,255,0.30);">Finanzas</p>

                    <a href="{{ route('cashbox.index') }}"
                        class="nav-item {{ request()->routeIs('cashbox.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-cash-register w-5 text-center"></i>
                        <span>Caja Chica</span>
                    </a>
                @endif

                @if (auth()->user()->company?->hasModuleAccess('settings'))
                    <p class="px-3 pb-2 pt-4 text-xs font-bold uppercase tracking-widest"
                        style="color: rgba(255,255,255,0.30);">Sistema</p>

                    <a href="{{ route('branches.index') }}"
                        class="nav-item {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-store w-5 text-center text-amber-400"></i>
                        <span>Locales / Sucursales</span>
                    </a>

                    <a href="{{ route('sellers.index') }}"
                        class="nav-item {{ request()->routeIs('sellers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-users-gear w-5 text-center"></i>
                        <span>Vendedores</span>
                    </a>

                    <a href="{{ route('settings.subscription.index') }}"
                        class="nav-item {{ request()->routeIs('settings.subscription.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-credit-card w-5 text-center"></i>
                        <span>Suscripción</span>
                    </a>

                    <a href="{{ auth()->user()->role === 'superadmin' ? route('superadmin.audits') : route('audit.index') }}"
                        class="nav-item {{ request()->routeIs('superadmin.audits') || request()->routeIs('audit.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-shield w-5 text-center"></i>
                        <span>Auditoría</span>
                    </a>

                    <a href="{{ route('settings.catalogs.index') }}"
                        class="nav-item {{ request()->routeIs('settings.catalogs.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-gears w-5 text-center"></i>
                        <span>Configuración</span>
                    </a>

                    @if (auth()->user()->company?->has_electronic_billing)
                        <a href="{{ route('billing.settings.index') }}"
                            class="nav-item {{ request()->routeIs('billing.settings.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-file-shield w-5 text-center text-cyan-500"></i>
                            <span>Firma Electrónica</span>
                        </a>
                    @endif
                @endif
            @endif
        </nav>

        {{-- User Info --}}
        <div class="p-3 m-3 rounded-xl"
            style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0"
                    style="background-color: var(--primary-light); color: var(--primary); border: 2px solid rgba(26,163,212,0.3);">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-bold truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs truncate" style="color: rgba(255,255,255,0.40);">
                        {{ auth()->user()->role ?? 'usuario' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-icon" style="color: rgba(255,255,255,0.40);"
                        title="Cerrar sesión">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ── Main Content ─────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">

        {{-- Topbar --}}
        <header id="main-topbar" class="h-16 flex items-center justify-between px-6 flex-shrink-0 z-10">
            <div>
                <button class="btn-icon lg:hidden mr-3" onclick="toggleSidebar()" aria-label="Menú">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="text-base font-bold leading-none" style="color: var(--text-main);">@yield('page-title', 'Dashboard')</h1>
                <p class="text-xs mt-0.5" style="color: var(--text-tertiary);">@yield('page-subtitle', '')</p>
            </div>
            <div class="flex items-center gap-3">
                @yield('header-actions')
                <span class="text-xs hidden sm:block font-medium"
                    style="color: var(--text-tertiary);">{{ now()->format('d/m/Y H:i') }}</span>

                {{-- Theme Toggle --}}
                <button id="theme-toggle" class="btn-icon rounded-full"
                    style="background-color: var(--bg); border: 1px solid var(--border);" title="Cambiar tema"
                    onclick="toggleTheme()">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm" style="color: var(--text-tertiary);"></i>
                </button>
            </div>
        </header>

        {{-- Alerts --}}
        <div class="px-6 pt-4 flex-shrink-0">
            @if (session('success'))
                <div class="alert-success mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert-danger mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="alert-danger mb-4">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Page Content --}}
        <main class="flex-1 overflow-y-auto px-6 pb-8 pt-2">
            @if (isset($showExpiryBanner) && $showExpiryBanner)
                <div
                    class="mb-4 p-4 rounded-xl text-sm font-semibold bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-500 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Su suscripción vencerá en {{ max(0, $daysRemaining) }} días. Por favor registre su pago
                            para renovarla.</span>
                    </div>
                    @if ($user && ($user->role === 'owner' || $user->role === 'company_representative'))
                        <a href="{{ route('settings.subscription.index') }}"
                            class="text-xs underline hover:no-underline font-bold">
                            Renovar Ahora →
                        </a>
                    @endif
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    {{-- Theme Toggle Script --}}
    <script>
        function toggleTheme() {
            var html = document.documentElement;
            var isDark = html.classList.contains('dark');
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                document.getElementById('theme-icon').className = 'fa-solid fa-moon text-sm';
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                document.getElementById('theme-icon').className = 'fa-solid fa-sun text-sm';
            }
        }

        // Set icon on load
        document.addEventListener('DOMContentLoaded', function() {
            if (document.documentElement.classList.contains('dark')) {
                var icon = document.getElementById('theme-icon');
                if (icon) icon.className = 'fa-solid fa-sun text-sm';
            }
        });
    </script>

    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('main-sidebar');
            var overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        function closeSidebar() {
            document.getElementById('main-sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.add('hidden');
        }
    </script>

    {{-- Alpine Store: Payment Modal --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('paymentModal', {
                payment: null,
                open(data) {
                    this.payment = data;
                },
                close() {
                    this.payment = null;
                }
            });
        });
    </script>

    @stack('modals')
    @stack('scripts')
</body>

</html>
