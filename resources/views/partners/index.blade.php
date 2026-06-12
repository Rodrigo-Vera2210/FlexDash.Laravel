@extends('layouts.app')

@section('title', $type === 'cliente' ? 'Clientes' : 'Proveedores')
@section('page-title', $type === 'cliente' ? 'Clientes (Cuentas por Cobrar)' : 'Proveedores (Cuentas por Pagar)')
@section('page-subtitle',
    'Administra la cartera de ' .
    ($type === 'cliente' ? 'clientes' : 'proveedores') .
    ' del
    negocio')

@section('header-actions')
    <a href="{{ route('partners.create', ['type' => $type]) }}" class="btn-primary">
        <i class="fa-solid fa-plus"></i>
        Nuevo {{ $type === 'cliente' ? 'Cliente' : 'Proveedor' }}
    </a>
@endsection

@section('content')
    <div class="mt-2 space-y-6 page-fade">

        {{-- Tabs de navegación entre Clientes y Proveedores --}}
        <div class="px-6 pt-4 -mb-6 border rounded-t-2xl"
            style="background-color: var(--surface); border-color: var(--border-light); border-bottom: 1px solid var(--border-light); box-shadow: var(--shadow-sm);">
            <div class="flex gap-6">
                <a href="{{ route('partners.index', ['type' => 'cliente']) }}"
                    class="pb-4 text-sm font-bold border-b-2 transition-all"
                    style="border-color: {{ $type === 'cliente' ? 'var(--primary)' : 'transparent' }}; color: {{ $type === 'cliente' ? 'var(--primary)' : 'var(--text-tertiary)' }};"
                    onmouseover="if(this.style.borderColor!=='var(--primary)') this.style.color='var(--text-secondary)'"
                    onmouseout="if(this.style.borderColor!=='var(--primary)') this.style.color='var(--text-tertiary)'">
                    Clientes
                </a>
                <a href="{{ route('partners.index', ['type' => 'proveedor']) }}"
                    class="pb-4 text-sm font-bold border-b-2 transition-all"
                    style="border-color: {{ $type === 'proveedor' ? 'var(--primary)' : 'transparent' }}; color: {{ $type === 'proveedor' ? 'var(--primary)' : 'var(--text-tertiary)' }};"
                    onmouseover="if(this.style.borderColor!=='var(--primary)') this.style.color='var(--text-secondary)'"
                    onmouseout="if(this.style.borderColor!=='var(--primary)') this.style.color='var(--text-tertiary)'">
                    Proveedores
                </a>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card-panel p-6">
            <form method="GET" action="{{ route('partners.index') }}" class="flex gap-4 items-end">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="flex-1">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text" name="search" id="search" value="{{ $search }}"
                        placeholder="Razón social, RUC, DNI..." class="input-solid w-full">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-magnifying-glass"></i> Buscar
                    </button>
                    <a href="{{ route('partners.index', ['type' => $type]) }}"
                        class="outline-blue-900 btn-outline flex items-center gap-1">
                        <i class="fa-solid fa-xmark"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabla de Socios --}}
        <div class="card-panel overflow-hidden w-full">
            <div class="overflow-x-auto w-full">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Documento</th>
                            <th class="table-header">Nombre Comercial / Razón Social</th>
                            <th class="table-header">Contacto</th>
                            <th class="table-header">Dirección / Ciudad</th>
                            <th class="table-header text-right">Límite Crédito</th>
                            <th class="table-header">Tipo Registro</th>
                            <th class="table-header text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($partners as $partner)
                            <tr>
                                <td class="table-cell">
                                    <div class="font-bold" style="color: var(--text-main);">{{ $partner->document_type }}
                                    </div>
                                    <div class="text-xs font-mono mt-0.5" style="color: var(--text-tertiary);">
                                        {{ $partner->document_number }}</div>
                                </td>
                                <td class="table-cell">
                                    <div class="font-bold" style="color: var(--text-main);">{{ $partner->business_name }}
                                    </div>
                                    @if ($partner->trade_name)
                                        <div class="text-xs mt-0.5" style="color: var(--text-tertiary);">
                                            {{ $partner->trade_name }}</div>
                                    @endif
                                </td>
                                <td class="table-cell text-xs">
                                    @if ($partner->email)
                                        <div class="flex items-center gap-1.5" style="color: var(--text-secondary);">
                                            <i class="fa-solid fa-envelope" style="color: var(--text-tertiary);"></i>
                                            {{ $partner->email }}
                                        </div>
                                    @endif
                                    @if ($partner->phone)
                                        <div class="flex items-center gap-1.5 mt-1" style="color: var(--text-tertiary);">
                                            <i class="fa-solid fa-phone" style="color: var(--text-tertiary);"></i>
                                            {{ $partner->phone }}
                                        </div>
                                    @endif
                                </td>
                                <td class="table-cell text-xs">
                                    <div class="truncate max-w-xs" style="color: var(--text-secondary);">
                                        {{ $partner->address ?? 'Sin dirección' }}</div>
                                    @if ($partner->city)
                                        <div class="mt-0.5 font-semibold" style="color: var(--text-tertiary);">
                                            {{ $partner->city }}</div>
                                    @endif
                                </td>
                                <td class="table-cell text-right font-bold font-mono" style="color: var(--text-main);">
                                    @if ($partner->credit_limit > 0)
                                        S/ {{ number_format($partner->credit_limit, 2) }}
                                    @else
                                        <span class="text-xs font-normal" style="color: var(--text-tertiary);">Sin
                                            límite</span>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    @switch($partner->type)
                                        @case('cliente')
                                            <span class="badge badge-info"><i class="fa-solid fa-user"></i> Cliente</span>
                                        @break

                                        @case('proveedor')
                                            <span class="badge badge-gold"><i class="fa-solid fa-truck"></i> Proveedor</span>
                                        @break

                                        @case('ambos')
                                            <span class="badge badge-magenta"><i class="fa-solid fa-handshake"></i> Ambos</span>
                                        @break
                                    @endswitch
                                </td>
                                <td class="table-cell text-center">
                                    <div class="inline-flex gap-2">
                                        <a href="{{ route('partners.show', $partner) }}" class="btn-icon"
                                            title="Ver Detalles">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('partners.edit', $partner) }}" class="btn-icon" title="Editar"
                                            style="color: var(--warning);">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form method="POST" action="{{ route('partners.destroy', $partner) }}"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar este socio comercial?')"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Eliminar"
                                                style="color: var(--danger);">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                                style="background-color: var(--primary-light);">
                                                <i class="fa-solid fa-users-slash text-xl" style="color: var(--primary);"></i>
                                            </div>
                                            <p class="text-sm font-medium" style="color: var(--text-tertiary);">
                                                No se encontraron socios comerciales con los filtros especificados.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($partners->hasPages())
                    <div class="px-6 py-4 border-t" style="border-color: var(--border-light); background-color: var(--bg);">
                        {{ $partners->links() }}
                    </div>
                @endif
            </div>

        </div>
    @endsection
