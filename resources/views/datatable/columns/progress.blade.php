@php
    $props = $column->getComponentProps();
    $rawValue = data_get($row, $column->getField(), 0);
@endphp

@if($props['circle'] ?? false)
    <x-kore::progress.circle
        :value="$rawValue"
        :size="$props['size'] ?? 'sm'"
        :color="$props['color'] ?? 'auto'"
        :show-value="$props['showValue'] ?? true"
    />
@else
    <x-kore::progress
        :value="$rawValue"
        :max="$props['max'] ?? 100"
        :size="$props['size'] ?? 'sm'"
        :color="$props['color'] ?? 'auto'"
        :show-value="$props['showValue'] ?? true"
        :striped="$props['striped'] ?? false"
    />
@endif
