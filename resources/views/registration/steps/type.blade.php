{{-- Step 1 — Select Registration Type --}}
<div>
    <h2 class="text-xl font-bold mb-1" style="color: var(--text-main);">Crea tu cuenta</h2>
    <p class="text-sm mb-8" style="color: var(--text-tertiary);">Selecciona cómo deseas registrarte en FlexDash.</p>

    <form action="{{ route('registration.type.post') }}" method="POST" novalidate>
        @csrf

        @error('company_type')
            <div class="mb-4 p-3 rounded-xl text-sm font-medium"
                 style="background-color: rgba(220,38,38,0.08); border: 1px solid rgba(220,38,38,0.25); color: #DC2626;"
                 role="alert">
                {{ $message }}
            </div>
        @enderror

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">

            {{-- Legal Entity --}}
            <label for="type_legal"
                   class="relative flex flex-col items-start gap-3 p-5 rounded-xl cursor-pointer transition-all"
                   style="border: 2px solid var(--border);"
                   onmouseover="this.style.borderColor='var(--primary)'"
                   onmouseout="if(!document.getElementById('type_legal').checked) this.style.borderColor='var(--border)'">
                <input type="radio" id="type_legal" name="company_type" value="legal_entity"
                       class="sr-only peer"
                       {{ old('company_type') === 'legal_entity' ? 'checked' : '' }}
                       onchange="highlightCard(this)">
                <span class="flex items-center justify-center w-11 h-11 rounded-xl transition-all"
                      style="background-color: var(--primary-light); color: var(--primary);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                </span>
                <span>
                    <span class="block text-sm font-bold mb-1" style="color: var(--text-main);">Persona Jurídica</span>
                    <span class="block text-xs leading-relaxed" style="color: var(--text-tertiary);">Para empresas, corporaciones y negocios con RUC/NIT.</span>
                </span>
                <span class="absolute top-3 right-3 hidden peer-checked:flex items-center justify-center w-5 h-5 rounded-full"
                      style="background-color: var(--primary);">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="white" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            </label>

            {{-- Natural Person --}}
            <label for="type_natural"
                   class="relative flex flex-col items-start gap-3 p-5 rounded-xl cursor-pointer transition-all"
                   style="border: 2px solid var(--border);"
                   onmouseover="this.style.borderColor='var(--primary)'"
                   onmouseout="if(!document.getElementById('type_natural').checked) this.style.borderColor='var(--border)'">
                <input type="radio" id="type_natural" name="company_type" value="natural_person"
                       class="sr-only peer"
                       {{ old('company_type') === 'natural_person' ? 'checked' : '' }}
                       onchange="highlightCard(this)">
                <span class="flex items-center justify-center w-11 h-11 rounded-xl transition-all"
                      style="background-color: rgba(242,169,0,0.10); color: #D97706;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </span>
                <span>
                    <span class="block text-sm font-bold mb-1" style="color: var(--text-main);">Persona Natural</span>
                    <span class="block text-xs leading-relaxed" style="color: var(--text-tertiary);">Para profesionales independientes y trabajadores autónomos.</span>
                </span>
                <span class="absolute top-3 right-3 hidden peer-checked:flex items-center justify-center w-5 h-5 rounded-full"
                      style="background-color: var(--primary);">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="white" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            </label>

        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">
                Continuar →
            </button>
        </div>

    </form>
</div>

<script>
function highlightCard(radio) {
    document.querySelectorAll('label[for]').forEach(label => {
        label.style.borderColor = 'var(--border)';
    });
    if (radio.id) {
        var label = document.querySelector('label[for="' + radio.id + '"]');
        if (label) label.style.borderColor = 'var(--primary)';
    }
}
// On load, highlight selected
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[type="radio"]:checked').forEach(function(r) { highlightCard(r); });
});
</script>
