@props([
    'label' => '',
    'value' => null,
    'icon' => null,
    'span' => 1,
])

@aware([
    'layout' => null,
    'bordered' => null,
    'size' => null,
])

@php
    $layout = $layout ?? config('kore-ui.ui.descriptions.layout', 'horizontal');
    $bordered = $bordered ?? config('kore-ui.ui.descriptions.bordered', false);
    $size = $size ?? config('kore-ui.ui.descriptions.size', 'md');

    $isHorizontal = $layout === 'horizontal';

    $spanClasses = match((int) $span) {
        2 => 'sm:col-span-2',
        3 => 'sm:col-span-3',
        default => '',
    };

    $cellPadding = match($size) {
        'sm' => 'px-3 py-2',
        'lg' => 'px-5 py-4',
        default => 'px-4 py-3',
    };

    $labelSize = match($size) {
        'lg' => 'text-sm',
        default => 'text-xs',
    };

    $valueSize = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-base',
        default => 'text-sm',
    };

    $iconSize = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };
@endphp

<div {{ $attributes->except(['label', 'value', 'icon', 'span'])->class([
    $spanClasses,
    'bg-kore-surface ' . $cellPadding => $bordered,
    'flex items-start gap-4' => $isHorizontal,
]) }}>
    <dt @class([
        'flex items-center gap-1.5 font-medium text-kore-muted-fg',
        $labelSize,
        'shrink-0 ' . ($bordered ? 'w-1/3' : 'min-w-32') => $isHorizontal,
        'mb-1' => !$isHorizontal,
    ])>
        @if($icon)
            <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconSize }}" />
        @endif

        {{ $label }}
    </dt>

    <dd @class([$valueSize, 'text-kore-fg', 'flex-1' => $isHorizontal])>
        {{ $value ?? $slot }}
    </dd>
</div>
