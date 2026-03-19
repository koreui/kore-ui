@php
    $allColumns = $allColumns ?? [];
    $deselectedColumns = $deselectedColumns ?? [];
    $translations = $translations ?? [];
@endphp

<x-kore::dropdown position="bottom-end" width="220" :persistent="true" max-height="70vh">
    <x-slot:trigger>
        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium border border-kore-border rounded-kore-md bg-kore-surface hover:bg-kore-muted transition-colors text-kore-fg">
            <x-lucide-columns-3 class="size-4 text-kore-muted-fg" />
            <span>{{ $translations['columns'] ?? 'Columnas' }}</span>
        </button>
    </x-slot:trigger>

    <div class="px-3 py-2 space-y-1">
        @foreach($allColumns as $column)
            <label class="flex items-center gap-2 py-1 cursor-pointer text-sm text-kore-fg hover:text-kore-primary transition-colors">
                <input
                    type="checkbox"
                    value="{{ $column->getField() }}"
                    @checked(!in_array($column->getField(), $deselectedColumns))
                    wire:click="toggleColumnVisibility('{{ $column->getField() }}')"
                    class="rounded border-kore-input text-kore-primary focus:ring-kore-ring"
                />
                <span>{{ $column->getLabel() }}</span>
            </label>
        @endforeach
    </div>

    @if(count($deselectedColumns) > 0)
        <div class="border-t border-kore-border px-3 py-2">
            <button
                type="button"
                wire:click="resetColumnSelect"
                class="text-sm text-kore-primary hover:text-kore-primary/80 transition-colors"
            >
                {{ $translations['reset_columns'] ?? 'Restablecer' }}
            </button>
        </div>
    @endif
</x-kore::dropdown>
