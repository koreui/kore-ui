@php
    $bulkActions = $bulkActions ?? [];
    $translations = $translations ?? [];
@endphp

@if(count($bulkActions) > 0)
    <div x-show="hasSelection" x-cloak wire:ignore.self class="flex flex-wrap items-center gap-2">
        <span class="text-sm text-kore-muted-fg">
            <span x-text="(selectAllMatching ? totalRows : selectedCount) + ' {{ $translations['selected'] ?? 'seleccionado(s)' }}'"></span>
            <template x-if="!selectAllMatching && hasOffPageSelection">
                <span class="text-kore-muted-fg/70">{{ $translations['incl_other_pages'] ?? '(incl. otras páginas)' }}</span>
            </template>
        </span>

        {{-- Select all rows matching the current filters --}}
        <template x-if="canSelectAllMatching">
            <button
                type="button"
                x-on:click="enableSelectAllMatching()"
                class="text-sm font-medium text-kore-primary hover:underline"
            >
                {{ $translations['select_all_matching'] ?? 'Seleccionar los' }} <span x-text="totalRows"></span>
            </button>
        </template>
        <template x-if="selectAllMatching">
            <button
                type="button"
                x-on:click="clearSelection()"
                class="text-sm font-medium text-kore-primary hover:underline"
            >
                {{ $translations['clear_selection'] ?? 'Limpiar selección' }}
            </button>
        </template>

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
                    x-on:click="runBulk('{{ $action->getIdentifier() }}'); close()"
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
