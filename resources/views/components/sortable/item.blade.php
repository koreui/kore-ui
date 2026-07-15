@props([
    'id' => null,
])

@aware([
    'mode' => null,
    'handle' => null,
])

@php
    $mode = $mode ?? config('kore-ui.ui.sortable.mode', 'server');
    $handle = $handle ?? config('kore-ui.ui.sortable.handle', false);

    $isServer = $mode === 'server';
    $p = $isServer ? 'wire:sort' : 'x-sort';

    // A stable id is required for server-side persistence (use the model id). The uniqid
    // fallback only keeps client-only reordering working when no id is given.
    $id = $id ?? 'sortable-' . uniqid();
@endphp

<div
    {{ $p }}:item="{{ $id }}"
    wire:key="kore-sortable-{{ $id }}"
    {{ $attributes->except(['id'])->class([
        'flex items-center gap-2 rounded-kore-md border border-kore-border bg-kore-surface px-3 py-2',
        'cursor-grab' => ! $handle,
    ]) }}
>
    @if($handle)
        <button
            type="button"
            {{ $p }}:handle
            class="shrink-0 cursor-grab text-kore-muted-fg hover:text-kore-fg transition-colors"
            aria-label="Arrastrar para reordenar"
        >
            <x-lucide-grip-vertical class="size-4" />
        </button>
    @endif

    {{ $slot }}
</div>
