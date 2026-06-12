<form action="{{ route('registration.entity') }}" method="POST" novalidate>
    @csrf
    <input type="hidden" name="company_type" value="{{ session('wizard_data.company_type', 'natural_person') }}">

    <h2 class="text-xl font-bold text-gray-800 mb-1">Detalles Personales</h2>
    <p class="text-sm text-gray-500 mb-6">Ingresa tu información personal. Todos los campos son obligatorios.</p>

    <div class="space-y-5">

        {{-- Full Name --}}
        <div>
            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">
                Nombre Completo <span class="text-red-500" aria-hidden="true">*</span>
            </label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                value="{{ old('full_name') }}"
                required
                autocomplete="name"
                class="w-full border border-gray-300 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal rounded-lg px-3 py-2 text-sm outline-none transition
                    {{ $errors->has('full_name') ? 'border-red-500' : '' }}"
                aria-describedby="{{ $errors->has('full_name') ? 'full_name_error' : '' }}"
            >
            @error('full_name')
                <p id="full_name_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- ID Number --}}
        <div>
            <label for="id_number" class="block text-sm font-medium text-gray-700 mb-1">
                Número de Identificación / DNI / Cédula <span class="text-red-500" aria-hidden="true">*</span>
            </label>
            <input
                type="text"
                id="id_number"
                name="id_number"
                value="{{ old('id_number') }}"
                required
                autocomplete="off"
                class="w-full border border-gray-300 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal rounded-lg px-3 py-2 text-sm outline-none transition
                    {{ $errors->has('id_number') ? 'border-red-500' : '' }}"
                aria-describedby="{{ $errors->has('id_number') ? 'id_number_error' : '' }}"
            >
            @error('id_number')
                <p id="id_number_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Address --}}
        <div>
            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                Dirección <span class="text-red-500" aria-hidden="true">*</span>
            </label>
            <input
                type="text"
                id="address"
                name="address"
                value="{{ old('address') }}"
                required
                autocomplete="street-address"
                class="w-full border border-gray-300 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal rounded-lg px-3 py-2 text-sm outline-none transition
                    {{ $errors->has('address') ? 'border-red-500' : '' }}"
                aria-describedby="{{ $errors->has('address') ? 'address_error' : '' }}"
            >
            @error('address')
                <p id="address_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- City + State/Province --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- City --}}
            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                    Ciudad <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input
                    type="text"
                    id="city"
                    name="city"
                    value="{{ old('city') }}"
                    required
                    autocomplete="address-level2"
                    class="w-full border border-gray-300 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal rounded-lg px-3 py-2 text-sm outline-none transition
                        {{ $errors->has('city') ? 'border-red-500' : '' }}"
                    aria-describedby="{{ $errors->has('city') ? 'city_error' : '' }}"
                >
                @error('city')
                    <p id="city_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- State / Province --}}
            <div>
                <label for="state_province" class="block text-sm font-medium text-gray-700 mb-1">
                    Estado / Provincia <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input
                    type="text"
                    id="state_province"
                    name="state_province"
                    value="{{ old('state_province') }}"
                    required
                    autocomplete="address-level1"
                    class="w-full border border-gray-300 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal rounded-lg px-3 py-2 text-sm outline-none transition
                        {{ $errors->has('state_province') ? 'border-red-500' : '' }}"
                    aria-describedby="{{ $errors->has('state_province') ? 'state_province_error' : '' }}"
                >
                @error('state_province')
                    <p id="state_province_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Postal Code + Country --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Postal Code --}}
            <div>
                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">
                    Código Postal <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input
                    type="text"
                    id="postal_code"
                    name="postal_code"
                    value="{{ old('postal_code') }}"
                    required
                    autocomplete="postal-code"
                    class="w-full border border-gray-300 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal rounded-lg px-3 py-2 text-sm outline-none transition
                        {{ $errors->has('postal_code') ? 'border-red-500' : '' }}"
                    aria-describedby="{{ $errors->has('postal_code') ? 'postal_code_error' : '' }}"
                >
                @error('postal_code')
                    <p id="postal_code_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Country --}}
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">
                    País <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input
                    type="text"
                    id="country"
                    name="country"
                    value="{{ old('country') }}"
                    required
                    autocomplete="country-name"
                    class="w-full border border-gray-300 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal rounded-lg px-3 py-2 text-sm outline-none transition
                        {{ $errors->has('country') ? 'border-red-500' : '' }}"
                    aria-describedby="{{ $errors->has('country') ? 'country_error' : '' }}"
                >
                @error('country')
                    <p id="country_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

    </div>

    {{-- Actions --}}
    <div class="mt-8 flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
        <a
            href="/register/account"
            class="text-brand-teal hover:underline text-sm font-medium"
        >
            ← Atrás
        </a>

        <button
            type="submit"
            class="w-full sm:w-auto bg-brand-yellow hover:bg-brand-orange text-white font-semibold py-2 px-6 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-brand-yellow focus:ring-offset-2"
        >
            Continuar →
        </button>
    </div>

</form>
