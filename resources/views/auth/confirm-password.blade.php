<x-guest-layout>
    <h2 class="text-xl font-bold mb-1" style="color: var(--text-main);">Confirmar Contraseña</h2>
    <p class="text-sm mb-6" style="color: var(--text-tertiary);">Esta es un área segura. Confirma tu contraseña antes de continuar.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <!-- Password -->
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

        <div class="pt-2">
            <button type="submit" class="btn-primary w-full justify-center py-3">
                <i class="fa-solid fa-lock"></i>
                Confirmar
            </button>
        </div>
    </form>
</x-guest-layout>
