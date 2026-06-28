{{-- Step 2 — Account & Contact Information --}}
@php $companyType = session('wizard_data.company_type', 'legal_entity'); @endphp

<form action="{{ route('registration.account') }}" method="POST" novalidate>
    @csrf

    <h2 class="text-xl font-bold text-gray-800 dark:text-slate-200 mb-1">Información de la Cuenta</h2>
    <p class="text-sm text-gray-500 mb-6">
        @if ($companyType === 'legal_entity')
            Ingresa los datos de contacto del representante de la empresa.
        @else
            Ingresa tus datos de contacto personales.
        @endif
    </p>

    <div class="space-y-5">

        {{-- Name --}}
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-slate-400 mb-1">
                {{ $companyType === 'legal_entity' ? 'Nombre Completo del Representante' : 'Nombre Completo' }}
                <span class="text-red-500" aria-hidden="true">*</span>
            </label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name"
                placeholder="{{ $companyType === 'legal_entity' ? 'ej. Juan Pérez' : 'ej. María García' }}"
                class="input-solid  outline-none transition
                       {{ $errors->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'focus:border-brand-teal focus:ring-1 focus:ring-brand-teal' }}"
                aria-describedby="{{ $errors->has('name') ? 'name_error' : '' }}">
            @error('name')
                <p id="name_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-400 mb-1">
                Correo Electrónico <span class="text-red-500" aria-hidden="true">*</span>
            </label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                autocomplete="email" placeholder="correo@ejemplo.com"
                class="input-solid outline-none transition
                       {{ $errors->has('email') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'focus:border-brand-teal focus:ring-1 focus:ring-brand-teal' }}"
                aria-describedby="{{ $errors->has('email') ? 'email_error' : '' }}">
            @error('email')
                <p id="email_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-slate-400 mb-1">
                Contraseña <span class="text-red-500" aria-hidden="true">*</span>
            </label>
            <div class="relative" x-data="{ show: false }">
                <input :type="show ? 'text' : 'password'" id="password" name="password" required autocomplete="new-password"
                    class="input-solid outline-none transition pr-10
                           {{ $errors->has('password') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'focus:border-brand-teal focus:ring-1 focus:ring-brand-teal' }}"
                    aria-describedby="password_hint {{ $errors->has('password') ? 'password_error' : '' }}">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 bg-transparent border-0 cursor-pointer">
                    <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            <p id="password_hint" class="text-gray-400 text-xs mt-1">
                Mínimo 8 caracteres con mayúscula, minúscula, número y símbolo.
            </p>
            @error('password')
                <p id="password_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password Confirmation --}}
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-slate-400 mb-1">
                Confirmar Contraseña <span class="text-red-500" aria-hidden="true">*</span>
            </label>
            <div class="relative" x-data="{ show: false }">
                <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                    autocomplete="new-password"
                    class="input-solid outline-none transition pr-10
                           {{ $errors->has('password_confirmation') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'focus:border-brand-teal focus:ring-1 focus:ring-brand-teal' }}">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 bg-transparent border-0 cursor-pointer">
                    <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            @error('password_confirmation')
                <p class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

    </div>

    {{-- Actions --}}
    <div class="mt-8 flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
        <a href="{{ route('registration.type') }}" class="text-brand-teal hover:underline text-sm font-medium">
            ← Atrás
        </a>
        <button type="submit"
            class="w-full sm:w-auto bg-brand-yellow hover:bg-brand-orange text-white font-semibold py-2 px-6 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-brand-yellow focus:ring-offset-2">
            Continuar →
        </button>
    </div>

</form>
