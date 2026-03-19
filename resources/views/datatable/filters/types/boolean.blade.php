@php $props = $filter->getComponentProps(); @endphp
<x-kore::select
    size="sm"
    :placeholder="$props['placeholder']"
    :options="$props['options']"
    :option-label="$props['option-label']"
    :option-value="$props['option-value']"
    wire:model.live="filters.{{ $filter->getKey() }}"
/>
