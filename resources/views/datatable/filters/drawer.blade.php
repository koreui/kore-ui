@php
    $filterDefs = $filterDefs ?? [];
    $filterCount = $filterCount ?? 0;
    $translations = $translations ?? [];
@endphp

@if(count($filterDefs) > 0)
    <div wire:ignore x-data="{ filtersOpen: false }" x-on:keydown.escape.window="filtersOpen = false">
        {{-- Trigger --}}
        <button
            type="button"
            x-on:click="filtersOpen = true"
            class="inline-flex items-center gap-1.5 rounded-kore-md border border-kore-input bg-kore-bg px-3 py-1.5 text-sm text-kore-fg hover:bg-kore-muted transition-colors"
        >
            <x-lucide-filter class="size-4" />
            <span>{{ $translations['filters'] ?? 'Filtros' }}</span>
            <template x-if="$wire.getActiveFilterCount() > 0">
                <span
                    class="inline-flex items-center justify-center size-5 rounded-full bg-kore-primary text-kore-primary-fg text-xs font-medium"
                    x-text="$wire.getActiveFilterCount()"
                ></span>
            </template>
        </button>

        {{-- Backdrop --}}
        <template x-teleport="body">
            <div
                data-kore-teleport
                x-show="filtersOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-on:click="filtersOpen = false"
                x-cloak
                class="fixed inset-0 z-[60] bg-black/30 backdrop-blur-[1px]"
            ></div>
        </template>

        {{-- Drawer panel --}}
        <template x-teleport="body">
            <div
                data-kore-teleport
                x-show="filtersOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                x-cloak
                class="fixed inset-y-0 right-0 z-[61] w-full sm:max-w-sm bg-kore-surface border-l border-kore-border shadow-xl flex flex-col"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-kore-border">
                    <h3 class="text-base font-semibold text-kore-fg">
                        {{ $translations['filters'] ?? 'Filtros' }}
                    </h3>
                    <button
                        type="button"
                        x-on:click="filtersOpen = false"
                        class="text-kore-muted-fg hover:text-kore-fg transition-colors"
                    >
                        <x-lucide-x class="size-5" />
                    </button>
                </div>

                {{-- Filters --}}
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">
                    @include('kore::datatable.filters.fields', ['filterDefs' => $filterDefs])
                </div>

                {{-- Footer --}}
                <div class="px-5 py-4 border-t border-kore-border flex items-center gap-3">
                    <button
                        type="button"
                        x-on:click="$wire.resetAllFilters()"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-kore-md border border-kore-input bg-kore-bg px-3 py-2 text-sm text-kore-fg hover:bg-kore-muted transition-colors"
                    >
                        <x-lucide-filter-x class="size-4" />
                        {{ $translations['clear_filters'] ?? 'Limpiar filtros' }}
                    </button>
                    <button
                        type="button"
                        x-on:click="filtersOpen = false"
                        class="flex-1 inline-flex items-center justify-center rounded-kore-md bg-kore-primary px-3 py-2 text-sm font-medium text-kore-primary-fg hover:bg-kore-primary/90 transition-colors"
                    >
                        {{ $translations['apply'] ?? 'Aplicar' }}
                    </button>
                </div>
            </div>
        </template>
    </div>
@endif
