@extends('layouts.app')

@section('title', 'Verificar Código OTP')
@section('page-title', 'Verificación por Correo')
@section('page-subtitle', 'Ingresa el código enviado a tu correo para confirmar el cambio de contraseña')

@section('content')
<div class="mt-2 max-w-md mx-auto page-fade">
    <div class="card-panel p-6 text-center">

        {{-- Icon --}}
        <div class="flex justify-center mb-5">
            <span class="flex items-center justify-center w-16 h-16 rounded-full bg-[color:var(--primary-light)] text-[color:var(--primary)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
            </span>
        </div>

        <h2 class="text-xl font-bold mb-2 text-[color:var(--text-main)]">Revisa tu correo electrónico</h2>
        <p class="text-sm mb-1 text-[color:var(--text-secondary)]">Enviamos un código de verificación de 6 dígitos a:</p>
        <p class="text-sm font-semibold mb-6 text-[color:var(--text-main)]">{{ $userEmail }}</p>

        @if (session('status') === 'otp-resent')
            <div class="mb-4 p-3 bg-emerald-100 border border-emerald-300 rounded-lg">
                <p class="text-emerald-700 text-sm font-medium">
                    <i class="fa-solid fa-circle-check mr-1"></i>
                    Código reenviado exitosamente.
                </p>
            </div>
        @endif

        {{-- OTP form --}}
        <form action="{{ route('password.change.verify.submit') }}" method="POST" novalidate class="text-left">
            @csrf

            @error('otp_code')
                <div class="mb-4 p-3 bg-red-50 border border-red-300 rounded-lg">
                    <p class="text-red-600 text-sm text-center" role="alert">{{ $message }}</p>
                </div>
            @enderror

            @error('form')
                <div class="mb-4 p-3 bg-red-50 border border-red-300 rounded-lg">
                    <p class="text-red-600 text-sm text-center" role="alert">{{ $message }}</p>
                </div>
            @enderror

            <div class="mb-6">
                <label for="otp_code" class="form-label text-center block">
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
                    class="input-solid text-center text-2xl font-bold tracking-[0.5em] px-3 py-3 {{ $errors->has('otp_code') ? 'border-red-500' : '' }}"
                    autofocus
                >
            </div>

            <button
                type="submit"
                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg font-semibold bg-[#0A7EA5] text-white border-0 cursor-pointer hover:bg-[#075f7d] transition-colors mb-4"
            >
                <i class="fa-solid fa-check"></i>
                Verificar y Cambiar Contraseña
            </button>
        </form>

        {{-- Resend + back --}}
        <div class="border-t border-[color:var(--border-light)] pt-4 mt-2">
            <p class="text-sm mb-2 text-[color:var(--text-secondary)]">¿No lo recibiste?</p>
            <form action="{{ route('password.change.resend') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="text-sm text-[color:var(--primary)] hover:underline font-medium">
                    Reenviar código de verificación
                </button>
            </form>
            <div class="mt-3">
                <a href="{{ route('password.change') }}" class="text-sm text-[color:var(--text-tertiary)] hover:underline">
                    ← Volver a contraseñas
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
