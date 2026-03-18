<div
    x-data="KoreDataTable({ density: '{{ $density }}' })"
    class="rounded-kore-lg border border-kore-border bg-kore-surface overflow-hidden"
>
    {{-- Toolbar: search + per page --}}
    @include('kore::datatable.toolbar', [
        'searchDebounce' => $searchDebounce,
        'perPageOptions' => $perPageOptions,
        'translations'   => $translations,
    ])

    {{-- Table --}}
    <div class="relative overflow-x-auto">
        {{-- Loading overlay --}}
        <div wire:loading.flex class="absolute inset-0 z-10 items-center justify-center bg-kore-surface/80 backdrop-blur-[1px]">
            <x-kore::loading size="md" />
        </div>

        <table class="min-w-full divide-y divide-kore-border">
            {{-- Header --}}
            <thead class="bg-kore-muted/50">
                <tr>
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
                    <tr class="hover:bg-kore-muted/40 transition-colors">
                        @foreach($columns as $column)
                            <td
                                class="{{ $column->getAlign() === 'center' ? 'text-center' : ($column->getAlign() === 'right' ? 'text-right' : 'text-left') }} text-kore-fg {{ $column->isWrap() ? '' : 'whitespace-nowrap' }}"
                                :class="densityClasses"
                            >
                                @if($column->isHtml())
                                    {!! $column->getValue($row) !!}
                                @else
                                    {{ $column->getValue($row) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}">
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
