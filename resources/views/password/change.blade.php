@extends('layouts.app')

@section('title', 'Cambiar Contraseña')
@section('page-title', 'Cambiar Contraseña')
@section('page-subtitle', 'Se enviará un código de verificación a tu correo para confirmar el cambio')

@section('content')
    <div class="mt-2 max-w-lg mx-auto page-fade">
        <div class="card-panel p-6">

            {{-- Header --}}
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[color:var(--border-light)]">
                <div
                    class="w-10 h-10 rounded-xl flex items-center justify-center bg-[color:var(--primary-light)] text-[color:var(--primary)]">
                    <i class="fa-solid fa-key text-lg"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-[color:var(--text-main)]">Cambiar Contraseña</h2>
                    <p class="text-sm text-[color:var(--text-tertiary)]">Recibirás un código OTP para confirmar el cambio</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-300 rounded-lg">
                    @foreach ($errors->all() as $error)
                        <p class="text-red-600 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.change.submit') }}" class="space-y-4">
                @csrf

                {{-- Contraseña actual --}}
                <div>
                    <label for="current_password" class="form-label">Contraseña Actual</label>
                    <div class="relative" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" id="current_password" name="current_password"
                            class="input-solid mt-1 pr-10 {{ $errors->has('current_password') ? 'border-red-500' : '' }}"
                            placeholder="Tu contraseña actual" autocomplete="current-password" required autofocus />
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 bg-transparent border-0 cursor-pointer">
                            <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <hr class="border-[color:var(--border-light)]">

                {{-- Nueva contraseña --}}
                <div>
                    <label for="new_password" class="form-label">Nueva Contraseña</label>
                    <div class="relative" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" id="new_password" name="new_password"
                            class="input-solid mt-1 pr-10 {{ $errors->has('new_password') ? 'border-red-500' : '' }}"
                            placeholder="Mínimo 8 caracteres" autocomplete="new-password" minlength="8" required />
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 bg-transparent border-0 cursor-pointer">
                            <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <p class="text-gray-400 text-xs mt-1">Requisitos mínimos: Al menos 8 caracteres de longitud.</p>
                    @error('new_password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirmar nueva contraseña --}}
                <div>
                    <label for="new_password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
                    <div class="relative" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" id="new_password_confirmation" name="new_password_confirmation"
                            class="input-solid mt-1 pr-10" placeholder="Repite la nueva contraseña" autocomplete="new-password"
                            minlength="8" required />
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 bg-transparent border-0 cursor-pointer">
                            <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('profile.edit') }}" class="btn-outline">
                        <i class="fa-solid fa-arrow-left"></i>
                        Cancelar
                    </a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg font-semibold bg-[#0A7EA5] text-white border-0 cursor-pointer hover:bg-[#075f7d] transition-colors">
                        <i class="fa-solid fa-envelope"></i>
                        Enviar Código OTP
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
