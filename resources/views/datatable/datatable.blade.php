<div
    x-data="KoreDataTable({ density: '{{ $density }}', rowIds: {{ Js::from($rowIds ?? []) }}, slideDownOpen: {{ ($filtersExpanded ?? false) ? 'true' : 'false' }}, responsiveMode: '{{ $responsiveMode ?? 'scroll' }}', responsiveBreakpoint: {{ $responsiveBreakpoint ?? 768 }} })"
    class="rounded-kore-lg border border-kore-border bg-kore-surface overflow-hidden"
>
    {{-- Toolbar: search + filters + per page + bulk actions --}}
    @include('kore::datatable.toolbar', [
        'searchDebounce'      => $searchDebounce,
        'perPageOptions'      => $perPageOptions,
        'translations'        => $translations,
        'filterDefs'          => $filterDefs ?? [],
        'filterCount'         => $filterCount ?? 0,
        'filterLayout'        => $filterLayout ?? 'popover',
        'filtersExpanded'     => $filtersExpanded ?? false,
        'bulkActions'         => $bulkActions ?? [],
        'columnSelectEnabled' => $columnSelectEnabled ?? false,
        'allColumns'          => $allColumns ?? [],
        'deselectedColumns'   => $deselectedColumns ?? [],
    ])

    {{-- Filter pills --}}
    @include('kore::datatable.filter-pills', [
        'activeFilters' => $activeFilters ?? [],
        'translations'  => $translations,
    ])

    {{-- Table --}}
    <div class="relative overflow-x-auto">
        {{-- Loading overlay --}}
        <div wire:loading.flex class="absolute inset-0 z-10 items-center justify-center bg-kore-surface/80 backdrop-blur-[1px]">
            <x-kore::loading size="md" />
        </div>

        {{-- Card mode (responsive) --}}
        @if(($responsiveMode ?? 'scroll') === 'card')
            <div x-show="isMobileView" x-cloak>
                @include('kore::datatable.responsive.card', [
                    'columns' => $columns,
                    'rows' => $rows,
                    'selectionEnabled' => $selectionEnabled ?? false,
                    'primaryKey' => $primaryKey ?? 'id',
                ])
            </div>
        @endif

        {{-- Collapse mode (responsive) --}}
        @if(($responsiveMode ?? 'scroll') === 'collapse')
            <div x-show="isMobileView" x-cloak>
                @include('kore::datatable.responsive.collapse', [
                    'columns' => $columns,
                    'collapsedColumns' => $collapsedColumns ?? [],
                    'rows' => $rows,
                    'selectionEnabled' => $selectionEnabled ?? false,
                    'primaryKey' => $primaryKey ?? 'id',
                ])
            </div>
        @endif

        <table @if(($responsiveMode ?? 'scroll') !== 'scroll') x-show="!isMobileView" @endif class="min-w-full divide-y divide-kore-border">
            {{-- Header --}}
            <thead class="bg-kore-muted/50">
                <tr>
                    @if($selectionEnabled ?? false)
                        <th class="w-10 text-center" :class="headerDensityClasses">
                            <input
                                type="checkbox"
                                x-bind:checked="isAllSelected"
                                x-bind:indeterminate="isIndeterminate"
                                x-on:change="toggleAll()"
                                class="rounded border-kore-input text-kore-primary focus:ring-kore-ring"
                            />
                        </th>
                    @endif

                    @foreach($columns as $column)
                        <th
                            class="{{ $column->getAlign() === 'center' ? 'text-center' : ($column->getAlign() === 'right' ? 'text-right' : 'text-left') }} font-semibold text-kore-muted-fg uppercase tracking-wider whitespace-nowrap"
                            :class="headerDensityClasses"
                            @if($column->getWidth()) style="width: {{ $column->getWidth() }}px;" @endif
                            @if($column->getMinWidth()) style="min-width: {{ $column->getMinWidth() }}px;" @endif
                        >
                            @if($column->isSortable())
                                @php
                                    $sortDir = $this->getSortDirection($column->getSortField());
                                    $sortIcon = match($sortDir) {
                                        'asc'   => 'arrow-up',
                                        'desc'  => 'arrow-down',
                                        default => 'arrow-up-down',
                                    };
                                    $sortIconClass = $sortDir
                                        ? 'size-3.5 text-kore-fg'
                                        : 'size-3.5 text-kore-muted-fg/50 group-hover:text-kore-muted-fg transition-colors';
                                @endphp
                                <button
                                    type="button"
                                    wire:click="sortBy('{{ $column->getSortField() }}')"
                                    class="inline-flex items-center gap-1 group hover:text-kore-fg transition-colors"
                                >
                                    <span>{{ $column->getLabel() }}</span>
                                    <x-dynamic-component :component="'lucide-' . $sortIcon" :class="$sortIconClass" />
                                </button>
                            @else
                                <span>{{ $column->getLabel() }}</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>

            {{-- Body --}}
            <tbody class="divide-y divide-kore-border">
                @forelse($rows as $row)
                    <tr
                        class="hover:bg-kore-muted/40 transition-colors"
                        @if($selectionEnabled ?? false)
                            x-bind:class="isSelected('{{ data_get($row, $primaryKey ?? 'id') }}') ? 'bg-kore-primary/5' : ''"
                        @endif
                    >
                        @if($selectionEnabled ?? false)
                            <td class="w-10 text-center" :class="densityClasses">
                                <input
                                    type="checkbox"
                                    value="{{ data_get($row, $primaryKey ?? 'id') }}"
                                    x-bind:checked="isSelected('{{ data_get($row, $primaryKey ?? 'id') }}')"
                                    x-on:change="toggleRow('{{ data_get($row, $primaryKey ?? 'id') }}')"
                                    class="rounded border-kore-input text-kore-primary focus:ring-kore-ring"
                                />
                            </td>
                        @endif

                        @foreach($columns as $column)
                            <td
                                class="{{ $column->getAlign() === 'center' ? 'text-center' : ($column->getAlign() === 'right' ? 'text-right' : 'text-left') }} text-kore-fg {{ $column->isWrap() ? '' : 'whitespace-nowrap' }}"
                                :class="densityClasses"
                            >
                                @if($column->getType() !== 'text')
                                    @include('kore::datatable.columns.' . $column->getType(), [
                                        'column' => $column,
                                        'row' => $row,
                                        'value' => $column->getValue($row),
                                        'primaryKey' => $primaryKey ?? 'id',
                                    ])
                                @elseif($column->isHtml())
                                    {!! $column->getValue($row) !!}
                                @else
                                    {{ $column->getValue($row) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + (($selectionEnabled ?? false) ? 1 : 0) }}">
                            <x-kore::empty-state
                                :title="$emptyText"
                                :icon="$emptyIcon"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($rows->hasPages())
        @include('kore::datatable.pagination', [
            'paginator'   => $rows,
            'showingText' => $showingText,
        ])
    @endif
</div>
