@props([
    'name',
    'size' => null,
    'color' => null,
    'animation' => null,
    'error' => false,
    'label' => null,
    'labelPosition' => 'right',
    'strokeWidth' => null,
    'raw' => false,
])

@php
    $size = $size ?? config('kore-ui.ui.icon.size') ?? config('kore-ui.ui.size', 'md');
    $prefix = config('kore-ui.ui.icon.prefix', 'lucide');

    $sizeClass = match($size) {
        'xs' => 'size-3',
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        'xl' => 'size-6',
        '2xl' => 'size-8',
        default => 'size-4',
    };

    $colorClass = $error ? 'text-kore-destructive' : match($color) {
        'primary' => 'text-kore-primary',
        'secondary' => 'text-kore-secondary-fg',
        'success' => 'text-kore-success',
        'warning' => 'text-kore-warning',
        'destructive' => 'text-kore-destructive',
        'info' => 'text-kore-info',
        'muted' => 'text-kore-muted-fg',
        default => '',
    };

    $animationClass = match($animation) {
        'spin' => 'animate-spin',
        'pulse' => 'animate-pulse',
        'bounce' => 'animate-bounce',
        'ping' => 'animate-ping',
        default => '',
    };

    $component = $raw ? $name : ($prefix . '-' . $name);

    $computedClasses = collect([$sizeClass, $colorClass, $animationClass])->filter()->implode(' ');

    $extraAttrs = $attributes
        ->except(['name', 'size', 'color', 'animation', 'error', 'label', 'labelPosition', 'strokeWidth', 'raw'])
        ->getAttributes();

    if (!isset($extraAttrs['aria-label']) && !isset($extraAttrs['aria-hidden'])) {
        $extraAttrs['aria-hidden'] = 'true';
    }
    if ($strokeWidth) {
        $extraAttrs['stroke-width'] = $strokeWidth;
    }

    $userClass = $extraAttrs['class'] ?? '';
    unset($extraAttrs['class']);
    $finalClass = trim($computedClasses . ' ' . $userClass);

    $svgHtml = svg($component, $finalClass, $extraAttrs)->toHtml();
@endphp

@if($label)
    <span class="inline-flex items-center gap-1.5">
        @if($labelPosition === 'left')
            <span>{{ $label }}</span>
        @endif

        {!! $svgHtml !!}

        @if($labelPosition !== 'left')
            <span>{{ $label }}</span>
        @endif
    </span>
@else
    {!! $svgHtml !!}
@endif
