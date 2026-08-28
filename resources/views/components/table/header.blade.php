@props([
    'label' => '',
    'align' => 'left',
    'sortable' => false,
    'sortDirection' => null,
    'densityClasses' => 'px-4 py-2 text-xs',
])

@php
    $alignClass = match($align) {
        'center' => 'text-center',
        'right'  => 'text-right',
        default  => 'text-left',
    };

    $sortIcon = match($sortDirection) {
        'asc'   => 'arrow-up',
        'desc'  => 'arrow-down',
        default => 'arrow-up-down',
    };

    $sortIconClass = $sortDirection
        ? 'size-3.5 text-kore-fg'
        : 'size-3.5 text-kore-muted-fg/50 group-hover:text-kore-muted-fg transition-colors';
@endphp

<th scope="col" @if($sortable) aria-sort="{{ $sortDirection === 'asc' ? 'ascending' : ($sortDirection === 'desc' ? 'descending' : 'none') }}" @endif {{ $attributes->class([
    $densityClasses,
    $alignClass,
    'font-semibold text-kore-muted-fg uppercase tracking-wider whitespace-nowrap',
]) }}>
    @if($sortable)
        <button type="button" aria-label="{{ str_replace(':columna', $label, config('kore-ui.datatable.translations.sort_by', 'Ordenar por :columna')) }}" class="inline-flex items-center gap-1 group hover:text-kore-fg transition-colors">
            <span>{{ $label }}</span>
            <x-dynamic-component :component="'lucide-' . $sortIcon" :class="$sortIconClass" />
        </button>
    @else
        {{ $label }}
    @endif
</th>
