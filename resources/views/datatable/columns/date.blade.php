@php
    $tooltipValue = $column->getTooltipValue($row);
@endphp

@if($tooltipValue)
    <x-kore::tooltip :text="$tooltipValue">
        <span>{{ $value }}</span>
    </x-kore::tooltip>
@else
    <span>{{ $value }}</span>
@endif
