{{-- Slide-down panel — rendered below the toolbar row --}}
@php
    $filterDefs = $filterDefs ?? [];
    $filtersExpanded = $filtersExpanded ?? false;
@endphp

@if(count($filterDefs) > 0)
    <div
        x-show="slideDownOpen"
        x-transition:enter="transition-all ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition-all ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        @if(!$filtersExpanded) x-cloak @endif
    >
        <div wire:ignore class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 px-4 py-3 border-t border-kore-border bg-kore-muted/20 max-h-[60vh] overflow-y-auto">
            @foreach($filterDefs as $filter)
                <div>
                    <label class="block text-xs font-medium text-kore-muted-fg mb-1">
                        {{ $filter->getLabel() }}
                    </label>
                    @include('kore::datatable.filters.types.' . $filter->getType(), ['filter' => $filter])
                </div>
            @endforeach
        </div>
    </div>
@endif
