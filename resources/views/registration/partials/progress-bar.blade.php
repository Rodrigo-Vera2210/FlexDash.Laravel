@php
    $steps = [
        'type'    => 'Tipo',
        'account' => 'Cuenta',
        'entity'  => 'Detalles',
        'review'  => 'Revisión',
        'verify'  => 'Verificación',
    ];
    $stepKeys   = array_keys($steps);
    $currentIdx = array_search($step ?? 'type', $stepKeys);
@endphp

<nav aria-label="Registration progress" class="bg-white border-b border-gray-200">
    <div class="max-w-2xl mx-auto px-4 py-3">
        <ol class="flex items-center justify-between gap-1 sm:gap-2">
            @foreach ($steps as $key => $label)
                @php
                    $idx         = array_search($key, $stepKeys);
                    $isActive    = $key === ($step ?? 'type');
                    $isCompleted = $idx < $currentIdx;
                    $isPending   = $idx > $currentIdx;
                @endphp
                <li class="flex flex-1 flex-col items-center gap-1">
                    {{-- Circle --}}
                    <span
                        aria-current="{{ $isActive ? 'step' : 'false' }}"
                        class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold
                            {{ $isCompleted ? 'bg-brand-teal text-white' : '' }}
                            {{ $isActive    ? 'bg-brand-blue text-white ring-2 ring-brand-blue ring-offset-2' : '' }}
                            {{ $isPending   ? 'bg-gray-200 text-gray-500' : '' }}"
                    >
                        @if ($isCompleted)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $idx + 1 }}
                        @endif
                    </span>
                    {{-- Label --}}
                    <span class="hidden sm:block text-xs font-medium
                        {{ $isActive    ? 'text-brand-blue' : '' }}
                        {{ $isCompleted ? 'text-brand-teal' : '' }}
                        {{ $isPending   ? 'text-gray-400'   : '' }}">
                        {{ $label }}
                    </span>
                </li>
                @if (!$loop->last)
                    <li class="flex-1 h-px bg-gray-200 mb-5" aria-hidden="true"></li>
                @endif
            @endforeach
        </ol>
    </div>
</nav>
