@php
    $props = $column->getComponentProps();

    $swatchSizeClass = match($props['swatchSize'] ?? 'md') {
        'sm' => 'size-4',
        'lg' => 'size-8',
        default => 'size-6',
    };

    $swatchShapeClass = match($props['swatchShape'] ?? 'rounded') {
        'circle' => 'rounded-full',
        'square' => 'rounded-none',
        default => 'rounded-kore-sm',
    };
@endphp

<div
    class="inline-flex items-center gap-2"
    @if($props['copyable'] ?? false)
        x-data="{ copied: false }"
    @endif
>
    <span
        class="inline-block border border-kore-border {{ $swatchSizeClass }} {{ $swatchShapeClass }}"
        style="background-color: {{ $value }};"
    ></span>

    @if($props['showLabel'] ?? true)
        <span class="text-sm text-kore-muted-fg font-mono">{{ $value }}</span>
    @endif

    @if($props['copyable'] ?? false)
        <button
            type="button"
            x-on:click="navigator.clipboard.writeText('{{ $value }}'); copied = true; setTimeout(() => copied = false, 2000)"
            class="p-0.5 rounded hover:bg-kore-muted transition-colors"
        >
            <template x-if="!copied">
                <x-lucide-copy class="size-3.5 text-kore-muted-fg" />
            </template>
            <template x-if="copied">
                <x-lucide-check class="size-3.5 text-kore-success" />
            </template>
        </button>
    @endif
</div>
