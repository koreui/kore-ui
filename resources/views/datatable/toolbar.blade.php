@php
    $searchDebounce = $searchDebounce ?? 300;
    $perPageOptions = $perPageOptions ?? [];
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

        {{-- Right: Bulk Actions + Column Select + Per Page --}}
        <div class="flex items-center gap-3">
            {{-- Bulk Actions --}}
            @include('kore::datatable.bulk-actions', [
                'bulkActions'  => $bulkActions,
                'translations' => $translations,
            ])

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
                    class="bg-kore-bg text-kore-fg border border-kore-input rounded-kore-md text-sm py-1 px-2 focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary"
                >
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

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
