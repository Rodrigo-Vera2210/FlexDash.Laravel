@extends('layouts.app')

@section('title', 'Mis Soporte / Tickets')
@section('page-title', 'Mis Soporte / Tickets')
@section('page-subtitle', 'Administra y realiza el seguimiento de tus reportes de problemas')

@section('content')
    <div class="mt-2 max-w-6xl mx-auto page-fade">
        {{-- Flash messages --}}
        @if (session('status'))
            <div class="mb-5 p-4 rounded-xl text-sm font-medium"
                style="background-color: rgba(22, 163, 74, 0.08); border: 1px solid rgba(22, 163, 74, 0.25); color: var(--success);">
                {{ session('status') }}
            </div>
        @endif

        {{-- Top Section --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-[color:var(--text-main)]">Historial de Reportes</h2>
            <a href="{{ route('tickets.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus mr-1"></i>
                Nuevo Ticket
            </a>
        </div>

        {{-- Tickets Table --}}
        <div class="card-panel p-0 overflow-hidden">
            @if ($tickets->isEmpty())
                <div class="p-8 text-center text-[color:var(--text-tertiary)]">
                    <i class="fa-solid fa-ticket-simple text-4xl mb-3 block"></i>
                    <p class="font-medium">Aún no has reportado ningún ticket.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-[color:var(--border-light)] bg-[color:var(--border-light)]/30">
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">ID</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Título</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Severidad</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Estado</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Fecha</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-[color:var(--text-tertiary)]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tickets as $ticket)
                                <tr class="border-b border-[color:var(--border-light)] hover:bg-[color:var(--primary-light)]/5 transition-colors">
                                    <td class="p-4 text-sm font-semibold text-[color:var(--text-main)]">#{{ $ticket->id }}</td>
                                    <td class="p-4 text-sm font-medium text-[color:var(--text-main)]">{{ $ticket->title }}</td>
                                    <td class="p-4 text-sm">
                                        @if ($ticket->severity === 'alto')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Alto</span>
                                        @elseif ($ticket->severity === 'medio')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Medio</span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Bajo</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-sm">
                                        @if ($ticket->status === 'aprobado')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">Aprobado</span>
                                        @elseif ($ticket->status === 'rechazado')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">Rechazado</span>
                                        @elseif ($ticket->status === 'en proceso')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400">En proceso</span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800 dark:bg-gray-800/60 dark:text-gray-400">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-sm text-[color:var(--text-tertiary)]">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="p-4 text-sm">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="text-[color:var(--primary)] hover:underline font-bold">
                                            Ver Detalles
                                        </a>
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
