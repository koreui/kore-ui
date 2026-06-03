@php
    $bulkActions = $bulkActions ?? [];
    $translations = $translations ?? [];
    $rowIds = $rowIds ?? [];
    $total = $total ?? 0;
@endphp

@if(count($bulkActions) > 0 && $this->hasSelection())
    <div class="flex flex-wrap items-center gap-2 px-4 py-2 border-t border-kore-border bg-kore-primary/5">
        <span class="text-sm text-kore-muted-fg">
            <span>{{ $this->selectAllMatching ? $total : count($this->selected) }} {{ $translations['selected'] ?? 'seleccionado(s)' }}</span>
            @if(! $this->selectAllMatching && $this->hasOffPageSelection($rowIds))
                <span class="text-kore-muted-fg/70">{{ $translations['incl_other_pages'] ?? '(incl. otras páginas)' }}</span>
            @endif
        </span>

        {{-- Select all rows matching the current filters --}}
        @if($this->canSelectAllMatching($rowIds, $total))
            <button
                type="button"
                wire:click="enableSelectAllMatching"
                class="text-sm font-medium text-kore-primary hover:underline"
            >
                {{ $translations['select_all_matching'] ?? 'Seleccionar los' }} {{ $total }}
            </button>
        @endif
        @if($this->selectAllMatching)
            <button
                type="button"
                wire:click="clearSelection"
                class="text-sm font-medium text-kore-primary hover:underline"
            >
                {{ $translations['clear_selection'] ?? 'Limpiar selección' }}
            </button>
        @endif

        <x-kore::dropdown width="240">
            <x-slot:trigger>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-kore-md border border-kore-input bg-kore-bg px-3 py-1.5 text-sm text-kore-fg hover:bg-kore-muted transition-colors"
                >
                    <x-lucide-more-horizontal class="size-4" />
                    <span>{{ $translations['actions'] ?? 'Acciones' }}</span>
                </button>
            </x-slot:trigger>

            @foreach($bulkActions as $action)
                @if($action->hasSeparator())
                    <x-kore::dropdown.separator />
                @endif

                <button
                    type="button"
                    wire:click="runBulk('{{ $action->getIdentifier() }}')"
                    x-on:click="close()"
                    class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left transition-colors text-kore-fg hover:bg-kore-muted focus:bg-kore-muted focus:outline-none"
                    role="menuitem"
                >
                    @if($action->getIcon())
                        <x-dynamic-component :component="'lucide-' . $action->getIcon()" class="size-4 shrink-0 text-kore-muted-fg" />
                    @endif
                    <span>{{ $action->getLabel() }}</span>
                </button>
            @endforeach
        </x-kore::dropdown>
    </div>
@endif
