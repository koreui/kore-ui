@props([
    'size' => null,
])

@php
    $size = $size ?? config('kore-ui.ui.kbd.size', 'md');

    $sizeClasses = match($size) {
        'sm' => 'text-[10px] px-1 py-0.5 min-w-[18px]',
        'lg' => 'text-sm px-2.5 py-1 min-w-[28px]',
        default => 'text-xs px-1.5 py-0.5 min-w-[22px]',
    };
@endphp

<kbd {{ $attributes->class([
    'inline-flex items-center justify-center font-mono font-medium',
    'rounded-kore-sm border border-kore-border bg-kore-muted text-kore-fg',
    'shadow-[0_1px_0_1px_var(--kore-border)]',
    $sizeClasses,
]) }}>
    {{ $slot }}
</kbd>
