<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h2 class="text-xl font-bold mb-1" style="color: var(--text-main);">Iniciar Sesión</h2>
    <p class="text-sm mb-6" style="color: var(--text-tertiary);">Ingresa a tu panel de administración</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="form-label">Correo electrónico</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required autofocus autocomplete="username"
                   class="input-solid"
                   placeholder="usuario@empresa.com">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs" />
        </div>

        {{-- Contraseña --}}
        <div>
            <label for="password" class="form-label">Contraseña</label>
            <input id="password"
                   type="password"
                   name="password"
                   required autocomplete="current-password"
                   class="input-solid"
                   placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs" />
        </div>

        {{-- Remember / Forgot --}}
        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded" style="accent-color: var(--primary);">
                <span class="text-sm font-medium" style="color: var(--text-secondary);">Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="text-sm font-semibold transition-colors"
                   style="color: var(--primary);">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-primary w-full justify-center py-3">
            <i class="fa-solid fa-right-to-bracket"></i>
            Iniciar Sesión
        </button>
    </form>

    {{-- Registro --}}
    <p class="text-center text-sm mt-6" style="color: var(--text-tertiary);">
        ¿No tienes cuenta?
        <a href="{{ route('registration.type') }}"
           class="font-bold ml-1 transition-colors"
           style="color: var(--primary);">
            Regístrate aquí
        </a>
    </p>
</x-guest-layout>
