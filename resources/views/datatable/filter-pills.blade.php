@php
    $activeFilters = $activeFilters ?? [];
    $translations = $translations ?? [];
@endphp

@if(count($activeFilters) > 0)
    <div class="flex flex-wrap items-center gap-2 px-4 py-2 border-b border-kore-border bg-kore-muted/30">
        @foreach($activeFilters as $filter)
            <span class="inline-flex items-center gap-1 rounded-full bg-kore-primary/10 text-kore-primary px-2.5 py-0.5 text-xs font-medium">
                {{ $filter['pill'] }}
                <button
                    type="button"
                    wire:click="resetFilter('{{ $filter['key'] }}')"
                    class="ml-0.5 inline-flex items-center justify-center size-3.5 rounded-full hover:bg-kore-primary/20 transition-colors"
                >
                    <x-lucide-x class="size-2.5" />
                </button>
            </span>
        @endforeach

        <button
            type="button"
            wire:click="resetAllFilters"
            class="text-xs text-kore-muted-fg hover:text-kore-fg transition-colors underline underline-offset-2"
        >
            {{ $translations['clear_filters'] ?? 'Limpiar filtros' }}
        </button>
    </div>
@endif
