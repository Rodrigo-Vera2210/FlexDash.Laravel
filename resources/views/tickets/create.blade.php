@extends('layouts.app')

@section('title', 'Reportar Nuevo Ticket')
@section('page-title', 'Reportar Nuevo Ticket')
@section('page-subtitle', 'Describe detalladamente el problema y adjunta la evidencia respectiva')

@section('content')
    <div class="mt-2 max-w-2xl mx-auto page-fade">
        <div class="card-panel p-6">
            <h2 class="text-base font-bold text-[color:var(--text-main)] mb-6 border-b border-[color:var(--border-light)] pb-3">
                Detalle del Problema
            </h2>

            @if ($errors->any())
                <div class="mb-5 p-4 rounded-xl text-sm font-medium"
                    style="background-color: rgba(220, 38, 38, 0.08); border: 1px solid rgba(220, 38, 38, 0.25); color: #DC2626;">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                {{-- Título --}}
                <div>
                    <label for="title" class="form-label">Título del Ticket</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $prefilledTitle) }}"
                        class="input-solid mt-1 {{ $errors->has('title') ? 'border-red-500' : '' }}"
                        placeholder="Ej. Error al guardar factura electrónica" required autofocus />
                </div>

                {{-- Descripción --}}
                <div>
                    <label for="description" class="form-label">Descripción del Problema</label>
                    <textarea id="description" name="description" rows="5"
                        class="input-solid mt-1 {{ $errors->has('description') ? 'border-red-500' : '' }}"
                        placeholder="Describe el error paso a paso o lo que estabas haciendo cuando ocurrió..." required>{{ old('description', $prefilledDescription) }}</textarea>
                </div>

                {{-- Evidencia (Múltiple) --}}
                <div>
                    <label class="form-label">Evidencia de Imagen (Mínimo 1 requerida)</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed border-[color:var(--border)] rounded-xl hover:border-[color:var(--primary)] transition-colors relative">
                        <div class="space-y-1 text-center">
                            <i class="fa-regular fa-image text-3xl text-[color:var(--text-tertiary)] mb-2 block"></i>
                            <div class="flex text-sm text-[color:var(--text-secondary)]">
                                <label for="evidence" class="relative cursor-pointer rounded-md font-semibold text-[color:var(--primary)] hover:underline">
                                    <span>Subir imágenes de evidencia</span>
                                    <input id="evidence" name="evidence[]" type="file" class="sr-only" multiple accept="image/*" required>
                                </label>
                            </div>
                            <p class="text-xs text-[color:var(--text-tertiary)]">PNG, JPG, GIF hasta 10MB</p>
                        </div>
                    </div>
                    <div id="file-list" class="mt-3 text-xs text-[color:var(--text-secondary)] space-y-1"></div>
                </div>

                {{-- Error Trace (Optional / Prefilled) --}}
                @if ($prefilledTrace || old('error_trace'))
                    <div>
                        <label for="error_trace" class="form-label">Detalles del Error (Traza técnica)</label>
                        <textarea id="error_trace" name="error_trace" rows="5"
                            class="input-solid mt-1 font-mono text-xs bg-slate-900 text-slate-300 border-0"
                            placeholder="Detalles del error o stack trace..." readonly>{{ old('error_trace', $prefilledTrace) }}</textarea>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-between pt-2 border-t border-[color:var(--border-light)]">
                    <a href="{{ route('tickets.index') }}" class="btn-outline">
                        <i class="fa-solid fa-arrow-left"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-paper-plane"></i>
                        Enviar Reporte
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('evidence').addEventListener('change', function(e) {
            const fileList = document.getElementById('file-list');
            fileList.innerHTML = '';
            
            if (this.files.length > 0) {
                const title = document.createElement('p');
                title.className = 'font-semibold text-[color:var(--text-main)]';
                title.innerText = `Archivos seleccionados (${this.files.length}):`;
                fileList.appendChild(title);
                
                const ul = document.createElement('ul');
                ul.className = 'list-disc pl-4';
                for (let i = 0; i < this.files.length; i++) {
                    const li = document.createElement('li');
                    li.innerText = `${this.files[i].name} (${(this.files[i].size / 1024 / 1024).toFixed(2)} MB)`;
                    ul.appendChild(li);
                }
                fileList.appendChild(ul);
            }
        });
    </script>
@endsection
