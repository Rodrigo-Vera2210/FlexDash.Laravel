@extends('layouts.app')

@section('title', 'Caja Negra — Auditoría')
@section('page-title', 'Bitácora de Auditoría')
@section('page-subtitle', 'Consulta de cambios, creación, edición o anulación de documentos en tiempo real')

@section('content')
    <div class="mt-2 space-y-6 page-fade">

        {{-- Filtros --}}
        <div class="card-panel p-6">
            <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="event" class="form-label">Tipo de Evento</label>
                    <select name="event" id="event" class="input-solid">
                        <option value="">Todos los eventos</option>
                        <option value="created" {{ $event === 'created' ? 'selected' : '' }}>Creado</option>
                        <option value="updated" {{ $event === 'updated' ? 'selected' : '' }}>Modificado</option>
                        <option value="deleted" {{ $event === 'deleted' ? 'selected' : '' }}>Eliminado</option>
                    </select>
                </div>

                <div>
                    <label for="model" class="form-label">Entidad / Modelo</label>
                    <input type="text" name="model" id="model" value="{{ $model }}"
                        placeholder="Ej: Product, Sale, Partner..." class="input-solid">
                </div>

                <div>
                    <label for="user_id" class="form-label">Usuario / Operador</label>
                    <select name="user_id" id="user_id" class="input-solid">
                        <option value="">Todos los operadores</option>
                        @foreach (\App\Models\User::orderBy('name')->get() as $u)
                            <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="fa-solid fa-filter"></i> Filtrar Log
                    </button>
                    <a href="{{ route('audit.index') }}" class="btn-outline justify-center">
                        <i class="fa-solid fa-xmark"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabla de Logs --}}
        <div class="card-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Fecha / Hora</th>
                            <th class="table-header">Operador</th>
                            <th class="table-header">Evento</th>
                            <th class="table-header">Entidad Auditada</th>
                            <th class="table-header">Detalle de Modificaciones (Old → New)</th>
                            <th class="table-header">Metadatos Conexión</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="table-cell whitespace-nowrap text-xs font-mono"
                                    style="color: var(--text-tertiary);">
                                    {{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—' }}
                                </td>
                                <td class="table-cell">
                                    <div class="font-bold text-sm" style="color: var(--text-main);">
                                        {{ $log->user->name ?? 'Sistema' }}</div>
                                    <div class="text-xs" style="color: var(--text-tertiary);">
                                        {{ $log->user->email ?? 'Automático' }}</div>
                                </td>
                                <td class="table-cell">
                                    @if ($log->event === 'created')
                                        <span class="badge badge-success"><i class="fa-solid fa-circle-plus"></i>
                                            Creado</span>
                                    @elseif($log->event === 'updated')
                                        <span class="badge badge-info"><i class="fa-solid fa-pen-to-square"></i>
                                            Modificado</span>
                                    @else
                                        <span class="badge badge-danger"><i class="fa-solid fa-trash-can"></i>
                                            Eliminado</span>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    <span class="font-bold text-xs font-mono block" style="color: var(--text-secondary);">
                                        {{ class_basename($log->auditable_type) }}
                                    </span>
                                    <span class="text-xs" style="color: var(--text-tertiary);">ID:
                                        {{ $log->auditable_id }}</span>
                                </td>
                                <td class="table-cell text-xs py-4">
                                    @if ($log->event === 'created')
                                        <p class="font-semibold" style="color: var(--success);">Registro inicial creado.</p>
                                        @if (is_array($log->new_values))
                                            <div class="mt-2 p-3 rounded font-mono text-[10px] max-w-lg overflow-x-auto border"
                                                style="background-color: var(--bg); border-color: var(--border-light); color: var(--text-secondary);">
                                                @foreach (array_filter($log->new_values) as $key => $val)
                                                    <div><strong>{{ $key }}:</strong>
                                                        {{ is_array($val) ? json_encode($val) : Str::limit($val, 50) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @elseif($log->event === 'deleted')
                                        <p class="font-semibold" style="color: var(--danger);">Registro removido del
                                            sistema.</p>
                                    @else
                                        <div class="space-y-1.5">
                                            @php
                                                $oldVals = $log->old_values ?? [];
                                                $newVals = $log->new_values ?? [];
                                                $changes = [];
                                                foreach ($newVals as $key => $val) {
                                                    $oldVal = $oldVals[$key] ?? null;
                                                    if ($oldVal != $val && !in_array($key, ['updated_at'])) {
                                                        $changes[$key] = ['old' => $oldVal, 'new' => $val];
                                                    }
                                                }
                                            @endphp

                                            @if (count($changes) > 0)
                                                <div class="grid grid-cols-3 gap-x-2 gap-y-1 font-mono text-[11px] max-w-xl"
                                                    style="color: var(--text-secondary);">
                                                    @foreach ($changes as $field => $change)
                                                        <div class="font-bold text-right"
                                                            style="color: var(--text-secondary);">{{ $field }}:
                                                        </div>
                                                        <div class="line-through" style="color: var(--text-tertiary);">
                                                            {{ is_array($change['old']) ? json_encode($change['old']) : Str::limit($change['old'], 25) }}
                                                        </div>
                                                        <div class="font-semibold" style="color: var(--primary);">→
                                                            {{ is_array($change['new']) ? json_encode($change['new']) : Str::limit($change['new'], 25) }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="italic" style="color: var(--text-tertiary);">No se registraron
                                                    cambios en campos clave.</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="table-cell text-xs" style="color: var(--text-tertiary);">
                                    <div class="font-mono">{{ $log->ip_address }}</div>
                                    <div class="truncate max-w-[150px] mt-0.5" title="{{ $log->user_agent }}">
                                        {{ $log->user_agent }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                            style="background-color: var(--primary-light);">
                                            <i class="fa-solid fa-list-check text-xl" style="color: var(--primary);"></i>
                                        </div>
                                        <p class="text-sm font-medium" style="color: var(--text-tertiary);">
                                            No se encontraron registros en la bitácora de auditoría.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="px-6 py-4 border-t" style="border-color: var(--border-light); background-color: var(--bg);">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
