@php
    $filterDefs = $filterDefs ?? [];
@endphp

@if(count($filterDefs) > 0)
    <div wire:ignore class="flex flex-wrap items-end gap-3">
        @foreach($filterDefs as $filter)
            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-kore-muted-fg mb-1">
                    {{ $filter->getLabel() }}
                </label>
                @include('kore::datatable.filters.types.' . $filter->getType(), ['filter' => $filter])
            </div>
        @endforeach
    </div>
@endif
