{{-- Shared filter field list. Used by every filter layout (drawer, popover,
     slide-down, inline) so the label + type-view loop lives in one place.
     Layouts pass their own wrapper/label spacing via $itemClass / $labelClass. --}}
@php
    $filterDefs = $filterDefs ?? [];
    $itemClass = $itemClass ?? '';
    $labelClass = $labelClass ?? 'mb-1.5';
@endphp

@foreach($filterDefs as $filter)
    @php
        // Un id estable por filtro y por tabla: los cuatro layouts pueden
        // coexistir en la misma página y dos tablas pueden repetir clave.
        $fieldId = 'kore-filter-' . $this->getId() . '-' . $filter->getKey();
    @endphp
    <div @if($itemClass) class="{{ $itemClass }}" @endif>
        <label for="{{ $fieldId }}" class="block text-xs font-medium text-kore-muted-fg {{ $labelClass }}">
            {{ $filter->getLabel() }}
        </label>
        @include('kore::datatable.filters.types.' . $filter->getType(), [
            'filter'  => $filter,
            'fieldId' => $fieldId,
        ])
    </div>
@endforeach
