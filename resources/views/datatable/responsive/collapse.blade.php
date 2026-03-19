@php
    $primaryKey = $primaryKey ?? 'id';
    $collapsedColumns = $collapsedColumns ?? [];
    $visibleColumns = collect($columns)->reject(fn ($col) => in_array($col->getField(), collect($collapsedColumns)->map(fn ($c) => $c->getField())->all()))->values();
    $collapsedCols = collect($columns)->filter(fn ($col) => in_array($col->getField(), collect($collapsedColumns)->map(fn ($c) => $c->getField())->all()))->values();
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
                        x-bind:checked="isAllSelected"
                        x-bind:indeterminate="isIndeterminate"
                        x-on:change="toggleAll()"
                        class="rounded border-kore-input text-kore-primary focus:ring-kore-ring"
                    />
                </th>
            @endif

            @foreach($visibleColumns as $column)
                <th
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
            @php $rowId = data_get($row, $primaryKey); @endphp
            <tr
                class="hover:bg-kore-muted/40 transition-colors"
                @if($selectionEnabled)
                    x-bind:class="isSelected('{{ $rowId }}') ? 'bg-kore-primary/5' : ''"
                @endif
            >
                @if(count($collapsedCols) > 0)
                    <td class="w-10 text-center" :class="densityClasses">
                        <button
                            type="button"
                            x-on:click="toggleExpand('{{ $rowId }}')"
                            class="p-0.5 rounded hover:bg-kore-muted transition-colors"
                        >
                            <x-lucide-chevron-right
                                class="size-4 text-kore-muted-fg transition-transform duration-200"
                                x-bind:class="isExpanded('{{ $rowId }}') ? 'rotate-90' : ''"
                            />
                        </button>
                    </td>
                @endif

                @if($selectionEnabled)
                    <td class="w-10 text-center" :class="densityClasses">
                        <input
                            type="checkbox"
                            value="{{ $rowId }}"
                            x-bind:checked="isSelected('{{ $rowId }}')"
                            x-on:change="toggleRow('{{ $rowId }}')"
                            class="rounded border-kore-input text-kore-primary focus:ring-kore-ring"
                        />
                    </td>
                @endif

                @foreach($visibleColumns as $column)
                    <td
                        class="{{ $column->getAlign() === 'center' ? 'text-center' : ($column->getAlign() === 'right' ? 'text-right' : 'text-left') }} text-kore-fg {{ $column->isWrap() ? '' : 'whitespace-nowrap' }}"
                        :class="densityClasses"
                    >
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
                    </td>
                @endforeach
            </tr>

            {{-- Expanded row with collapsed columns --}}
            @if(count($collapsedCols) > 0)
                <tr x-show="isExpanded('{{ $rowId }}')" x-cloak class="bg-kore-muted/20">
                    <td colspan="{{ count($visibleColumns) + ($selectionEnabled ? 2 : 1) }}">
                        <div class="px-6 py-3 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
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
                        </div>
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
