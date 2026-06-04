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

    // The swatch color goes straight into a `style` attribute, so only allow real
    // CSS color tokens (hex, rgb/hsl functional notations, named colors) and drop
    // anything else — otherwise a cell value could inject extra CSS declarations.
    $rawColor = trim((string) $value);
    $safeColor = preg_match(
        '/^(#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})|(?:rgb|rgba|hsl|hsla)\(\s*[0-9.,%\/\s]+\)|[a-zA-Z]+)$/',
        $rawColor
    ) ? $rawColor : null;
@endphp

<div
    class="inline-flex items-center gap-2"
    @if($props['copyable'] ?? false)
        x-data="{ copied: false }"
    @endif
>
    <span
        class="inline-block border border-kore-border {{ $swatchSizeClass }} {{ $swatchShapeClass }}"
        @if($safeColor) style="background-color: {{ $safeColor }};" @endif
    ></span>

    @if($props['showLabel'] ?? true)
        <span class="text-sm text-kore-muted-fg font-mono">{{ $value }}</span>
    @endif

    @if($props['copyable'] ?? false)
        <button
            type="button"
            x-on:click="navigator.clipboard.writeText(@js($value)); copied = true; setTimeout(() => copied = false, 2000)"
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
