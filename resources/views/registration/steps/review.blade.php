{{-- Step 4 — Review & Submit --}}
@php
    $data        = $wizardData ?? [];
    $isLegal     = ($data['company_type'] ?? '') === 'legal_entity';
    $companyLabel = $isLegal ? 'Persona Jurídica' : 'Persona Natural';
@endphp

<form action="{{ route('registration.review') }}" method="POST" novalidate>
    @csrf

    <h2 class="text-xl font-bold text-gray-800 mb-1">Revisa tu información</h2>
    <p class="text-sm text-gray-500 mb-6">Por favor confirma tus datos antes de enviarlos.</p>

    {{-- Summary card --}}
    <div class="rounded-xl border border-gray-200 divide-y divide-gray-100 mb-6 text-sm">

        {{-- Company Type --}}
        <div class="flex items-center justify-between px-4 py-3">
            <span class="text-gray-500">Tipo de Registro</span>
            <span class="font-medium text-gray-800">{{ $companyLabel }}</span>
        </div>

        {{-- Account info --}}
        <div class="flex items-center justify-between px-4 py-3">
            <span class="text-gray-500">Nombre</span>
            <span class="font-medium text-gray-800">{{ $data['name'] ?? '—' }}</span>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <span class="text-gray-500">Correo electrónico</span>
            <span class="font-medium text-gray-800">{{ $data['email'] ?? '—' }}</span>
        </div>

        @if ($isLegal)
            <div class="flex items-center justify-between px-4 py-3">
                <span class="text-gray-500">Nombre de la Empresa</span>
                <span class="font-medium text-gray-800">{{ $data['company_name'] ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between px-4 py-3">
                <span class="text-gray-500">RUC / NIT / RFC</span>
                <span class="font-medium text-gray-800">{{ $data['tax_id'] ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between px-4 py-3">
                <span class="text-gray-500">Dirección Legal</span>
                <span class="font-medium text-gray-800 text-right max-w-xs">{{ $data['legal_address'] ?? '—' }}</span>
            </div>
        @else
            <div class="flex items-center justify-between px-4 py-3">
                <span class="text-gray-500">Número de Identificación</span>
                <span class="font-medium text-gray-800">{{ $data['id_number'] ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between px-4 py-3">
                <span class="text-gray-500">Dirección</span>
                <span class="font-medium text-gray-800 text-right max-w-xs">{{ $data['address'] ?? '—' }}</span>
            </div>
        @endif

        <div class="flex items-center justify-between px-4 py-3">
            <span class="text-gray-500">Ubicación</span>
            <span class="font-medium text-gray-800">
                {{ implode(', ', array_filter([
                    $data['city'] ?? null,
                    $data['state_province'] ?? null,
                    $data['postal_code'] ?? null,
                    $data['country'] ?? null,
                ])) ?: '—' }}
            </span>
        </div>

    </div>

    {{-- Consent --}}
    <div class="flex items-start gap-3 mb-6">
        <input
            type="checkbox"
            id="consent"
            name="consent"
            value="1"
            required
            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-blue focus:ring-brand-teal"
        >
        <label for="consent" class="text-sm text-gray-600">
            Confirmo que la información anterior es precisa y acepto los
            <a href="#" class="text-brand-teal hover:underline">Términos de Servicio</a>
            y la
            <a href="#" class="text-brand-teal hover:underline">Política de Privacidad</a>.
        </label>
    </div>

    {{-- Actions --}}
    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
        <a href="{{ route('registration.entity.show') }}" class="text-brand-teal hover:underline text-sm font-medium">
            ← Editar Detalles
        </a>
        <button
            type="submit"
            class="w-full sm:w-auto bg-brand-yellow hover:bg-brand-orange text-white font-semibold py-2 px-8 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-brand-yellow focus:ring-offset-2"
        >
            Enviar Registro
        </button>
    </div>

</form>
