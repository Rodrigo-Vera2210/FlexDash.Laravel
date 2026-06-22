@extends('layouts.app')

@section('title', 'Configuración de Facturación Electrónica')
@section('page-title', 'Facturación Electrónica')
@section('page-subtitle', 'Configura tu firma digital y los parámetros de emisión de comprobantes para el SRI (Ecuador)')

@section('content')
<div class="mt-2 max-w-5xl mx-auto page-fade space-y-6">
    
    @if(session('success'))
        <div class="p-4 rounded-lg bg-green-500/10 text-green-500 border border-green-500/20">
            <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-lg bg-red-500/10 text-red-500 border border-red-500/20">
            <i class="fa-solid fa-circle-xmark mr-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Columna Izquierda: Información y Límites --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="card-panel p-6 space-y-4">
                <h3 class="text-sm font-bold border-b pb-2" style="color: var(--text-main); border-color: var(--border-light);">Límites de tu Plan</h3>
                
                @php
                    $company = auth()->user()->company;
                    $maxCerts = $company->max_certificates;
                    $currentCerts = $certificates->count();
                @endphp

                <div class="space-y-3">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500 font-semibold">Certificados Activos:</span>
                        <span class="font-bold font-mono text-primary">{{ $currentCerts }} / {{ $maxCerts >= 9999 ? 'Ilimitados' : $maxCerts }}</span>
                    </div>
                    <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                        <div class="bg-primary h-full transition-all duration-300" style="width: {{ min(100, ($currentCerts / ($maxCerts ?: 1)) * 100) }}%"></div>
                    </div>
                </div>

                <div class="space-y-2 text-xs">
                    <div>
                        <span class="block text-gray-500 font-semibold">Plan de Suscripción:</span>
                        <span class="font-bold text-gray-400 capitalize">{{ $company->subscription_plan }}</span>
                    </div>
                </div>
            </div>

            <div class="card-panel p-6 space-y-2 text-xs text-gray-500">
                <h4 class="font-bold mb-1" style="color: var(--text-secondary);">Facturación en Ecuador (SRI)</h4>
                <p>La facturación se realiza en base al esquema fuera de línea obligatorio del SRI.</p>
                <p class="mt-2">Asegúrate de que los códigos de establecimiento y punto de emisión correspondan a tu RUC declarado.</p>
            </div>
        </div>

        {{-- Columna Derecha: Certificados y Parámetros --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Listado de Certificados --}}
            <div class="card-panel p-6 space-y-4">
                <h3 class="text-sm font-bold border-b pb-2" style="color: var(--text-main); border-color: var(--border-light);">Listado de Firmas Electrónicas</h3>

                @if($certificates->isEmpty())
                    <div class="text-center py-6 text-xs text-gray-500">
                        <i class="fa-solid fa-file-signature text-3xl mb-2 text-gray-400"></i>
                        <p>No tienes firmas electrónicas configuradas aún.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b" style="border-color: var(--border-light); color: var(--text-secondary);">
                                    <th class="py-3 font-semibold">Titular / RUC</th>
                                    <th class="py-3 font-semibold">Vencimiento</th>
                                    <th class="py-3 font-semibold text-center">Predeterminado</th>
                                    <th class="py-3 font-semibold text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($certificates as $cert)
                                    <tr class="border-b last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/20" style="border-color: var(--border-light);">
                                        <td class="py-3 space-y-1">
                                            <div class="font-bold" style="color: var(--text-main);">{{ $cert->owner_name }}</div>
                                            <div class="font-mono text-gray-400 text-[10px]">RUC/CI: {{ $cert->ruc ?: ($cert->cedula ?: 'No especificado') }}</div>
                                        </td>
                                        <td class="py-3">
                                            <div class="font-mono">{{ $cert->certificate_expires_at->format('d/m/Y') }}</div>
                                            <div>
                                                @if($cert->certificate_expires_at->isPast())
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-500/10 text-red-500 border border-red-500/20">Expirado</span>
                                                @else
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-500/10 text-green-500 border border-green-500/20">Vigente</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-3 text-center">
                                            @if($cert->is_default)
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-green-500/10 text-green-500 border border-green-500/20">
                                                    <i class="fa-solid fa-circle-check"></i> Activo
                                                </span>
                                            @else
                                                <form method="POST" action="{{ route('billing.settings.certificates.default', $cert->id) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-xs text-gray-400 hover:text-primary transition-colors">
                                                        Marcar por defecto
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                        <td class="py-3 text-right">
                                            <form method="POST" action="{{ route('billing.settings.certificates.destroy', $cert->id) }}" onsubmit="return confirm('¿Estás seguro de eliminar esta firma electrónica?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 transition-colors p-1" title="Eliminar Firma">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Formulario para Subir Nueva Firma --}}
            @if($currentCerts < $maxCerts)
                <div class="card-panel p-6 space-y-4">
                    <h3 class="text-sm font-bold border-b pb-2" style="color: var(--text-main); border-color: var(--border-light);">Subir Nueva Firma Electrónica</h3>

                    <form method="POST" action="{{ route('billing.settings.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        {{-- Preservar los campos de parámetros ocultos para no pisar --}}
                        <input type="hidden" name="establishment" value="{{ $config->establishment ?? '001' }}">
                        <input type="hidden" name="emission_point" value="{{ $config->emission_point ?? '001' }}">
                        <input type="hidden" name="last_sequence" value="{{ $config->last_sequence ?? '0' }}">
                        <input type="hidden" name="environment" value="{{ $config->environment ?? 'pruebas' }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="certificate" class="form-label text-xs">Archivo Certificado (.p12) <span class="text-red-500">*</span></label>
                                <input type="file" name="certificate" id="certificate" accept=".p12" required class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer">
                            </div>

                            <div>
                                <label for="password" class="form-label text-xs">Contraseña de Firma <span class="text-red-500">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" name="password" id="password" required class="input-solid" placeholder="••••••••">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_default" id="is_default" value="1" class="rounded border-gray-300 text-primary focus:ring-primary">
                            <label for="is_default" class="text-xs text-gray-500">Establecer esta firma como predeterminada</label>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="btn-primary py-1.5 px-4 text-xs">
                                <i class="fa-solid fa-upload mr-1"></i> Cargar Firma
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="p-4 rounded-lg bg-amber-500/10 text-amber-500 border border-amber-500/20 text-xs">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> Ha alcanzado el número máximo de firmas permitidas por tu plan de suscripción (Límite: {{ $maxCerts }}). Para agregar más firmas, por favor mejora tu plan.
                </div>
            @endif

            {{-- Parámetros Generales de Emisión --}}
            <div class="card-panel p-6">
                <form method="POST" action="{{ route('billing.settings.store') }}" class="space-y-6">
                    @csrf
                    
                    <h3 class="text-sm font-bold border-b pb-2" style="color: var(--text-main); border-color: var(--border-light);">Parámetros de Emisión</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="establishment" class="form-label">Establecimiento <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-building-user"></i>
                                <input type="text" name="establishment" id="establishment" maxlength="3" value="{{ old('establishment', $config->establishment ?? '001') }}" required class="input-solid" placeholder="Ej: 001">
                            </div>
                            @error('establishment')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="emission_point" class="form-label">Punto de Emisión <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-desktop"></i>
                                <input type="text" name="emission_point" id="emission_point" maxlength="3" value="{{ old('emission_point', $config->emission_point ?? '001') }}" required class="input-solid" placeholder="Ej: 001">
                            </div>
                            @error('emission_point')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="last_sequence" class="form-label">Secuencia Inicial <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-arrow-down-1-9"></i>
                                <input type="number" name="last_sequence" id="last_sequence" min="0" value="{{ old('last_sequence', $config->last_sequence ?? '0') }}" required class="input-solid" placeholder="Ej: 0">
                            </div>
                            @error('last_sequence')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="environment" class="form-label">Ambiente SRI <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-server"></i>
                                <select name="environment" id="environment" class="input-solid" required>
                                    <option value="pruebas" {{ old('environment', $config->environment ?? 'pruebas') === 'pruebas' ? 'selected' : '' }}>Pruebas (Sandbox)</option>
                                    <option value="produccion" {{ old('environment', $config->environment ?? 'pruebas') === 'produccion' ? 'selected' : '' }}>Producción</option>
                                </select>
                            </div>
                            @error('environment')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="border-t pt-4 flex justify-end gap-2" style="border-color: var(--border-light);">
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar Parámetros
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
