@extends('layouts.app')

@section('title', 'Nuevo Producto')
@section('page-title', 'Registrar Nuevo Producto')
@section('page-subtitle', 'Agrega un nuevo producto al catálogo del sistema')

@section('content')
<div class="mt-2 max-w-4xl mx-auto page-fade" x-data="productQuickAdd()">
    <div class="mb-4">
        <a href="{{ route('products.index') }}" class="btn-outline">
            <i class="fa-solid fa-arrow-left"></i>
            Volver al listado
        </a>
    </div>

    <div class="card-panel p-6">
        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Sección General --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold border-b pb-2" style="color: var(--text-main); border-color: var(--border-light);">Información Básica</h3>

                    <div>
                        <label for="code" class="form-label">Código de Producto <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-barcode"></i>
                            <input type="text" name="code" id="code" value="{{ old('code') }}" class="input-solid" required placeholder="Ej: PROD-001">
                        </div>
                        @error('code')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="form-label">Nombre del Producto <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-box"></i>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="input-solid" required placeholder="Ej: Laptop Dell Latitude">
                        </div>
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="category_id" class="form-label flex justify-between items-center">
                                <span>Categoría <span class="text-red-500">*</span></span>
                                <button type="button" @click="openQuickCategoryModal()" class="text-xs font-bold hover:underline" style="color: var(--primary);">
                                    <i class="fa-solid fa-plus-circle"></i> Nueva
                                </button>
                            </label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-tags"></i>
                                <select name="category_id" id="category_id" class="input-solid" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="unit" class="form-label">Unidad de Medida <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-scale-balanced"></i>
                                <input type="text" name="unit" id="unit" value="{{ old('unit', 'UND') }}" class="input-solid" required placeholder="Ej: UND, KG, LTS">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="form-label">Descripción</label>
                        <textarea name="description" id="description" rows="3" class="input-solid" placeholder="Detalles o especificaciones técnicas del producto...">{{ old('description') }}</textarea>
                    </div>
                </div>

                {{-- Sección Financiera e Imagen --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold border-b pb-2" style="color: var(--text-main); border-color: var(--border-light);">Precios, Stock e Imagen</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="cost" class="form-label">Costo Promedio (S/) <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-money-bill-1-wave"></i>
                                <input type="number" name="cost" id="cost" step="0.01" min="0" value="{{ old('cost', '0.00') }}" class="input-solid" required>
                            </div>
                        </div>
                        <div>
                            <label for="price" class="form-label">Precio de Venta (S/) <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                                <input type="number" name="price" id="price" step="0.01" min="0" value="{{ old('price', '0.00') }}" class="input-solid" required>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="tax_id" class="form-label flex justify-between items-center">
                                <span>Impuesto Aplicable <span class="text-red-500">*</span></span>
                                <button type="button" @click="openQuickTaxModal()" class="text-xs font-bold hover:underline" style="color: var(--primary);">
                                    <i class="fa-solid fa-plus-circle"></i> Nuevo
                                </button>
                            </label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-percent"></i>
                                <select name="tax_id" id="tax_id" class="input-solid" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach($taxes as $tax)
                                        <option value="{{ $tax->id }}" {{ old('tax_id') == $tax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ number_format($tax->rate, 0) }}%)</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="minimum_stock" class="form-label">Stock Mínimo (Alerta) <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <input type="number" name="minimum_stock" id="minimum_stock" step="0.01" min="0" value="{{ old('minimum_stock', '5.00') }}" class="input-solid" required>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="image" class="form-label">Imagen del Producto</label>
                        <div class="mt-1 flex items-center gap-4">
                            <input type="file" name="image" id="image" accept="image/*" class="text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" style="color: var(--text-secondary);">
                        </div>
                        <p class="text-xs mt-1" style="color: var(--text-tertiary);">Formato JPG, PNG o WEBP. Máx 2MB.</p>
                        @error('image')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="border-t pt-4 flex justify-end gap-2" style="border-color: var(--border-light);">
                <a href="{{ route('products.index') }}" class="btn-outline">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar Producto
                </button>
            </div>
        </form>
    </div>

    {{-- MODAL: QUICK CATEGORY --}}
    <div 
        x-show="showCategoryModal" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="display: none;"
    >
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-6 overflow-hidden transform transition-all page-fade">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold" style="color: var(--text-main);">Crear Categoría Rápida</h3>
                <button type="button" @click="showCategoryModal = false" class="btn-icon"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="form-label">Nombre de Categoría <span class="text-red-500">*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-tag"></i>
                        <input type="text" x-model="newCat.name" class="input-solid" placeholder="Ej: Bebidas, Limpieza">
                    </div>
                </div>
                <div>
                    <label class="form-label">Descripción</label>
                    <textarea x-model="newCat.description" class="input-solid" rows="3" placeholder="Detalle o notas de la categoría..."></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t" style="border-color: var(--border-light);">
                <button type="button" @click="showCategoryModal = false" class="btn-outline">Cancelar</button>
                <button type="button" @click="submitQuickCategory()" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL: QUICK TAX --}}
    <div 
        x-show="showTaxModal" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="display: none;"
    >
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-6 overflow-hidden transform transition-all page-fade">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold" style="color: var(--text-main);">Crear Impuesto Rápido</h3>
                <button type="button" @click="showTaxModal = false" class="btn-icon"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="form-label">Nombre del Impuesto <span class="text-red-500">*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-percent"></i>
                        <input type="text" x-model="newTax.name" class="input-solid" placeholder="Ej: IVA, IGV">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Código Sunat/Fiscal <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-barcode"></i>
                            <input type="text" x-model="newTax.code" class="input-solid" placeholder="Ej: 1000">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Tasa (%) <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-calculator"></i>
                            <input type="number" x-model="newTax.rate" step="0.01" min="0" max="100" class="input-solid" placeholder="18.00">
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t" style="border-color: var(--border-light);">
                <button type="button" @click="showTaxModal = false" class="btn-outline">Cancelar</button>
                <button type="button" @click="submitQuickTax()" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function productQuickAdd() {
    return {
        showCategoryModal: false,
        showTaxModal: false,
        newCat: { name: '', description: '' },
        newTax: { name: '', code: '', rate: '' },
        
        openQuickCategoryModal() {
            this.newCat = { name: '', description: '' };
            this.showCategoryModal = true;
        },
        openQuickTaxModal() {
            this.newTax = { name: '', code: '', rate: '' };
            this.showTaxModal = true;
        },
        submitQuickCategory() {
            fetch('{{ route("settings.catalogs.categories.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(this.newCat)
            })
            .then(res => {
                if (!res.ok) throw res;
                return res.json();
            })
            .then(data => {
                const select = document.getElementById('category_id');
                const opt = new Option(data.name, data.id, true, true);
                select.add(opt);
                this.showCategoryModal = false;
            })
            .catch(err => {
                err.json().then(body => {
                    alert('Error: ' + Object.values(body.errors).flat().join('\n'));
                }).catch(() => {
                    alert('Error al crear la categoría.');
                });
            });
        },
        submitQuickTax() {
            fetch('{{ route("settings.catalogs.taxes.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(this.newTax)
            })
            .then(res => {
                if (!res.ok) throw res;
                return res.json();
            })
            .then(data => {
                const select = document.getElementById('tax_id');
                const rateFormatted = parseFloat(data.rate).toFixed(0);
                const opt = new Option(`${data.name} (${rateFormatted}%)`, data.id, true, true);
                select.add(opt);
                this.showTaxModal = false;
            })
            .catch(err => {
                err.json().then(body => {
                    alert('Error: ' + Object.values(body.errors).flat().join('\n'));
                }).catch(() => {
                    alert('Error al crear el impuesto.');
                });
            });
        }
    }
}
</script>
@endpush
@endsection
