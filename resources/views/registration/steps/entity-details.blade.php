<form action="{{ route('registration.entity') }}" method="POST" novalidate>
    @csrf
    <input type="hidden" name="company_type" value="{{ session('wizard_data.company_type', 'legal_entity') }}">

    <h2 class="text-xl font-bold text-gray-800 mb-1">Detalles de Persona Jurídica</h2>
    <p class="text-sm text-gray-500 mb-6">Ingresa la información legal de tu empresa. Todos los campos son obligatorios.
    </p>

    <div class="space-y-5">

        {{-- Company Name --}}
        <div>
            <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-slate-400 mb-1">
                Nombre de la Empresa <span class="text-red-500" aria-hidden="true">*</span>
            </label>
            <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required
                autocomplete="organization"
                class="input-solid outline-none transition
                    {{ $errors->has('company_name') ? 'border-red-500' : '' }}"
                aria-describedby="{{ $errors->has('company_name') ? 'company_name_error' : '' }}">
            @error('company_name')
                <p id="company_name_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Tax ID / RFC --}}
        <div>
            <label for="tax_id" class="block text-sm font-medium text-gray-700 dark:text-slate-400 mb-1">
                RUC / NIT / RFC <span class="text-red-500" aria-hidden="true">*</span>
            </label>
            <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id') }}" required autocomplete="off"
                class="input-solid outline-none transition
                    {{ $errors->has('tax_id') ? 'border-red-500' : '' }}"
                aria-describedby="{{ $errors->has('tax_id') ? 'tax_id_error' : '' }}">
            @error('tax_id')
                <p id="tax_id_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Legal Address --}}
        <div>
            <label for="legal_address" class="block text-sm font-medium text-gray-700 dark:text-slate-400 mb-1">
                Dirección Legal <span class="text-red-500" aria-hidden="true">*</span>
            </label>
            <input type="text" id="legal_address" name="legal_address" value="{{ old('legal_address') }}" required
                autocomplete="street-address"
                class="input-solid outline-none transition
                    {{ $errors->has('legal_address') ? 'border-red-500' : '' }}"
                aria-describedby="{{ $errors->has('legal_address') ? 'legal_address_error' : '' }}">
            @error('legal_address')
                <p id="legal_address_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- City + State/Province --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- City --}}
            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 dark:text-slate-400 mb-1">
                    Ciudad <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input type="text" id="city" name="city" value="{{ old('city') }}" required
                    autocomplete="address-level2"
                    class="input-solid outline-none transition
                        {{ $errors->has('city') ? 'border-red-500' : '' }}"
                    aria-describedby="{{ $errors->has('city') ? 'city_error' : '' }}">
                @error('city')
                    <p id="city_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- State / Province --}}
            <div>
                <label for="state_province" class="block text-sm font-medium text-gray-700 dark:text-slate-400 mb-1">
                    Estado / Provincia <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input type="text" id="state_province" name="state_province" value="{{ old('state_province') }}"
                    required autocomplete="address-level1"
                    class="input-solid outline-none transition
                        {{ $errors->has('state_province') ? 'border-red-500' : '' }}"
                    aria-describedby="{{ $errors->has('state_province') ? 'state_province_error' : '' }}">
                @error('state_province')
                    <p id="state_province_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Postal Code + Country --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Postal Code --}}
            <div>
                <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-slate-400 mb-1">
                    Código Postal <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" required
                    autocomplete="postal-code"
                    class="input-solid outline-none transition
                        {{ $errors->has('postal_code') ? 'border-red-500' : '' }}"
                    aria-describedby="{{ $errors->has('postal_code') ? 'postal_code_error' : '' }}">
                @error('postal_code')
                    <p id="postal_code_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Country --}}
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 dark:text-slate-400 mb-1">
                    País <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input type="text" id="country" name="country" value="{{ old('country') }}" required
                    autocomplete="country-name"
                    class="input-solid outline-none transition
                        {{ $errors->has('country') ? 'border-red-500' : '' }}"
                    aria-describedby="{{ $errors->has('country') ? 'country_error' : '' }}">
                @error('country')
                    <p id="country_error" class="text-red-600 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

    </div>

    {{-- Actions --}}
    <div class="mt-8 flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
        <a href="/register/account" class="text-brand-teal hover:underline text-sm font-medium">
            ← Atrás
        </a>

        <button type="submit"
            class="w-full sm:w-auto bg-brand-yellow hover:bg-brand-orange text-white font-semibold py-2 px-6 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-brand-yellow focus:ring-offset-2">
            Continuar →
        </button>
    </div>

</form>
