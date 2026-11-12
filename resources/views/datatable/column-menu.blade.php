{{-- Menú de la cabecera: ordenar, fijar y ocultar desde la propia columna.
     Se teleporta a body (lo hace x-kore::dropdown), así que el overflow del
     contenedor de scroll no lo recorta. --}}
@php
    $field       = $column->getField();
    $sortField   = $column->getSortField();
    $currentSort = $column->isSortable() ? $this->getSortDirection($sortField) : null;
    $currentPin  = $column->isPinned() ? $column->getPinnedSide() : null;
    $t           = $translations ?? [];
@endphp

<x-kore::dropdown position="bottom-end" width="200">
    <x-slot:trigger>
        <button
            type="button"
            aria-label="{{ ($t['column_options'] ?? 'Opciones de columna') . ': ' . $column->getLabel() }}"
            class="p-0.5 rounded-kore-sm text-kore-muted-fg hover:text-kore-fg hover:bg-kore-border/60 kore-transition kore-focus opacity-0 group-hover/th:opacity-100 focus-visible:opacity-100 aria-expanded:opacity-100 [@media(hover:none)]:opacity-100"
        >
            <x-lucide-chevron-down class="size-3.5" />
        </button>
    </x-slot:trigger>

    @if($column->isSortable())
        <button
            type="button"
            wire:click="setSort(@js($sortField), 'asc')"
            x-on:click="close()"
            class="flex items-center gap-2 w-full px-2.5 py-1.5 text-sm text-left kore-option hover:bg-kore-accent focus:bg-kore-accent {{ $currentSort === 'asc' ? 'text-kore-primary-text font-medium' : 'text-kore-fg' }}"
            role="menuitem"
        >
            <x-lucide-arrow-up class="size-4 shrink-0 text-kore-muted-fg" />
            <span>{{ $t['sort_asc'] ?? 'Ordenar ascendente' }}</span>
        </button>

        <button
            type="button"
            wire:click="setSort(@js($sortField), 'desc')"
            x-on:click="close()"
            class="flex items-center gap-2 w-full px-2.5 py-1.5 text-sm text-left kore-option hover:bg-kore-accent focus:bg-kore-accent {{ $currentSort === 'desc' ? 'text-kore-primary-text font-medium' : 'text-kore-fg' }}"
            role="menuitem"
        >
            <x-lucide-arrow-down class="size-4 shrink-0 text-kore-muted-fg" />
            <span>{{ $t['sort_desc'] ?? 'Ordenar descendente' }}</span>
        </button>

        @if($currentSort)
            <button
                type="button"
                wire:click="removeSortBy(@js($sortField))"
                x-on:click="close()"
                class="flex items-center gap-2 w-full px-2.5 py-1.5 text-sm text-left text-kore-fg kore-option hover:bg-kore-accent focus:bg-kore-accent"
                role="menuitem"
            >
                <x-lucide-x class="size-4 shrink-0 text-kore-muted-fg" />
                <span>{{ $t['clear_sort'] ?? 'Quitar orden' }}</span>
            </button>
        @endif

        <x-kore::dropdown.separator />
    @endif

    <button
        type="button"
        wire:click="toggleColumnPin(@js($field), 'left')"
        x-on:click="close()"
        class="flex items-center gap-2 w-full px-2.5 py-1.5 text-sm text-left kore-option hover:bg-kore-accent focus:bg-kore-accent {{ $currentPin === 'left' ? 'text-kore-primary-text font-medium' : 'text-kore-fg' }}"
        role="menuitem"
    >
        <x-lucide-pin class="size-4 shrink-0 text-kore-muted-fg" />
        <span>{{ $currentPin === 'left' ? ($t['unpin'] ?? 'Soltar columna') : ($t['pin_left'] ?? 'Fijar a la izquierda') }}</span>
    </button>

    <button
        type="button"
        wire:click="toggleColumnPin(@js($field), 'right')"
        x-on:click="close()"
        class="flex items-center gap-2 w-full px-2.5 py-1.5 text-sm text-left kore-option hover:bg-kore-accent focus:bg-kore-accent {{ $currentPin === 'right' ? 'text-kore-primary-text font-medium' : 'text-kore-fg' }}"
        role="menuitem"
    >
        <x-lucide-pin class="size-4 shrink-0 text-kore-muted-fg rotate-90" />
        <span>{{ $currentPin === 'right' ? ($t['unpin'] ?? 'Soltar columna') : ($t['pin_right'] ?? 'Fijar a la derecha') }}</span>
    </button>

    @if($columnSelectEnabled ?? false)
        <x-kore::dropdown.separator />

        <button
            type="button"
            wire:click="toggleColumnVisibility(@js($field))"
            x-on:click="close()"
            class="flex items-center gap-2 w-full px-2.5 py-1.5 text-sm text-left text-kore-fg kore-option hover:bg-kore-accent focus:bg-kore-accent"
            role="menuitem"
        >
            <x-lucide-eye-off class="size-4 shrink-0 text-kore-muted-fg" />
            <span>{{ $t['hide_column'] ?? 'Ocultar columna' }}</span>
        </button>
    @endif
</x-kore::dropdown>
