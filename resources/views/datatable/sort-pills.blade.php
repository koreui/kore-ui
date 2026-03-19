@php
    $activeSorts = $activeSorts ?? [];
    $translations = $translations ?? [];
@endphp

@if(count($activeSorts) > 0)
    <div class="flex flex-wrap items-center gap-2 px-4 py-2 border-b border-kore-border bg-kore-muted/30">
        <span class="text-xs text-kore-muted-fg font-medium">
            {{ $translations['sorted_by'] ?? 'Ordenado por' }}:
        </span>

        @foreach($activeSorts as $sort)
            <span class="inline-flex items-center gap-1 rounded-full bg-kore-info/10 text-kore-info px-2.5 py-0.5 text-xs font-medium">
                {{ $sort['label'] }}
                @if($sort['direction'] === 'asc')
                    <x-lucide-arrow-up class="size-3" />
                @else
                    <x-lucide-arrow-down class="size-3" />
                @endif
                <button
                    type="button"
                    wire:click="removeSortBy('{{ $sort['field'] }}')"
                    class="ml-0.5 inline-flex items-center justify-center size-3.5 rounded-full hover:bg-kore-info/20 transition-colors"
                >
                    <x-lucide-x class="size-2.5" />
                </button>
            </span>
        @endforeach

        <button
            type="button"
            wire:click="clearSorts"
            class="text-xs text-kore-muted-fg hover:text-kore-fg transition-colors underline underline-offset-2"
        >
            {{ $translations['clear_sorts'] ?? 'Limpiar ordenamiento' }}
        </button>
    </div>
@endif
