@php $props = $filter->getComponentProps(); @endphp
<x-kore::select
    size="sm"
    :id="$fieldId ?? null"
    :placeholder="$props['placeholder']"
    :options="$props['options']"
    :option-label="$props['option-label']"
    :option-value="$props['option-value']"
    :searchable="$props['searchable'] ?? false"
    wire:model.live="filters.{{ $filter->getKey() }}"
/>
