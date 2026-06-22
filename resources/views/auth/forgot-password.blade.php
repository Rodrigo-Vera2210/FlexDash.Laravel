<x-guest-layout>
    <div class="mb-6 text-sm" style="color: var(--text-secondary);">
        ¿Olvidaste tu contraseña? No hay problema. Simplemente dinos tu dirección de correo electrónico y te enviaremos un enlace de restablecimiento de contraseña por correo electrónico que te permitirá elegir una nueva.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="form-label">Correo electrónico</label>
            <input id="email" 
                   type="email" 
                   name="email" 
                   value="{{ old('email') }}" 
                   required autofocus 
                   class="input-solid" 
                   placeholder="usuario@empresa.com">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs" />
        </div>

        <div class="pt-2">
            <button type="submit" class="btn-primary w-full justify-center py-3">
                <i class="fa-solid fa-paper-plane"></i>
                Enviar enlace para restablecer contraseña
            </button>
        </div>
    </form>
</x-guest-layout>
