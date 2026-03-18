@props([
    'shape' => 'rect',
    'width' => null,
    'height' => null,
    'size' => null,
    'lines' => 1,
    'animation' => null,
    'rounded' => null,
])

@php
    $animation = $animation ?? config('kore-ui.ui.skeleton.animation', 'shimmer');

    // Backwards compat: boolean true/false mapped to string
    if ($animation === true) $animation = 'shimmer';
    if ($animation === false) $animation = 'none';

    $defaultWidth = match($shape) {
        'circle' => $size ?? '2.5rem',
        'text' => '100%',
        default => '100%',
    };
    $defaultHeight = match($shape) {
        'circle' => $size ?? '2.5rem',
        'text' => '0.75rem',
        default => '1rem',
    };
    $resolvedWidth = $width ?? $defaultWidth;
    $resolvedHeight = $height ?? $defaultHeight;

    $shapeClasses = match(true) {
        $rounded !== null => $rounded,
        $shape === 'circle' => 'rounded-full',
        $shape === 'text' => 'rounded-kore-sm',
        default => 'rounded-kore-md',
    };

    $animationClass = match($animation) {
        'pulse' => 'animate-pulse',
        'shimmer' => 'kore-skeleton-shimmer',
        default => '',
    };

    $baseClasses = [
        'bg-kore-muted',
        $shapeClasses,
        $animationClass,
        $animation === 'shimmer' ? 'overflow-hidden relative' : '',
    ];
@endphp

@if($shape === 'text' && $lines > 1)
    <div {{ $attributes->class(['space-y-2']) }} aria-hidden="true">
        @for($i = 0; $i < $lines; $i++)
            <div class="{{ implode(' ', array_filter($baseClasses)) }}"
                 style="width: {{ $i === $lines - 1 ? '75%' : $resolvedWidth }}; height: {{ $resolvedHeight }}"></div>
        @endfor
    </div>
@else
    <div {{ $attributes->class(array_filter($baseClasses)) }}
         style="width: {{ $resolvedWidth }}; height: {{ $resolvedHeight }}"
         aria-hidden="true"></div>
@endif
