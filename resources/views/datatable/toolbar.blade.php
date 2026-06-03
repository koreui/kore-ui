@php
    $searchDebounce = $searchDebounce ?? 300;
    $perPageOptions = $perPageOptions ?? [];
    $perPage = $perPage ?? null;
    $translations = $translations ?? [];
    $filterDefs = $filterDefs ?? [];
    $filterCount = $filterCount ?? 0;
    $filterLayout = $filterLayout ?? 'popover';
    $bulkActions = $bulkActions ?? [];
    $filtersExpanded = $filtersExpanded ?? false;
    $columnSelectEnabled = $columnSelectEnabled ?? false;
    $allColumns = $allColumns ?? [];
    $deselectedColumns = $deselectedColumns ?? [];
    $isSlideDown = $filterLayout === 'slide-down';
    $exportEnabled = $exportEnabled ?? false;
    $exportFormats = $exportFormats ?? [];
    $koreSlots = $koreSlots ?? [];
    $rowIds = $rowIds ?? [];
    $total = $total ?? 0;
@endphp

<div class="border-b border-kore-border">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-4 py-3">
        {{-- Left: Search + Filters --}}
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            {{-- Search --}}
            <div class="w-full sm:w-auto sm:min-w-[260px]">
                <x-kore::input
                    type="search"
                    icon="search"
                    size="sm"
                    :placeholder="$translations['search'] ?? 'Buscar...'"
                    :clearable="true"
                    wire:model.live.debounce.300ms="search"
                    data-datatable-search
                />
            </div>

            {{-- Filter button — all layouts except inline --}}
            @if($filterLayout !== 'inline' && count($filterDefs) > 0)
                @include('kore::datatable.filters.' . $filterLayout, [
                    'filterDefs'   => $filterDefs,
                    'filterCount'  => $filterCount,
                    'translations' => $translations,
                ])
            @endif
        </div>

        {{-- Right: Export + Column Select + Per Page --}}
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto sm:justify-end">
            {{-- Export --}}
                @if($exportEnabled)
                    @if(count($exportFormats) === 1)
                        <button
                            type="button"
                            wire:click="exportAs('{{ $exportFormats[0] }}')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-kore-fg bg-kore-bg border border-kore-input rounded-kore-md hover:bg-kore-muted transition-colors"
                        >
                            <x-lucide-download class="size-4" />
                            <span>{{ $translations['export'] ?? 'Exportar' }}</span>
                        </button>
                    @else
                        {{-- Teleported to body (z-50) so it isn't clipped by the table's overflow-hidden --}}
                        <x-kore::dropdown width="160">
                            <x-slot:trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-kore-fg bg-kore-bg border border-kore-input rounded-kore-md hover:bg-kore-muted transition-colors"
                                >
                                    <x-lucide-download class="size-4" />
                                    <span>{{ $translations['export'] ?? 'Exportar' }}</span>
                                    <x-lucide-chevron-down class="size-3" />
                                </button>
                            </x-slot:trigger>

                            @foreach($exportFormats as $format)
                                <button
                                    type="button"
                                    wire:click="exportAs('{{ $format }}')"
                                    x-on:click="close()"
                                    class="w-full text-left px-3 py-1.5 text-sm text-kore-fg hover:bg-kore-muted transition-colors"
                                >
                                    {{ strtoupper($format) }}
                                </button>
                            @endforeach
                        </x-kore::dropdown>
                    @endif
                @endif

                {{-- Column Select --}}
                @if($columnSelectEnabled ?? false)
                    @include('kore::datatable.column-select', [
                        'allColumns'        => $allColumns ?? [],
                        'deselectedColumns' => $deselectedColumns ?? [],
                        'translations'      => $translations,
                    ])
                @endif

                {{-- Per Page --}}
            <div class="flex items-center gap-2 text-sm text-kore-muted-fg">
                <span>{{ $translations['per_page'] ?? 'Por página' }}</span>
                <select
                    wire:model.live="perPage"
                    class="bg-kore-bg text-kore-fg border border-kore-input rounded-kore-md text-sm py-1 pl-2 pr-7 focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary"
                >
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" @selected(($perPage ?? null) == $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Slot: toolbar-right-end --}}
            @if($slot = ($koreSlots['toolbar-right-end'] ?? null))
                @include($slot['view'], array_merge(['component' => $this], $slot['params']))
            @endif
        </div>
    </div>

    {{-- Bulk actions: its own full-width row so showing/hiding it never reflows
         the search/filters/export/per-page controls above. --}}
    @include('kore::datatable.bulk-actions', [
        'bulkActions'  => $bulkActions,
        'translations' => $translations,
        'rowIds'       => $rowIds,
        'total'        => $total,
    ])

    {{-- Slide-down panel (below toolbar row) --}}
    @if($isSlideDown && count($filterDefs) > 0)
        @include('kore::datatable.filters.slide-down-panel', [
            'filterDefs'      => $filterDefs,
            'filtersExpanded' => $filtersExpanded,
        ])
    @endif

    {{-- Inline filters (below the toolbar row) --}}
    @if($filterLayout === 'inline' && count($filterDefs) > 0)
        <div class="px-4 pb-3">
            @include('kore::datatable.filters.inline', ['filterDefs' => $filterDefs])
        </div>
    @endif
</div>
