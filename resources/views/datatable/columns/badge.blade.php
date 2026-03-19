@php
    $props = $column->getComponentProps();
    $resolvedColor = $column->resolveColor($value);
    $resolvedIcon = $column->resolveIcon($value);
@endphp

<x-kore::badge
    :label="$value"
    :color="$resolvedColor"
    :variant="$props['variant'] ?? null"
    :size="$props['size'] ?? 'md'"
    :icon="$resolvedIcon"
/>
