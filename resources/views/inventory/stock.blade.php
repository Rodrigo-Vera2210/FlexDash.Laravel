@extends('layouts.app')

@section('title', 'Inventario por Local')
@section('page-title', 'Inventario por Local')
@section('page-subtitle', 'Consulta las existencias y stock consolidado de tus productos en cada sucursal')

@section('content')
    <div class="mt-2 max-w-6xl mx-auto page-fade">
        {{-- Flash messages --}}
        @if (session('status'))
            <div class="mb-5 p-4 rounded-xl text-sm font-medium"
                style="background-color: rgba(22, 163, 74, 0.08); border: 1px solid rgba(22, 163, 74, 0.25); color: var(--success);">
                {{ session('status') }}
            </div>
        @endif

        {{-- Top Actions & Filter --}}
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
            <form method="GET" action="{{ route('inventory.stock') }}" class="flex items-center gap-2 w-full md:w-auto">
                <label for="branch_id" class="text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)] shrink-0">Filtrar por local:</label>
                <select id="branch_id" name="branch_id" onchange="this.form.submit()" class="input-solid max-w-xs text-sm">
                    <option value="">Todos los locales</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string)$selectedBranchId === (string)$branch->id ? 'selected' : '' }}>
                            {{ $branch->name }} ({{ $branch->establishment_code }})
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                <a href="{{ route('inventory.index') }}" class="btn-outline">
                    <i class="fa-solid fa-list mr-1"></i>
                    Ver Kardex (Movimientos)
                </a>

                @if (auth()->user()->company->max_branches > 1)
                    <a href="{{ route('inventory.transfers.index') }}" class="btn-primary">
                        <i class="fa-solid fa-right-left mr-1"></i>
                        Traslados de Bodega
                    </a>
                @endif
            </div>
        </div>

        {{-- Stock Grid --}}
        <div class="card-panel p-0 overflow-hidden">
            @if ($products->isEmpty())
                <div class="p-8 text-center text-[color:var(--text-tertiary)]">
                    <i class="fa-solid fa-warehouse text-4xl mb-3 block"></i>
                    <p class="font-medium">No se encontraron productos registrados.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-[color:var(--border-light)] bg-[color:var(--border-light)]/30">
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">ID</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Nombre</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">SKU</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Precio Venta</th>
                                
                                @if ($selectedBranchId)
                                    @php
                                        $singleBranch = $branches->firstWhere('id', $selectedBranchId);
                                    @endphp
                                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Stock ({{ $singleBranch->name }})</th>
                                @else
                                    @foreach ($branches as $branch)
                                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Stock ({{ $branch->name }})</th>
                                    @endforeach
                                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Stock Total</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr class="border-b border-[color:var(--border-light)] hover:bg-[color:var(--primary-light)]/5 transition-colors">
                                    <td class="p-4 text-sm font-semibold text-[color:var(--text-main)]">#{{ $product->id }}</td>
                                    <td class="p-4 text-sm font-medium text-[color:var(--text-main)]">{{ $product->name }}</td>
                                    <td class="p-4 text-sm font-mono text-[color:var(--text-secondary)]">{{ $product->sku ?? 'N/A' }}</td>
                                    <td class="p-4 text-sm font-semibold text-[color:var(--text-main)]">${{ number_format($product->price, 2) }}</td>
                                    
                                    @if ($selectedBranchId)
                                        @php
                                            $bp = $product->branches->firstWhere('id', $selectedBranchId);
                                            $stock = $bp ? $bp->pivot->stock : 0;
                                        @endphp
                                        <td class="p-4 text-sm font-bold {{ $stock <= 0 ? 'text-rose-500' : 'text-[color:var(--text-main)]' }}">{{ $stock }}</td>
                                    @else
                                        @php $totalStock = 0; @endphp
                                        @foreach ($branches as $branch)
                                            @php
                                                $bp = $product->branches->firstWhere('id', $branch->id);
                                                $stock = $bp ? $bp->pivot->stock : 0;
                                                $totalStock += $stock;
                                            @endphp
                                            <td class="p-4 text-sm text-[color:var(--text-secondary)]">{{ $stock }}</td>
                                        @endforeach
                                        <td class="p-4 text-sm font-bold {{ $totalStock <= 0 ? 'text-rose-500' : 'text-[color:var(--primary)]' }}">{{ $totalStock }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
