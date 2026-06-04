@php
    $filterDefs = $filterDefs ?? [];
@endphp

@if(count($filterDefs) > 0)
    <div wire:ignore class="flex flex-wrap items-end gap-3">
        @include('kore::datatable.filters.fields', [
            'filterDefs' => $filterDefs,
            'itemClass'  => 'min-w-[160px]',
            'labelClass' => 'mb-1',
        ])
    </div>
@endif
