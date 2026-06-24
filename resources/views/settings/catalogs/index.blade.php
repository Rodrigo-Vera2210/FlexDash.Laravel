@extends('layouts.app')

@section('title', 'Gestión de Catálogos')
@section('page-title', 'Gestión de Catálogos')
@section('page-subtitle', 'Administra los impuestos, categorías de productos, métodos de pago y otros catálogos del sistema.')

@section('content')
<div class="mt-2 page-fade" x-data="catalogManager()">
    
    {{-- Selector de Pestañas --}}
    <div class="flex border-b mb-6" style="border-color: var(--border-light);">
        <button 
            @click="activeTab = 'taxes'"
            :class="activeTab === 'taxes' ? 'border-b-2 text-primary font-bold' : 'text-gray-500 hover:text-gray-700'"
            class="px-6 py-3 text-sm focus:outline-none transition-colors border-transparent"
            :style="activeTab === 'taxes' ? 'border-color: var(--primary); color: var(--primary);' : ''"
        >
            <i class="fa-solid fa-percent mr-2"></i> Impuestos
        </button>
        <button 
            @click="activeTab = 'categories'"
            :class="activeTab === 'categories' ? 'border-b-2 text-primary font-bold' : 'text-gray-500 hover:text-gray-700'"
            class="px-6 py-3 text-sm focus:outline-none transition-colors border-transparent"
            :style="activeTab === 'categories' ? 'border-color: var(--primary); color: var(--primary);' : ''"
        >
            <i class="fa-solid fa-tags mr-2"></i> Categorías de Producto
        </button>
        <button 
            @click="activeTab = 'service-categories'"
            :class="activeTab === 'service-categories' ? 'border-b-2 text-primary font-bold' : 'text-gray-500 hover:text-gray-700'"
            class="px-6 py-3 text-sm focus:outline-none transition-colors border-transparent"
            :style="activeTab === 'service-categories' ? 'border-color: var(--primary); color: var(--primary);' : ''"
        >
            <i class="fa-solid fa-screwdriver-wrench mr-2"></i> Categorías de Servicio
        </button>
        <button 
            @click="activeTab = 'payment-methods'"
            :class="activeTab === 'payment-methods' ? 'border-b-2 text-primary font-bold' : 'text-gray-500 hover:text-gray-700'"
            class="px-6 py-3 text-sm focus:outline-none transition-colors border-transparent"
            :style="activeTab === 'payment-methods' ? 'border-color: var(--primary); color: var(--primary);' : ''"
        >
            <i class="fa-solid fa-credit-card mr-2"></i> Métodos de Pago
        </button>
    </div>

    {{-- ── PESTAÑA: IMPUESTOS ── --}}
    <div x-show="activeTab === 'taxes'" class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-base font-bold" style="color: var(--text-main);">Listado de Impuestos</h3>
            <button @click="openTaxModal('create')" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nuevo Impuesto
            </button>
        </div>

        <div class="card-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Nombre</th>
                            <th class="table-header">Código</th>
                            <th class="table-header text-right">Tasa (%)</th>
                            <th class="table-header text-center">Estado</th>
                            <th class="table-header text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($taxes as $tax)
                            <tr id="tax-row-{{ $tax->id }}">
                                <td class="table-cell font-bold" style="color: var(--text-main);">{{ $tax->name }}</td>
                                <td class="table-cell font-mono text-xs font-bold">{{ $tax->code }}</td>
                                <td class="table-cell text-right font-mono font-bold">{{ number_format($tax->rate, 2) }}%</td>
                                <td class="table-cell text-center">
                                    <button 
                                        @click="toggleStatus('tax', {{ $tax->id }}, $event)"
                                        class="badge cursor-pointer transition-all hover:scale-105"
                                        :class="isActive('tax', {{ $tax->id }}, {{ $tax->is_active ? 'true' : 'false' }}) ? 'badge-success' : 'badge-danger'"
                                    >
                                        <span x-text="isActive('tax', {{ $tax->id }}, {{ $tax->is_active ? 'true' : 'false' }}) ? 'Activo' : 'Inactivo'"></span>
                                    </button>
                                </td>
                                <td class="table-cell text-center">
                                    <div class="inline-flex gap-2 justify-center">
                                        <button @click="openTaxModal('edit', {{ json_encode($tax) }})" class="btn-icon text-blue-500 hover:text-blue-700" title="Editar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" action="{{ route('settings.catalogs.destroy', ['type' => 'taxes', 'id' => $tax->id]) }}" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este impuesto?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon text-red-500 hover:text-red-700" title="Eliminar">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-gray-500">No hay impuestos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── PESTAÑA: CATEGORÍAS ── --}}
    <div x-show="activeTab === 'categories'" class="space-y-4" style="display: none;">
        <div class="flex justify-between items-center">
            <h3 class="text-base font-bold" style="color: var(--text-main);">Listado de Categorías</h3>
            <button @click="openCategoryModal('create')" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nueva Categoría
            </button>
        </div>

        <div class="card-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Nombre</th>
                            <th class="table-header">Descripción</th>
                            <th class="table-header text-center">Estado</th>
                            <th class="table-header text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $cat)
                            <tr id="category-row-{{ $cat->id }}">
                                <td class="table-cell font-bold" style="color: var(--text-main);">{{ $cat->name }}</td>
                                <td class="table-cell text-sm">{{ $cat->description ?? '—' }}</td>
                                <td class="table-cell text-center">
                                    <button 
                                        @click="toggleStatus('category', {{ $cat->id }}, $event)"
                                        class="badge cursor-pointer transition-all hover:scale-105"
                                        :class="isActive('category', {{ $cat->id }}, {{ $cat->is_active ? 'true' : 'false' }}) ? 'badge-success' : 'badge-danger'"
                                    >
                                        <span x-text="isActive('category', {{ $cat->id }}, {{ $cat->is_active ? 'true' : 'false' }}) ? 'Activo' : 'Inactivo'"></span>
                                    </button>
                                </td>
                                <td class="table-cell text-center">
                                    <div class="inline-flex gap-2 justify-center">
                                        <button @click="openCategoryModal('edit', {{ json_encode($cat) }})" class="btn-icon text-blue-500 hover:text-blue-700" title="Editar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" action="{{ route('settings.catalogs.destroy', ['type' => 'categories', 'id' => $cat->id]) }}" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar esta categoría?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon text-red-500 hover:text-red-700" title="Eliminar">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-gray-500">No hay categorías registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── PESTAÑA: CATEGORÍAS DE SERVICIO ── --}}
    <div x-show="activeTab === 'service-categories'" class="space-y-4" style="display: none;">
        <div class="flex justify-between items-center">
            <h3 class="text-base font-bold" style="color: var(--text-main);">Listado de Categorías de Servicio</h3>
            <button @click="openServiceCategoryModal('create')" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nueva Categoría
            </button>
        </div>

        <div class="card-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Nombre</th>
                            <th class="table-header">Descripción</th>
                            <th class="table-header text-center">Estado</th>
                            <th class="table-header text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serviceCategories as $scat)
                            <tr id="service-category-row-{{ $scat->id }}">
                                <td class="table-cell font-bold" style="color: var(--text-main);">{{ $scat->name }}</td>
                                <td class="table-cell text-sm">{{ $scat->description ?? '—' }}</td>
                                <td class="table-cell text-center">
                                    <button 
                                        @click="toggleStatus('service_category', {{ $scat->id }}, $event)"
                                        class="badge cursor-pointer transition-all hover:scale-105"
                                        :class="isActive('service_category', {{ $scat->id }}, {{ $scat->is_active ? 'true' : 'false' }}) ? 'badge-success' : 'badge-danger'"
                                    >
                                        <span x-text="isActive('service_category', {{ $scat->id }}, {{ $scat->is_active ? 'true' : 'false' }}) ? 'Activo' : 'Inactivo'"></span>
                                    </button>
                                </td>
                                <td class="table-cell text-center">
                                    <div class="inline-flex gap-2 justify-center">
                                        <button @click="openServiceCategoryModal('edit', {{ json_encode($scat) }})" class="btn-icon text-blue-500 hover:text-blue-700" title="Editar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" action="{{ route('settings.catalogs.destroy', ['type' => 'service-categories', 'id' => $scat->id]) }}" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar esta categoría de servicio?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon text-red-500 hover:text-red-700" title="Eliminar">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-gray-500">No hay categorías de servicio registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── PESTAÑA: MÉTODOS DE PAGO ── --}}
    <div x-show="activeTab === 'payment-methods'" class="space-y-4" style="display: none;">
        <div class="flex justify-between items-center">
            <h3 class="text-base font-bold" style="color: var(--text-main);">Métodos de Pago</h3>
            <button @click="openMethodModal('create')" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nuevo Método
            </button>
        </div>

        <div class="card-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Nombre</th>
                            <th class="table-header">Descripción</th>
                            <th class="table-header text-center">Estado</th>
                            <th class="table-header text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paymentMethods as $method)
                            <tr id="method-row-{{ $method->id }}">
                                <td class="table-cell font-bold" style="color: var(--text-main);">{{ $method->name }}</td>
                                <td class="table-cell text-sm">{{ $method->description ?? '—' }}</td>
                                <td class="table-cell text-center">
                                    <button 
                                        @click="toggleStatus('payment_method', {{ $method->id }}, $event)"
                                        class="badge cursor-pointer transition-all hover:scale-105"
                                        :class="isActive('payment_method', {{ $method->id }}, {{ $method->is_active ? 'true' : 'false' }}) ? 'badge-success' : 'badge-danger'"
                                    >
                                        <span x-text="isActive('payment_method', {{ $method->id }}, {{ $method->is_active ? 'true' : 'false' }}) ? 'Activo' : 'Inactivo'"></span>
                                    </button>
                                </td>
                                <td class="table-cell text-center">
                                    <div class="inline-flex gap-2 justify-center">
                                        <button @click="openMethodModal('edit', {{ json_encode($method) }})" class="btn-icon text-blue-500 hover:text-blue-700" title="Editar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" action="{{ route('settings.catalogs.destroy', ['type' => 'payment-methods', 'id' => $method->id]) }}" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este método de pago?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon text-red-500 hover:text-red-700" title="Eliminar">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-gray-500">No hay métodos de pago registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ==========================================
         MODALES DE EDICIÓN Y CREACIÓN
         ========================================== --}}

    {{-- MODAL: IMPUESTOS --}}
    <div 
        x-show="showTaxModal" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="display: none;"
    >
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-6 overflow-hidden transform transition-all page-fade">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold" style="color: var(--text-main);" x-text="taxEditMode ? 'Editar Impuesto' : 'Crear Impuesto'"></h3>
                <button @click="showTaxModal = false" class="btn-icon"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form :action="taxEditMode ? '{{ url('/settings/catalogs/taxes') }}/' + taxData.id : '{{ route('settings.catalogs.taxes.store') }}'" method="POST">
                @csrf
                <template x-if="taxEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="space-y-4">
                    <div>
                        <label class="form-label">Nombre del Impuesto <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-percent"></i>
                            <input type="text" name="name" x-model="taxData.name" required class="input-solid" placeholder="Ej: IGV, IVA, Exento">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Código Sunat/Fiscal <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-barcode"></i>
                                <input type="text" name="code" x-model="taxData.code" required class="input-solid" placeholder="Ej: 1000, VAT">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Tasa (%) <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-calculator"></i>
                                <input type="number" name="rate" x-model="taxData.rate" required step="0.01" min="0" max="100" class="input-solid" placeholder="18.00">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6 pt-4 border-t" style="border-color: var(--border-light);">
                    <button type="button" @click="showTaxModal = false" class="btn-outline">Cancelar</button>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: CATEGORÍAS --}}
    <div 
        x-show="showCategoryModal" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="display: none;"
    >
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-6 overflow-hidden transform transition-all page-fade">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold" style="color: var(--text-main);" x-text="categoryEditMode ? 'Editar Categoría' : 'Crear Categoría'"></h3>
                <button @click="showCategoryModal = false" class="btn-icon"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form :action="categoryEditMode ? '{{ url('/settings/catalogs/categories') }}/' + categoryData.id : '{{ route('settings.catalogs.categories.store') }}'" method="POST">
                @csrf
                <template x-if="categoryEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="space-y-4">
                    <div>
                        <label class="form-label">Nombre de Categoría <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-tag"></i>
                            <input type="text" name="name" x-model="categoryData.name" required class="input-solid" placeholder="Ej: Bebidas, Limpieza, Tecnología">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Descripción</label>
                        <textarea name="description" x-model="categoryData.description" class="input-solid" rows="3" placeholder="Detalle o notas de la categoría..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6 pt-4 border-t" style="border-color: var(--border-light);">
                    <button type="button" @click="showCategoryModal = false" class="btn-outline">Cancelar</button>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: CATEGORÍAS DE SERVICIO --}}
    <div 
        x-show="showServiceCategoryModal" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="display: none;"
    >
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-6 overflow-hidden transform transition-all page-fade">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold" style="color: var(--text-main);" x-text="serviceCategoryEditMode ? 'Editar Categoría' : 'Crear Categoría'"></h3>
                <button @click="showServiceCategoryModal = false" class="btn-icon"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form :action="serviceCategoryEditMode ? '{{ url('/settings/catalogs/service-categories') }}/' + serviceCategoryData.id : '{{ route('settings.catalogs.service-categories.store') }}'" method="POST">
                @csrf
                <template x-if="serviceCategoryEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="space-y-4">
                    <div>
                        <label class="form-label">Nombre de Categoría <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-tag"></i>
                            <input type="text" name="name" x-model="serviceCategoryData.name" required class="input-solid" placeholder="Ej: Instalaciones, Soporte, Mantenimiento">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Descripción</label>
                        <textarea name="description" x-model="serviceCategoryData.description" class="input-solid" rows="3" placeholder="Detalle o notas de la categoría..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6 pt-4 border-t" style="border-color: var(--border-light);">
                    <button type="button" @click="showServiceCategoryModal = false" class="btn-outline">Cancelar</button>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: MÉTODOS DE PAGO --}}
    <div 
        x-show="showMethodModal" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="display: none;"
    >
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-6 overflow-hidden transform transition-all page-fade">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold" style="color: var(--text-main);" x-text="methodEditMode ? 'Editar Método de Pago' : 'Crear Método de Pago'"></h3>
                <button @click="showMethodModal = false" class="btn-icon"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form :action="methodEditMode ? '{{ url('/settings/catalogs/payment-methods') }}/' + methodData.id : '{{ route('settings.catalogs.payment-methods.store') }}'" method="POST">
                @csrf
                <template x-if="methodEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="space-y-4">
                    <div>
                        <label class="form-label">Nombre del Método <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-credit-card"></i>
                            <input type="text" name="name" x-model="methodData.name" required class="input-solid" placeholder="Ej: Efectivo, Tarjeta de Crédito, Transferencia">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Descripción</label>
                        <textarea name="description" x-model="methodData.description" class="input-solid" rows="3" placeholder="Detalle o notas del método de pago..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6 pt-4 border-t" style="border-color: var(--border-light);">
                    <button type="button" @click="showMethodModal = false" class="btn-outline">Cancelar</button>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function catalogManager() {
        return {
            activeTab: 'taxes',
            
            // Modales
            showTaxModal: false,
            taxEditMode: false,
            taxData: { id: '', name: '', code: '', rate: '' },

            showCategoryModal: false,
            categoryEditMode: false,
            categoryData: { id: '', name: '', description: '' },

            showServiceCategoryModal: false,
            serviceCategoryEditMode: false,
            serviceCategoryData: { id: '', name: '', description: '' },

            showMethodModal: false,
            methodEditMode: false,
            methodData: { id: '', name: '', description: '' },

            // Historial de estados en cliente para evitar delays de red
            states: {},

            isActive(model, id, serverValue) {
                const key = `${model}-${id}`;
                if (this.states[key] === undefined) {
                    this.states[key] = serverValue;
                }
                return this.states[key];
            },

            openTaxModal(mode, tax = null) {
                this.taxEditMode = (mode === 'edit');
                if (tax) {
                    this.taxData = { id: tax.id, name: tax.name, code: tax.code, rate: tax.rate };
                } else {
                    this.taxData = { id: '', name: '', code: '', rate: '' };
                }
                this.showTaxModal = true;
            },

            openCategoryModal(mode, cat = null) {
                this.categoryEditMode = (mode === 'edit');
                if (cat) {
                    this.categoryData = { id: cat.id, name: cat.name, description: cat.description || '' };
                } else {
                    this.categoryData = { id: '', name: '', description: '' };
                }
                this.showCategoryModal = true;
            },

            openServiceCategoryModal(mode, scat = null) {
                this.serviceCategoryEditMode = (mode === 'edit');
                if (scat) {
                    this.serviceCategoryData = { id: scat.id, name: scat.name, description: scat.description || '' };
                } else {
                    this.serviceCategoryData = { id: '', name: '', description: '' };
                }
                this.showServiceCategoryModal = true;
            },

            openMethodModal(mode, method = null) {
                this.methodEditMode = (mode === 'edit');
                if (method) {
                    this.methodData = { id: method.id, name: method.name, description: method.description || '' };
                } else {
                    this.methodData = { id: '', name: '', description: '' };
                }
                this.showMethodModal = true;
            },

            toggleStatus(model, id, event) {
                const key = `${model}-${id}`;
                // Previene que se haga doble submit
                event.target.disabled = true;

                fetch('{{ route("settings.catalogs.toggle-status") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ model: model, id: id })
                })
                .then(res => res.json())
                .then(data => {
                    event.target.disabled = false;
                    if (data.success) {
                        this.states[key] = data.is_active;
                    }
                })
                .catch(err => {
                    event.target.disabled = false;
                    console.error(err);
                    alert('Error al actualizar el estado.');
                });
            }
        }
    }
</script>
@endpush
