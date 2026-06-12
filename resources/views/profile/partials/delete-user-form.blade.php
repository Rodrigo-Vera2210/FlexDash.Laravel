<section class="space-y-4" x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
    <header class="border-b pb-3" style="border-color: var(--border-light);">
        <h2 class="text-base font-bold" style="color: var(--danger);">
            {{ __('Eliminar Cuenta') }}
        </h2>
        <p class="mt-1 text-sm" style="color: var(--text-tertiary);">
            {{ __('Una vez que tu cuenta sea eliminada, todos sus recursos y datos se borrarán permanentemente.') }}
        </p>
    </header>

    <button type="button" class="btn-primary" style="background-color: var(--danger);" @click="open = true">
        <i class="fa-solid fa-trash-can"></i>
        {{ __('Eliminar Cuenta') }}
    </button>

    {{-- MODAL ELIMINAR CUENTA --}}
    <div class="fixed inset-0 flex items-center justify-center z-50"
         style="background-color: rgba(13,30,54,0.65); backdrop-filter: blur(4px); display: none;"
         x-show="open"
         x-transition
         @click.self="open = false">
         
         <div class="card-panel w-full max-w-md overflow-hidden p-0" style="border-radius: 20px;">
             {{-- Modal Header --}}
             <div class="flex items-center justify-between px-6 py-5" style="border-bottom: 1px solid var(--border-light);">
                 <div class="flex items-center gap-3">
                     <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background-color: var(--danger-light); color: var(--danger);">
                         <i class="fa-solid fa-triangle-exclamation"></i>
                     </div>
                     <h3 class="text-base font-bold" style="color: var(--text-main);">¿Confirmar eliminación?</h3>
                 </div>
                 <button type="button" @click="open = false" class="btn-icon">
                     <i class="fa-solid fa-xmark"></i>
                 </button>
             </div>

             {{-- Modal Body --}}
             <form method="post" action="{{ route('profile.destroy') }}" class="p-6 space-y-4">
                 @csrf
                 @method('delete')

                 <p class="text-sm" style="color: var(--text-secondary);">
                     {{ __('Una vez que tu cuenta sea eliminada, todos sus recursos y datos se borrarán permanentemente. Por favor, introduce tu contraseña para confirmar la eliminación definitiva.') }}
                 </p>

                 <div>
                     <label for="password" class="form-label sr-only">{{ __('Contraseña') }}</label>
                     <input id="password" name="password" type="password" class="input-solid" placeholder="{{ __('Introduce tu contraseña') }}" required />
                     @if($errors->userDeletion->get('password'))
                         <p class="text-red-500 text-xs mt-1">{{ $errors->userDeletion->first('password') }}</p>
                     @endif
                 </div>

                 <div class="flex justify-end gap-2 pt-4 border-t" style="border-color: var(--border-light);">
                     <button type="button" @click="open = false" class="btn-outline">
                         {{ __('Cancelar') }}
                     </button>
                     <button type="submit" class="btn-primary" style="background-color: var(--danger);">
                         {{ __('Eliminar Cuenta') }}
                     </button>
                 </div>
             </form>
         </div>
    </div>
</section>
