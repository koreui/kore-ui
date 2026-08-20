@props([
    'label' => null,
    'icon' => null,
    'iconRight' => null,
    'href' => null,
    'target' => null,
    'size' => null,
    'variant' => null,
    'color' => null,
    'loading' => false,
    'disabled' => false,
    'block' => false,
    'type' => 'button',
])

@php
    $size = $size ?? config('kore-ui.ui.size', 'md');
    $variant = $variant ?? config('kore-ui.ui.button.variant', 'solid');
    $color = $color ?? config('kore-ui.ui.button.color', 'primary');
    $tag = $href ? 'a' : 'button';
    $isDisabled = $disabled || $loading;

    // Las variantes que pintan el color COMO TEXTO —soft, outline y ghost— usan
    // el token `-text`, no el color base. El base está pensado para ser un
    // FONDO: sobre su propio tinte al diez por ciento se queda muy por debajo de
    // AA. Medido en un navegador, antes: success 3,01 · info 3,24 ·
    // destructive 3,91 · primary 4,08. Ver la nota de `--kore-warning-text` en
    // kore-theme.css, que ya lo resolvía para un color de cinco.
    $colorClasses = match($variant) {
        'solid' => match($color) {
            'primary' => 'bg-kore-primary text-kore-primary-fg hover:bg-kore-primary/90',
            'secondary' => 'bg-kore-secondary text-kore-secondary-fg hover:bg-kore-secondary/80',
            'destructive' => 'bg-kore-destructive text-kore-destructive-fg hover:bg-kore-destructive/90',
            'success' => 'bg-kore-success text-kore-success-fg hover:bg-kore-success/90',
            'warning' => 'bg-kore-warning text-kore-warning-fg hover:bg-kore-warning/90',
            'info' => 'bg-kore-info text-kore-info-fg hover:bg-kore-info/90',
            default => 'bg-kore-primary text-kore-primary-fg hover:bg-kore-primary/90',
        },
        'outline' => match($color) {
            'primary' => 'border border-kore-primary text-kore-primary-text hover:bg-kore-primary/10',
            'secondary' => 'border border-kore-border text-kore-fg hover:bg-kore-muted',
            'destructive' => 'border border-kore-destructive text-kore-destructive-text hover:bg-kore-destructive/10',
            'success' => 'border border-kore-success text-kore-success-text hover:bg-kore-success/10',
            'warning' => 'border border-kore-warning text-kore-warning-text hover:bg-kore-warning/10',
            'info' => 'border border-kore-info text-kore-info-text hover:bg-kore-info/10',
            default => 'border border-kore-primary text-kore-primary-text hover:bg-kore-primary/10',
        },
        'ghost' => match($color) {
            'primary' => 'text-kore-primary-text hover:bg-kore-primary/10',
            'secondary' => 'text-kore-fg hover:bg-kore-muted',
            'destructive' => 'text-kore-destructive-text hover:bg-kore-destructive/10',
            'success' => 'text-kore-success-text hover:bg-kore-success/10',
            'warning' => 'text-kore-warning-text hover:bg-kore-warning/10',
            'info' => 'text-kore-info-text hover:bg-kore-info/10',
            default => 'text-kore-primary-text hover:bg-kore-primary/10',
        },
        'soft' => match($color) {
            'primary' => 'bg-kore-primary/10 text-kore-primary-text hover:bg-kore-primary/20',
            'secondary' => 'bg-kore-secondary text-kore-secondary-fg hover:bg-kore-secondary/80',
            'destructive' => 'bg-kore-destructive/10 text-kore-destructive-text hover:bg-kore-destructive/20',
            'success' => 'bg-kore-success/10 text-kore-success-text hover:bg-kore-success/20',
            'warning' => 'bg-kore-warning/10 text-kore-warning-text hover:bg-kore-warning/20',
            'info' => 'bg-kore-info/10 text-kore-info-text hover:bg-kore-info/20',
            default => 'bg-kore-primary/10 text-kore-primary-text hover:bg-kore-primary/20',
        },
        'link' => match($color) {
            'primary' => 'text-kore-primary-text underline-offset-4 hover:underline',
            'secondary' => 'text-kore-fg underline-offset-4 hover:underline',
            'destructive' => 'text-kore-destructive-text underline-offset-4 hover:underline',
            default => 'text-kore-primary-text underline-offset-4 hover:underline',
        },
        default => 'bg-kore-primary text-kore-primary-fg hover:bg-kore-primary/90',
    };

    $sizeClasses = match($size) {
        'sm' => 'text-xs px-3 py-1.5 gap-1.5',
        'lg' => 'text-base px-5 py-2.5 gap-2.5',
        default => 'text-sm px-4 py-2 gap-2',
    };

    $iconSize = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };

    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-kore-md transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring focus-visible:ring-offset-2 ring-offset-kore-bg disabled:opacity-50 disabled:pointer-events-none';
@endphp

<{{ $tag }}
    {{ $attributes
        ->except(['label', 'icon', 'iconRight', 'href', 'target', 'size', 'variant', 'color', 'loading', 'disabled', 'block', 'type'])
        ->class([
            $baseClasses,
            $colorClasses,
            $sizeClasses,
            'w-full' => $block,
            'opacity-50 pointer-events-none' => $isDisabled,
        ])
    }}
    @if($tag === 'a')
        href="{{ $href }}"
        @if($target) target="{{ $target }}" @endif
        @if($isDisabled) aria-disabled="true" tabindex="-1" @endif
    @else
        type="{{ $type }}"
        @if($isDisabled) disabled @endif
    @endif
    @if($loading) aria-busy="true" @endif
>
    @if($loading)
        <svg class="animate-spin kore-anim-spinner {{ $iconSize }}" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @elseif($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconSize }}" />
    @endif

    @if($label)
        <span>{{ $label }}</span>
    @endif

    {{ $slot }}

    @if($iconRight && !$loading)
        <x-dynamic-component :component="'lucide-' . $iconRight" class="{{ $iconSize }}" />
    @endif
</{{ $tag }}>
