@php $props = $filter->getComponentProps(); @endphp
{{-- `placeholder=` y no `:placeholder=`: ver la nota en toolbar.blade.php --}}
<x-kore::number
    size="sm"
    :id="$fieldId ?? null"
    :controls="false"
    placeholder="{{ $props['placeholder'] }}"
    :min="$props['min']"
    :max="$props['max']"
    :step="$props['step']"
    wire:model.live.debounce.500ms="filters.{{ $filter->getKey() }}"
/>
