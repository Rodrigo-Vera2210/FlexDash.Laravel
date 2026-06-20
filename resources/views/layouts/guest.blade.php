<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'FlexDash') }}</title>
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

    {{-- Vite compiled CSS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen flex flex-col sm:justify-center items-center py-10 px-4"
      style="background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif;">

    {{-- Logo --}}
    <div class="mb-6 flex flex-col items-center">
        <div class="w-16 h-16 rounded-2xl overflow-hidden mb-3 flex items-center justify-center bg-white p-1"
             style="box-shadow: var(--shadow-md);">
            <img src="{{ asset('build/assets/FlexDash.jpg') }}" class="w-full h-full object-cover rounded-xl" alt="FlexDash">
        </div>
        <h1 class="text-xl font-bold" style="color: var(--text-main);">FlexDash</h1>
        <p class="text-sm mt-0.5" style="color: var(--text-tertiary);">Sistema de gestión empresarial</p>
    </div>

    {{-- Card --}}
    <div class="w-full sm:max-w-md card-panel p-8">
        {{ $slot }}
    </div>

</body>
</html>
