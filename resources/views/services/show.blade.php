@extends('layouts.app')

@section('title', $service->name)
@section('page-title', 'Detalle del Servicio')
@section('page-subtitle', $service->code . ' — ' . $service->name)

@section('header-actions')
    <a href="{{ route('services.edit', $service) }}" class="btn-secondary">
        <i class="fa-solid fa-pen"></i>
        Editar
    </a>
@endsection

@section('content')
<div class="mt-2 space-y-6 page-fade">

    <div class="mb-4">
        <a href="{{ route('services.index') }}" class="btn-outline">
            <i class="fa-solid fa-arrow-left"></i>
            Volver al listado
        </a>
    </div>

    {{-- Ficha del Servicio --}}
    <div class="card-panel p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Info Principal --}}
            <div class="space-y-3 md:border-r pr-6" style="border-color: var(--border-light);">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: var(--primary-light);">
                        <i class="fa-solid fa-screwdriver-wrench text-xl" style="color: var(--primary);"></i>
                    </div>
                    <div>
                        <p class="font-bold text-sm" style="color: var(--text-main);">{{ $service->name }}</p>
                        <p class="font-mono text-xs" style="color: var(--text-tertiary);">{{ $service->code }}</p>
                    </div>
                </div>

                <div>
                    <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Categoría</span>
                    <span class="font-bold" style="color: var(--text-secondary);">{{ $service->category->name ?? 'Sin categoría' }}</span>
                </div>

                <div>
                    <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Estado</span>
                    @if ($service->is_active)
                        <span class="badge badge-success">Activo</span>
                    @else
                        <span class="badge badge-danger">Inactivo</span>
                    @endif
                </div>

                @if ($service->description)
                    <div>
                        <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Descripción</span>
                        <p class="text-sm" style="color: var(--text-secondary);">{{ $service->description }}</p>
                    </div>
                @endif
            </div>

            {{-- Financiero --}}
            <div class="space-y-3 md:border-r pr-6" style="border-color: var(--border-light);">
                <div>
                    <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Precio de Venta</span>
                    <span class="text-xl font-bold font-mono" style="color: var(--primary);">S/ {{ number_format($service->price, 2) }}</span>
                </div>
                <div>
                    <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Costo Referencial</span>
                    <span class="font-bold font-mono" style="color: var(--text-secondary);">S/ {{ number_format($service->cost, 2) }}</span>
                </div>
                @if ($service->cost > 0)
                    <div>
                        <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Margen</span>
                        <span class="font-bold" style="color: var(--success);">{{ number_format($service->margin, 1) }}%</span>
                    </div>
                @endif
            </div>

            {{-- Impuestos --}}
            <div class="space-y-3">
                <div>
                    <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Impuesto Asignado</span>
                    @if ($service->tax)
                        <span class="font-bold" style="color: var(--text-main);">{{ $service->tax->name }}</span>
                        <span class="text-xs font-mono" style="color: var(--text-tertiary);">{{ number_format($service->tax->rate, 0) }}%</span>
                    @else
                        <span class="badge badge-draft">Exento (0%)</span>
                    @endif
                </div>

                <div>
                    <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Precio + IVA</span>
                    @php
                        $taxRate = $service->tax ? $service->tax->rate / 100 : 0;
                        $priceWithTax = $service->price * (1 + $taxRate);
                    @endphp
                    <span class="text-lg font-bold font-mono" style="color: var(--text-main);">S/ {{ number_format($priceWithTax, 2) }}</span>
                </div>

                <div>
                    <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Creado</span>
                    <span class="text-sm" style="color: var(--text-secondary);">{{ $service->created_at->format('d/m/Y H:i') }}</span>
                </div>

                <div>
                    <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Última Actualización</span>
                    <span class="text-sm" style="color: var(--text-secondary);">{{ $service->updated_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Acciones --}}
    <div class="flex gap-2">
        <a href="{{ route('services.edit', $service) }}" class="btn-secondary">
            <i class="fa-solid fa-pen"></i>
            Editar Servicio
        </a>
        <form method="POST" action="{{ route('services.destroy', $service) }}"
            onsubmit="return confirm('¿Seguro que deseas eliminar este servicio?')"
            class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">
                <i class="fa-solid fa-trash-can"></i>
                Eliminar Servicio
            </button>
        </form>
    </div>
</div>
@endsection
