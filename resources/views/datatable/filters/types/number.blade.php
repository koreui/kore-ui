@php $props = $filter->getComponentProps(); @endphp
<x-kore::number
    size="sm"
    :id="$fieldId ?? null"
    :placeholder="$props['placeholder']"
    :min="$props['min']"
    :max="$props['max']"
    :step="$props['step']"
    wire:model.live.debounce.500ms="filters.{{ $filter->getKey() }}"
/>
