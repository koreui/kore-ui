@php
    $primaryKey = $primaryKey ?? 'id';
    $firstColumn = $columns[0] ?? null;
    $actionColumn = collect($columns)->first(fn ($col) => $col->getType() === 'action');
    $dataColumns = collect($columns)->reject(fn ($col) => $col === $firstColumn || $col->getType() === 'action')->values();
@endphp

<div class="divide-y divide-kore-border">
    @forelse($rows as $row)
        {{-- wire:key: sin él Livewire reutiliza las tarjetas por posición y el
             estado Alpine de una celda en edición se queda pegado al hueco, no
             al registro, al paginar o filtrar. --}}
        <div wire:key="card-{{ data_get($row, $primaryKey) }}" class="p-4 space-y-3">
            {{-- Card header: first column as title + action menu --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if($selectionEnabled)
                        <input
                            type="checkbox"
                            aria-label="Seleccionar fila"
                            value="{{ data_get($row, $primaryKey) }}"
                            @checked($this->isRowSelected(data_get($row, $primaryKey)))
                            data-checked="{{ $this->isRowSelected(data_get($row, $primaryKey)) ? '1' : '0' }}"
                            x-on:click="onRowCheckboxClick(@js(data_get($row, $primaryKey)), $event)"
                            class="rounded border-kore-input text-kore-primary focus:ring-kore-ring"
                        />
                    @endif

                    @if($firstColumn)
                        <div class="font-medium text-kore-fg">
                            @include('kore::datatable.cell-description', ['column' => $firstColumn, 'row' => $row, 'slot' => 'above'])
                            @if($firstColumn->getType() !== 'text')
                                @include('kore::datatable.columns.' . $firstColumn->getType(), [
                                    'column' => $firstColumn,
                                    'row' => $row,
                                    'value' => $firstColumn->getValue($row),
                                    'primaryKey' => $primaryKey,
                                ])
                            @else
                                {{ $firstColumn->getValue($row) }}
                            @endif
                            @include('kore::datatable.cell-description', ['column' => $firstColumn, 'row' => $row, 'slot' => 'below'])
                        </div>
                    @endif
                </div>

                @if($actionColumn)
                    @include('kore::datatable.columns.action', [
                        'column' => $actionColumn,
                        'row' => $row,
                        'value' => null,
                        'primaryKey' => $primaryKey,
                    ])
                @endif
            </div>

            {{-- Card body: label:value pairs --}}
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                @foreach($dataColumns as $col)
                    <div>
                        <dt class="text-kore-muted-fg text-xs">{{ $col->getLabel() }}</dt>
                        <dd class="text-kore-fg mt-0.5">
                            @include('kore::datatable.cell-description', ['column' => $col, 'row' => $row, 'slot' => 'above'])
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
                            @include('kore::datatable.cell-description', ['column' => $col, 'row' => $row, 'slot' => 'below'])
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @empty
        <div class="p-8">
            <x-kore::empty-state
                :title="$emptyText ?? 'No se encontraron resultados'"
                :icon="$emptyIcon ?? 'inbox'"
            />
        </div>
    @endforelse
</div>
