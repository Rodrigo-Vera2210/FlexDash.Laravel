<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Interno del Servidor (500) — FlexDash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0D1E36;
            color: #F9FAFB;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 text-center">
    <div class="max-w-md w-full bg-[#162538] border border-[#1e3352] p-8 rounded-2xl shadow-xl">
        <div class="flex justify-center mb-6">
            <span class="w-16 h-16 rounded-full bg-red-900/30 text-red-500 flex items-center justify-center text-3xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </span>
        </div>
        
        <h1 class="text-2xl font-bold mb-2">¡Ups! Algo salió mal</h1>
        <p class="text-sm text-gray-400 mb-6">
            Ha ocurrido un error interno en el servidor. Por favor, reporta el problema a soporte técnico para solucionarlo lo antes posible.
        </p>

        @php
            $errMsg = isset($exception) ? $exception->getMessage() : 'Error 500 inesperado';
            $errTrace = isset($exception) ? $exception->getTraceAsString() : '';
        @endphp

        <div class="space-y-3">
            @if (auth()->check())
                <a href="{{ route('tickets.create', [
                    'title' => 'Error 500: ' . substr($errMsg, 0, 50),
                    'description' => 'Ocurrió un error interno en la URL: ' . request()->fullUrl(),
                    'error_trace' => $errMsg . "\n\n" . substr($errTrace, 0, 2000)
                ]) }}" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg font-bold bg-[#E35205] text-white hover:bg-[#b83f04] transition-colors cursor-pointer border-0">
                    <i class="fa-solid fa-ticket-simple"></i>
                    Reportar a Soporte
                </a>
            @else
                <p class="text-xs text-gray-500">Inicia sesión para reportar este ticket.</p>
            @endif

            <a href="{{ url('/') }}" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg font-bold bg-gray-800 text-gray-300 hover:bg-gray-700 transition-colors cursor-pointer border-0">
                <i class="fa-solid fa-house"></i>
                Volver al Inicio
            </a>
        </div>
    </div>
</body>
</html>
