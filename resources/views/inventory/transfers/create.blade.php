@extends('layouts.app')

@section('title', 'Nuevo Traslado de Bodega')
@section('page-title', 'Registrar Traslado')
@section('page-subtitle', 'Selecciona el local origen, destino y los productos a transferir')

@section('content')
    <div class="mt-2 max-w-4xl mx-auto page-fade">
        <div class="card-panel p-6" x-data="transferForm()">
            <h2 class="text-base font-bold text-[color:var(--text-main)] mb-6 border-b border-[color:var(--border-light)] pb-3">
                Formulario de Traslado
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

            <form method="POST" action="{{ route('inventory.transfers.store') }}" class="space-y-6">
                @csrf

                {{-- Branch selections --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="origin_branch_id" class="form-label">Local / Bodega de Origen</label>
                        <select id="origin_branch_id" name="origin_branch_id" x-model="originBranch"
                            class="input-solid mt-1 {{ $errors->has('origin_branch_id') ? 'border-red-500' : '' }}" required>
                            <option value="">Seleccione origen...</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->establishment_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="destination_branch_id" class="form-label">Local / Bodega de Destino</label>
                        <select id="destination_branch_id" name="destination_branch_id" x-model="destinationBranch"
                            class="input-solid mt-1 {{ $errors->has('destination_branch_id') ? 'border-red-500' : '' }}" required>
                            <option value="">Seleccione destino...</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" x-show="originBranch != {{ $branch->id }}">{{ $branch->name }} ({{ $branch->establishment_code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Products selector list --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between border-b border-[color:var(--border-light)] pb-2">
                        <label class="form-label">Productos a Trasladar</label>
                        <button type="button" @click="addItem()" class="btn-outline py-1 px-3 text-xs">
                            <i class="fa-solid fa-plus mr-1"></i>
                            Agregar Producto
                        </button>
                    </div>

                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-[color:var(--border-light)]">
                                <th class="py-2 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Producto</th>
                                <th class="py-2 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)] w-32">Cantidad</th>
                                <th class="py-2 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)] w-16 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="border-b border-[color:var(--border-light)]/50">
                                    <td class="py-2 pr-4">
                                        <select :name="'items['+index+'][product_id]'" x-model="item.product_id" class="input-solid text-sm" required>
                                            <option value="">Seleccione un producto...</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }} (SKU: {{ $product->sku ?? 'N/A' }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <input type="number" :name="'items['+index+'][quantity]'" x-model="item.quantity" min="1" class="input-solid text-sm" required />
                                    </td>
                                    <td class="py-2 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-rose-500 hover:text-rose-700 bg-transparent border-0 cursor-pointer">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-between pt-4 border-t border-[color:var(--border-light)]">
                    <a href="{{ route('inventory.transfers.index') }}" class="btn-outline">
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-paper-plane mr-1"></i>
                        Confirmar Traslado
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function transferForm() {
            return {
                originBranch: '',
                destinationBranch: '',
                items: [
                    { product_id: '', quantity: 1 }
                ],
                addItem() {
                    this.items.push({ product_id: '', quantity: 1 });
                },
                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                }
            }
        }
    </script>
@endsection
