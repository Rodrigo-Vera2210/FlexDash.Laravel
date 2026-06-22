<x-guest-layout>
    <h2 class="text-xl font-bold mb-1" style="color: var(--text-main);">Verifica tu Correo</h2>
    <div class="mb-6 text-sm mt-3" style="color: var(--text-secondary);">
        ¡Gracias por registrarte! Antes de comenzar, ¿podrías verificar tu dirección de correo haciendo clic en el enlace que te acabamos de enviar? Si no lo recibiste, con gusto te enviaremos otro.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-semibold text-sm alert-success">
            Se ha enviado un nuevo enlace de verificación a la dirección de correo proporcionada durante el registro.
        </div>
    @endif

    <div class="mt-6 flex flex-col gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full justify-center py-3">
                <i class="fa-solid fa-paper-plane"></i>
                Reenviar Correo de Verificación
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-center text-sm font-semibold transition-colors py-2 rounded-md hover:bg-gray-100 dark:hover:bg-slate-800" style="color: var(--text-tertiary);">
                Cerrar Sesión
            </button>
        </form>
    </div>
</x-guest-layout>
