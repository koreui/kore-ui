@props([
    'value' => false,
    'trueIcon' => null,
    'falseIcon' => null,
    'trueColor' => null,
    'falseColor' => null,
    'size' => 'md',
])

@php
    $trueIcon = $trueIcon ?? config('kore-ui.ui.boolean.true_icon', 'check');
    $falseIcon = $falseIcon ?? config('kore-ui.ui.boolean.false_icon', 'x');
    $trueColor = $trueColor ?? config('kore-ui.ui.boolean.true_color', 'success');
    $falseColor = $falseColor ?? config('kore-ui.ui.boolean.false_color', 'destructive');

    $icon = $value ? $trueIcon : $falseIcon;
    $activeColor = $value ? $trueColor : $falseColor;

    $colorClass = match($activeColor) {
        'primary' => 'text-kore-primary',
        'success' => 'text-kore-success',
        'destructive' => 'text-kore-destructive',
        'warning' => 'text-kore-warning',
        'info' => 'text-kore-info',
        'muted' => 'text-kore-muted-fg',
        default => 'text-kore-success',
    };

    $sizeClass = match($size) {
        'sm' => 'size-4',
        'lg' => 'size-6',
        default => 'size-5',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center justify-center', $colorClass]) }}
    role="img" aria-label="{{ $value ? 'true' : 'false' }}">
    <x-dynamic-component :component="'lucide-' . $icon" class="{{ $sizeClass }}" />
</span>
