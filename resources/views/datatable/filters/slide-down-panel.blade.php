{{-- Slide-down panel — rendered below the toolbar row --}}
@php
    $filterDefs = $filterDefs ?? [];
    $filtersExpanded = $filtersExpanded ?? false;
@endphp

@if(count($filterDefs) > 0)
    <div
        x-show="slideDownOpen"
        class="kore-slide-down"
        @if(!$filtersExpanded) x-cloak @endif
    >
        <div wire:ignore class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 px-4 py-3 border-t border-kore-border bg-kore-muted/20 max-h-[60vh] overflow-y-auto">
            @include('kore::datatable.filters.fields', ['filterDefs' => $filterDefs, 'labelClass' => 'mb-1'])
        </div>
    </div>
@endif
