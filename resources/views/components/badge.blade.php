@props([
    'label' => null,
    'icon' => null,
    'size' => 'md',
    'variant' => null,
    'color' => 'primary',
    'dot' => false,
])

@php
    $variant = $variant ?? config('kore-ui.ui.badge.variant', 'soft');

    // Las variantes que pintan el color COMO TEXTO —soft, outline y ghost— usan
    // el token `-text`, no el color base. El base está pensado para ser un
    // FONDO: sobre su propio tinte al diez por ciento se queda muy por debajo de
    // AA. Medido en un navegador, antes: success 3,01 · info 3,24 ·
    // destructive 3,91 · primary 4,08. Ver la nota de `--kore-warning-text` en
    // kore-theme.css, que ya lo resolvía para un color de cinco.
    $colorClasses = match($variant) {
        'solid' => match($color) {
            'primary' => 'bg-kore-primary text-kore-primary-fg',
            'secondary' => 'bg-kore-secondary text-kore-secondary-fg',
            'success' => 'bg-kore-success text-kore-success-fg',
            'warning' => 'bg-kore-warning text-kore-warning-fg',
            'destructive' => 'bg-kore-destructive text-kore-destructive-fg',
            'info' => 'bg-kore-info text-kore-info-fg',
            'muted' => 'bg-kore-muted text-kore-muted-fg',
            default => 'bg-kore-primary text-kore-primary-fg',
        },
        'outline' => match($color) {
            'primary' => 'border border-kore-primary text-kore-primary-text',
            'secondary' => 'border border-kore-border text-kore-fg',
            'success' => 'border border-kore-success text-kore-success-text',
            'warning' => 'border border-kore-warning text-kore-warning-text',
            'destructive' => 'border border-kore-destructive text-kore-destructive-text',
            'info' => 'border border-kore-info text-kore-info-text',
            'muted' => 'border border-kore-border text-kore-muted-fg',
            default => 'border border-kore-primary text-kore-primary-text',
        },
        default => match($color) {
            'primary' => 'bg-kore-primary/10 text-kore-primary-text',
            'secondary' => 'bg-kore-secondary text-kore-secondary-fg',
            'success' => 'bg-kore-success/10 text-kore-success-text',
            'warning' => 'bg-kore-warning/10 text-kore-warning-text',
            'destructive' => 'bg-kore-destructive/10 text-kore-destructive-text',
            'info' => 'bg-kore-info/10 text-kore-info-text',
            'muted' => 'bg-kore-muted text-kore-muted-fg',
            default => 'bg-kore-primary/10 text-kore-primary-text',
        },
    };

    $sizeClasses = match($size) {
        'sm' => 'text-[10px] px-1.5 py-0.5',
        'lg' => 'text-sm px-3 py-1',
        default => 'text-xs px-2 py-0.5',
    };

    $iconSize = match($size) {
        'sm' => 'size-3',
        'lg' => 'size-4',
        default => 'size-3.5',
    };

    $dotColor = match($color) {
        'primary' => 'bg-kore-primary',
        'secondary' => 'bg-kore-secondary-fg',
        'success' => 'bg-kore-success',
        'warning' => 'bg-kore-warning',
        'destructive' => 'bg-kore-destructive',
        'info' => 'bg-kore-info',
        'muted' => 'bg-kore-muted-fg',
        default => 'bg-kore-primary',
    };
@endphp

@if($dot)
    <span {{ $attributes->class(['inline-block size-2 rounded-full', $dotColor]) }}></span>
@else
    <span {{ $attributes
        ->except(['label', 'icon', 'size', 'variant', 'color', 'dot'])
        ->class([
            'inline-flex items-center gap-1 font-medium rounded-full',
            $colorClasses,
            $sizeClasses,
        ])
    }}>
        @if($icon)
            <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconSize }}" />
        @endif

        @if($label)
            {{ $label }}
        @endif

        {{ $slot }}
    </span>
@endif
