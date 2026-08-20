@php
    $primaryKey = $primaryKey ?? 'id';
    $collapsedColumns = $collapsedColumns ?? [];

    // La lista de campos colapsados se construye UNA vez. Antes se recalculaba
    // dentro del in_array, es decir, una vez por columna y por partición.
    $collapsedFields = collect($collapsedColumns)->map(fn ($c) => $c->getField())->all();

    $visibleColumns = collect($columns)
        ->reject(fn ($col) => in_array($col->getField(), $collapsedFields, true))
        ->values();

    $collapsedCols = collect($columns)
        ->filter(fn ($col) => in_array($col->getField(), $collapsedFields, true))
        ->values();
@endphp

<table class="min-w-full divide-y divide-kore-border">
    <thead class="bg-kore-muted/50">
        <tr>
            @if(count($collapsedCols) > 0)
                <th class="w-10" :class="headerDensityClasses"></th>
            @endif

            @if($selectionEnabled)
                <th class="w-10 text-center" :class="headerDensityClasses">
                    <input
                        type="checkbox"
                        aria-label="Seleccionar todo"
                        @checked($this->isPageFullySelected($rowIds ?? []))
                        data-checked="{{ $this->isPageFullySelected($rowIds ?? []) ? '1' : '0' }}"
                        data-indeterminate="{{ $this->isPagePartiallySelected($rowIds ?? []) ? '1' : '0' }}"
                        x-init="$el.indeterminate = $el.dataset.indeterminate === '1'"
                        wire:click="toggleSelectAll"
                        class="rounded border-kore-input text-kore-primary focus:ring-kore-ring"
                    />
                </th>
            @endif

            @foreach($visibleColumns as $column)
                <th
                    scope="col"
                    @if($column->isSortable()) aria-sort="{{ $this->getSortDirection($column->getSortField()) === 'asc' ? 'ascending' : ($this->getSortDirection($column->getSortField()) === 'desc' ? 'descending' : 'none') }}" @endif
                    class="{{ $column->getAlign() === 'center' ? 'text-center' : ($column->getAlign() === 'right' ? 'text-right' : 'text-left') }} font-semibold text-kore-muted-fg uppercase tracking-wider whitespace-nowrap"
                    :class="headerDensityClasses"
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
                            aria-label="Ordenar por {{ $column->getLabel() }}"
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

    <tbody class="divide-y divide-kore-border">
        @forelse($rows as $row)
            @php
                $rowId = data_get($row, $primaryKey);
                $rowSelected = ($selectionEnabled ?? false) && $this->isRowSelected($rowId);
            @endphp
            <tr
                wire:key="row-{{ $rowId }}"
                class="hover:bg-kore-muted/40 transition-colors {{ $rowSelected ? 'bg-kore-primary/5' : '' }}"
            >
                @if(count($collapsedCols) > 0)
                    <td class="w-10 text-center" :class="densityClasses">
                        <button
                            type="button"
                            x-on:click="toggleExpand(@js((string) $rowId))"
                            class="p-0.5 rounded hover:bg-kore-muted transition-colors"
                        >
                            <x-lucide-chevron-right
                                class="size-4 text-kore-muted-fg transition-transform duration-200"
                                x-bind:class="isExpanded(@js((string) $rowId)) ? 'rotate-90' : ''"
                            />
                        </button>
                    </td>
                @endif

                @if($selectionEnabled)
                    <td class="w-10 text-center" :class="densityClasses">
                        <input
                            type="checkbox"
                            aria-label="Seleccionar fila"
                            value="{{ $rowId }}"
                            @checked($rowSelected)
                            data-checked="{{ $rowSelected ? '1' : '0' }}"
                            x-on:click="onRowCheckboxClick(@js($rowId), $event)"
                            class="rounded border-kore-input text-kore-primary focus:ring-kore-ring"
                        />
                    </td>
                @endif

                @foreach($visibleColumns as $column)
                    <td
                        class="{{ $column->getAlign() === 'center' ? 'text-center' : ($column->getAlign() === 'right' ? 'text-right' : 'text-left') }} text-kore-fg {{ $column->isWrap() ? '' : 'whitespace-nowrap' }}"
                        :class="densityClasses"
                    >
                        @include('kore::datatable.cell-description', ['column' => $column, 'row' => $row, 'slot' => 'above'])
                        @if($column->getType() !== 'text')
                            @include('kore::datatable.columns.' . $column->getType(), [
                                'column' => $column,
                                'row' => $row,
                                'value' => $column->getValue($row),
                                'primaryKey' => $primaryKey,
                            ])
                        @elseif($column->isHtml())
                            {!! $column->getValue($row) !!}
                        @else
                            {{ $column->getValue($row) }}
                        @endif
                        @include('kore::datatable.cell-description', ['column' => $column, 'row' => $row, 'slot' => 'below'])
                    </td>
                @endforeach
            </tr>

            {{-- Expanded row with collapsed columns --}}
            @if(count($collapsedCols) > 0)
                <tr wire:key="row-detail-{{ $rowId }}" x-show="isExpanded(@js((string) $rowId))" x-cloak class="bg-kore-muted/20">
                    <td colspan="{{ count($visibleColumns) + ($selectionEnabled ? 2 : 1) }}">
                        <dl class="px-6 py-3 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                            @foreach($collapsedCols as $col)
                                <div>
                                    <dt class="text-kore-muted-fg text-xs font-medium">{{ $col->getLabel() }}</dt>
                                    <dd class="text-kore-fg mt-0.5">
                                        @if($col->getType() !== 'text')
                                            @include('kore::datatable.columns.' . $col->getType(), [
                                                'column' => $col,
                                                'row' => $row,
                                                'value' => $col->getValue($row),
                                                'primaryKey' => $primaryKey,
                                            ])
                                        @elseif($col->isHtml())
                                            {!! $col->getValue($row) !!}
                                        @else
                                            {{ $col->getValue($row) ?? '—' }}
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + ($selectionEnabled ? 1 : 0) + (count($collapsedCols) > 0 ? 1 : 0) }}">
                    <x-kore::empty-state
                        :title="$emptyText ?? 'No se encontraron resultados'"
                        :icon="$emptyIcon ?? 'inbox'"
                    />
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
