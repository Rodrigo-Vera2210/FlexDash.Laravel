<section class="space-y-4">

    <header class="border-b border-[color:var(--border-light)] pb-3">
        <h2 class="text-base font-bold text-[color:var(--text-main)]">
            {{ __('Actualizar Contraseña') }}
        </h2>
        <p class="mt-1 text-sm text-[color:var(--text-tertiary)]">
            {{ __('Asegúrate de que tu cuenta esté usando una contraseña larga y aleatoria para mantenerla segura.') }}
        </p>
    </header>

    @if (session('status') === 'password-updated')
        <div class="p-3 bg-emerald-100 border-l-4 border-emerald-600 rounded-md">
            <p class="text-emerald-800 text-sm font-semibold">
                <i class="fa-solid fa-circle-check mr-1"></i>
                Contraseña actualizada exitosamente.
            </p>
        </div>
    @endif

    <div class="pt-2">
        <a href="{{ route('password.change') }}"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg font-semibold bg-[#0A7EA5] text-white no-underline hover:bg-[#075f7d] transition-colors">
            <i class="fa-solid fa-key"></i>
            <span>{{ __('Cambiar Contraseña') }}</span>
        </a>
    </div>

</section>
