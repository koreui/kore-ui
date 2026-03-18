@props([
    'label' => null,
    'icon' => null,
    'image' => null,
    'removable' => false,
    'color' => 'muted',
    'size' => 'md',
    'variant' => null,
])

@php
    $variant = $variant ?? config('kore-ui.ui.chip.variant', 'soft');

    $colorClasses = match($variant) {
        'solid' => match($color) {
            'primary' => 'bg-kore-primary text-kore-primary-fg',
            'secondary' => 'bg-kore-secondary text-kore-secondary-fg',
            'success' => 'bg-kore-success text-kore-success-fg',
            'warning' => 'bg-kore-warning text-kore-warning-fg',
            'destructive' => 'bg-kore-destructive text-kore-destructive-fg',
            'info' => 'bg-kore-info text-kore-info-fg',
            default => 'bg-kore-muted text-kore-fg',
        },
        'outline' => match($color) {
            'primary' => 'border border-kore-primary text-kore-primary',
            'secondary' => 'border border-kore-border text-kore-fg',
            'success' => 'border border-kore-success text-kore-success',
            'warning' => 'border border-kore-warning text-kore-warning',
            'destructive' => 'border border-kore-destructive text-kore-destructive',
            'info' => 'border border-kore-info text-kore-info',
            default => 'border border-kore-border text-kore-fg',
        },
        default => match($color) {
            'primary' => 'bg-kore-primary/10 text-kore-primary',
            'secondary' => 'bg-kore-secondary text-kore-secondary-fg',
            'success' => 'bg-kore-success/10 text-kore-success',
            'warning' => 'bg-kore-warning/10 text-kore-warning',
            'destructive' => 'bg-kore-destructive/10 text-kore-destructive',
            'info' => 'bg-kore-info/10 text-kore-info',
            default => 'bg-kore-muted text-kore-fg',
        },
    };

    $sizeClasses = match($size) {
        'sm' => 'text-xs px-2 py-0.5 gap-1',
        default => 'text-sm px-3 py-1 gap-1.5',
    };

    $iconSize = match($size) { 'sm' => 'size-3', default => 'size-3.5' };
    $imgSize = match($size) { 'sm' => 'size-4', default => 'size-5' };
@endphp

<span
    @if($removable) x-data="{ visible: true }" x-show="visible" @endif
    {{ $attributes->class(['inline-flex items-center rounded-full font-medium', $colorClasses, $sizeClasses]) }}
>
    @if($image)
        <img src="{{ $image }}" class="{{ $imgSize }} rounded-full object-cover -ml-1" alt="" />
    @elseif($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconSize }}" />
    @endif

    @if($label) {{ $label }} @endif
    {{ $slot }}

    @if($removable)
        <button type="button" x-on:click="visible = false; $dispatch('chip-removed')"
            class="ml-0.5 -mr-1 rounded-full p-0.5 hover:bg-black/10 dark:hover:bg-white/10 transition-colors">
            <x-lucide-x class="{{ $iconSize }}" />
        </button>
    @endif
</span>
