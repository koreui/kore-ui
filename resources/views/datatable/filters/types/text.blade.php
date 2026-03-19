@php $props = $filter->getComponentProps(); @endphp
<div class="relative">
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5">
        <x-lucide-search class="size-3.5 text-kore-muted-fg" />
    </div>
    <input
        type="text"
        placeholder="{{ $props['placeholder'] }}"
        wire:model.live.debounce.{{ $props['debounce'] }}ms="filters.{{ $filter->getKey() }}"
        class="bg-kore-bg text-kore-fg placeholder:text-kore-muted-fg transition-colors duration-150 block w-full rounded-kore-md border border-kore-input focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary text-xs py-1.5 px-2.5 pl-8"
    />
</div>
