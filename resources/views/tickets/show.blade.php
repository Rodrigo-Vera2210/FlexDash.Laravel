@extends('layouts.app')

@section('title', 'Detalle del Ticket #' . $ticket->id)
@section('page-title', 'Ticket #' . $ticket->id)
@section('page-subtitle', 'Visualiza el estado y discute con soporte técnico')

@section('content')
    <div class="mt-2 max-w-5xl mx-auto page-fade grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left side: Ticket Details --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="card-panel p-5">
                <div class="flex items-center justify-between mb-4 border-b border-[color:var(--border-light)] pb-3">
                    <h3 class="font-bold text-[color:var(--text-main)]">Información del Ticket</h3>
                    <a href="{{ route('tickets.index') }}" class="text-xs text-[color:var(--primary)] hover:underline">
                        Volver
                    </a>
                </div>

                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-[color:var(--text-tertiary)] uppercase tracking-wider font-bold">Título</p>
                        <p class="text-sm font-semibold text-[color:var(--text-main)]">{{ $ticket->title }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-[color:var(--text-tertiary)] uppercase tracking-wider font-bold">Estado</p>
                        <span class="inline-block mt-1">
                            @if ($ticket->status === 'aprobado')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">Aprobado</span>
                            @elseif ($ticket->status === 'rechazado')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">Rechazado</span>
                            @elseif ($ticket->status === 'en proceso')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400">En proceso</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800 dark:bg-gray-800/60 dark:text-gray-400">Pendiente</span>
                            @endif
                        </span>
                    </div>

                    <div>
                        <p class="text-xs text-[color:var(--text-tertiary)] uppercase tracking-wider font-bold">Severidad</p>
                        <span class="inline-block mt-1">
                            @if ($ticket->severity === 'alto')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Alto</span>
                            @elseif ($ticket->severity === 'medio')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Medio</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Bajo</span>
                            @endif
                        </span>
                    </div>

                    <div>
                        <p class="text-xs text-[color:var(--text-tertiary)] uppercase tracking-wider font-bold">Reportado el</p>
                        <p class="text-sm text-[color:var(--text-main)]">{{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="pt-3 border-t border-[color:var(--border-light)]">
                        <p class="text-xs text-[color:var(--text-tertiary)] uppercase tracking-wider font-bold">Descripción</p>
                        <p class="text-sm mt-1 text-[color:var(--text-main)] whitespace-pre-wrap">{{ $ticket->description }}</p>
                    </div>

                    {{-- Evidence Attachments --}}
                    @if ($ticket->attachments->isNotEmpty())
                        <div class="pt-3 border-t border-[color:var(--border-light)]">
                            <p class="text-xs text-[color:var(--text-tertiary)] uppercase tracking-wider font-bold mb-2">Imágenes de Evidencia</p>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($ticket->attachments as $attachment)
                                    <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="block rounded-lg overflow-hidden border border-[color:var(--border-light)] hover:opacity-80 transition-opacity">
                                        <img src="{{ asset('storage/' . $attachment->file_path) }}" class="w-full h-24 object-cover" alt="Evidencia">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Technical Trace --}}
                    @if ($ticket->error_trace)
                        <div class="pt-3 border-t border-[color:var(--border-light)]" x-data="{ open: false }">
                            <button type="button" @click="open = !open" class="flex items-center justify-between w-full text-xs text-[color:var(--text-tertiary)] uppercase tracking-wider font-bold hover:text-[color:var(--primary)] transition-colors">
                                <span>Traza Técnica</span>
                                <i class="fa-solid" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                            <div x-show="open" class="mt-2 p-2 rounded-lg bg-slate-900 border border-slate-800 text-[10px] font-mono text-slate-300 max-h-40 overflow-y-auto" style="display: none;">
                                <pre class="whitespace-pre-wrap">{{ $ticket->error_trace }}</pre>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right side: Chat timeline --}}
        <div class="lg:col-span-2 flex flex-col">
            <div class="card-panel p-5 flex flex-col flex-1" style="min-height: 400px;">
                <h3 class="font-bold text-[color:var(--text-main)] mb-4 border-b border-[color:var(--border-light)] pb-3">
                    Discusión del Ticket
                </h3>

                {{-- Messages List --}}
                <div class="flex-1 overflow-y-auto space-y-4 pr-1 mb-4" style="max-height: 380px;">
                    @if ($ticket->messages->isEmpty())
                        <div class="text-center text-[color:var(--text-tertiary)] py-8">
                            <i class="fa-regular fa-comments text-3xl mb-2 block"></i>
                            <p class="text-sm font-medium">Aún no hay mensajes. Escribe un comentario a continuación para iniciar la conversación.</p>
                        </div>
                    @else
                        @foreach ($ticket->messages as $message)
                            @php
                                $isMe = $message->user_id === Auth::id();
                                $isSuperAdmin = $message->user->role === 'superadmin';
                            @endphp
                            <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[85%] rounded-2xl px-4 py-2.5 {{ $isMe ? 'bg-[#0A7EA5] text-white rounded-tr-none' : ($isSuperAdmin ? 'bg-amber-100 dark:bg-amber-950/20 text-amber-900 dark:text-amber-300 border border-amber-200 dark:border-amber-900/30 rounded-tl-none' : 'bg-gray-100 dark:bg-gray-800 text-[color:var(--text-main)] rounded-tl-none') }}">
                                    <div class="flex items-center justify-between gap-4 mb-1">
                                        <span class="text-[10px] font-bold opacity-75">
                                            {{ $isMe ? 'Tú' : $message->user->name }} 
                                            @if ($isSuperAdmin) (Soporte) @endif
                                        </span>
                                        <span class="text-[9px] opacity-60">
                                            {{ $message->created_at->format('H:i') }}
                                        </span>
                                    </div>
                                    <p class="text-sm whitespace-pre-wrap">{{ $message->message }}</p>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Send Message Form --}}
                <form method="POST" action="{{ route('tickets.messages.store', $ticket) }}" class="border-t border-[color:var(--border-light)] pt-4 mt-auto">
                    @csrf
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <textarea name="message" rows="2" class="input-solid w-full" placeholder="Escribe un mensaje para soporte técnico..." required></textarea>
                        </div>
                        <button type="submit" class="btn-primary py-3 px-4 shrink-0">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
