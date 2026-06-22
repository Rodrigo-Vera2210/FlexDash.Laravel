{{-- Step 5 — Email OTP Verification --}}
@php
    $userEmail = '';
    if (session()->has('registered_user_id')) {
        $user = \App\Models\User::find(session('registered_user_id'));
        $userEmail = $user?->email ?? '';
    }
@endphp

<div class="text-center">

    {{-- Icon --}}
    <div class="flex justify-center mb-5">
        <span class="flex items-center justify-center w-16 h-16 rounded-full bg-brand-teal/10 text-brand-teal">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
            </svg>
        </span>
    </div>

    <h2 class="text-xl font-bold mb-2" style="color: var(--text-main);">Revisa tu correo electrónico</h2>

    @if ($userEmail)
        <p class="text-sm mb-1" style="color: var(--text-secondary);">Enviamos un código de verificación de 6 dígitos a:</p>
        <p class="text-sm font-semibold mb-6" style="color: var(--text-main);">{{ $userEmail }}</p>
    @else
        <p class="text-sm mb-6" style="color: var(--text-secondary);">Enviamos un código de verificación de 6 dígitos a tu dirección de correo electrónico.</p>
    @endif

    {{-- OTP form --}}
    <form action="{{ route('registration.verify-otp') }}" method="POST" novalidate class="text-left">
        @csrf

        @error('otp_code')
            <div class="mb-4 p-3 bg-red-50 border border-red-300 rounded-lg">
                <p class="text-red-600 text-sm text-center" role="alert">{{ $message }}</p>
            </div>
        @enderror

        <div class="mb-6">
            <label for="otp_code" class="form-label text-center">
                Ingresa el código de verificación
            </label>
            <input
                type="text"
                id="otp_code"
                name="otp_code"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="000000"
                class="input-solid text-center text-2xl font-bold tracking-[0.5em] px-3 py-3
                       {{ $errors->has('otp_code') ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal' }}"
            >
        </div>

        <button
            type="submit"
            class="w-full bg-brand-yellow hover:bg-brand-orange text-white font-semibold py-2.5 px-6 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-brand-yellow focus:ring-offset-2 mb-4"
        >
            Verificar Cuenta
        </button>

    </form>

    {{-- Resend --}}
    <div class="border-t pt-4 mt-2" style="border-color: var(--border-light);">
        <p class="text-sm mb-2" style="color: var(--text-secondary);">¿No lo recibiste?</p>
        <form action="{{ route('registration.resend-otp') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="text-sm text-brand-teal hover:underline font-medium">
                Reenviar código de verificación
            </button>
        </form>
    </div>

</div>
