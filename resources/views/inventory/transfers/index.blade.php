@extends('layouts.app')

@section('title', 'Historial de Traslados')
@section('page-title', 'Traslados de Bodega')
@section('page-subtitle', 'Consulta el registro de movimientos y traslados de mercancía entre tus locales')

@section('content')
    <div class="mt-2 max-w-6xl mx-auto page-fade">
        {{-- Flash messages --}}
        @if (session('status'))
            <div class="mb-5 p-4 rounded-xl text-sm font-medium"
                style="background-color: rgba(22, 163, 74, 0.08); border: 1px solid rgba(22, 163, 74, 0.25); color: var(--success);">
                {{ session('status') }}
            </div>
        @endif

        {{-- Plan upgrade banner if applicable --}}
        @if (!$canTransfer)
            <div class="mb-6 p-5 rounded-2xl border flex flex-col sm:flex-row items-center justify-between gap-4"
                style="background-color: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); color: #D97706;">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center text-lg text-amber-600 shrink-0">
                        <i class="fa-solid fa-crown"></i>
                    </span>
                    <div>
                        <h4 class="font-bold text-sm">Funcionalidad Multibodega Restringida</h4>
                        <p class="text-xs text-amber-700/80 mt-0.5">El traslado de stock entre bodegas solo está habilitado para planes multibodega de nivel superior.</p>
                    </div>
                </div>
                <a href="{{ route('settings.subscription.index') }}" class="btn-primary bg-amber-600 hover:bg-amber-700 shrink-0 border-0">
                    Subir de Plan
                </a>
            </div>
        @endif

        {{-- Top Section --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-[color:var(--text-main)]">Registro de Traslados</h2>
            @if ($canTransfer)
                <a href="{{ route('inventory.transfers.create') }}" class="btn-primary">
                    <i class="fa-solid fa-plus mr-1"></i>
                    Nuevo Traslado
                </a>
            @endif
        </div>

        {{-- Table --}}
        <div class="card-panel p-0 overflow-hidden">
            @if ($transfers->isEmpty())
                <div class="p-8 text-center text-[color:var(--text-tertiary)]">
                    <i class="fa-solid fa-right-left text-4xl mb-3 block"></i>
                    <p class="font-medium">Aún no se han registrado traslados de mercancía.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-[color:var(--border-light)] bg-[color:var(--border-light)]/30">
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">ID</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Local Origen</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Local Destino</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Realizado Por</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Fecha y Hora</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Productos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transfers as $transfer)
                                <tr class="border-b border-[color:var(--border-light)] hover:bg-[color:var(--primary-light)]/5 transition-colors">
                                    <td class="p-4 text-sm font-semibold text-[color:var(--text-main)]">#{{ $transfer->id }}</td>
                                    <td class="p-4 text-sm font-medium text-[color:var(--text-main)]">
                                        {{ $transfer->originBranch ? $transfer->originBranch->name : 'N/A' }}
                                    </td>
                                    <td class="p-4 text-sm font-medium text-[color:var(--text-main)]">
                                        {{ $transfer->destinationBranch ? $transfer->destinationBranch->name : 'N/A' }}
                                    </td>
                                    <td class="p-4 text-sm text-[color:var(--text-main)]">{{ $transfer->user->name }}</td>
                                    <td class="p-4 text-sm text-[color:var(--text-tertiary)]">{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="p-4 text-sm">
                                        <div class="space-y-1">
                                            @foreach ($transfer->details as $detail)
                                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-800 text-[color:var(--text-secondary)]">
                                                    {{ $detail->product ? $detail->product->name : 'Producto Eliminado' }} (x{{ $detail->quantity }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
