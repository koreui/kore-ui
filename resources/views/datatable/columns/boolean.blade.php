@php
    $props = $column->getComponentProps();
@endphp

<x-kore::boolean
    :value="(bool) $value"
    :true-icon="$props['trueIcon'] ?? null"
    :false-icon="$props['falseIcon'] ?? null"
    :true-color="$props['trueColor'] ?? null"
    :false-color="$props['falseColor'] ?? null"
    :size="$props['size'] ?? 'md'"
/>
