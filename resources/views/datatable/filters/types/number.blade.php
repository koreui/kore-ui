@php $props = $filter->getComponentProps(); @endphp
<x-kore::number
    size="sm"
    :placeholder="$props['placeholder']"
    :min="$props['min']"
    :max="$props['max']"
    :step="$props['step']"
    wire:model.live="filters.{{ $filter->getKey() }}"
/>
