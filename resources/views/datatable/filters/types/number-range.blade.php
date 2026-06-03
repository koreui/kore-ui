@php $props = $filter->getComponentProps(); @endphp
<div class="flex items-center gap-2">
    <x-kore::number
        size="sm"
        placeholder="Min"
        :min="$props['min']"
        :max="$props['max']"
        :step="$props['step']"
        wire:model.live.debounce.500ms="filters.{{ $filter->getKey() }}.min"
    />
    <span class="text-kore-muted-fg text-sm">—</span>
    <x-kore::number
        size="sm"
        placeholder="Max"
        :min="$props['min']"
        :max="$props['max']"
        :step="$props['step']"
        wire:model.live.debounce.500ms="filters.{{ $filter->getKey() }}.max"
    />
</div>
