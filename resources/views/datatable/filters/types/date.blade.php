@php $props = $filter->getComponentProps(); @endphp
<x-kore::datepicker
    size="sm"
    :id="$fieldId ?? null"
    :placeholder="$props['placeholder']"
    :min-date="$props['min-date']"
    :max-date="$props['max-date']"
    wire:model.live="filters.{{ $filter->getKey() }}"
/>
