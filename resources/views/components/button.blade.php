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
    // `false` apaga las transiciones de este componente y de su panel. Ver
    // KoreUi\Core\Support\Motion.
    'animated' => null,
])

@php $attributes = \KoreUi\Core\Support\Motion::marcar($attributes, $animated); @endphp

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
    // El sólido lleva `shadow-kore-solid`: un brillo de un píxel en el canto
    // superior y una sombra corta debajo. Es lo que lo separa de un rectángulo
    // de color plano, y lo que hace que «primario» se lea como EL botón.
    //
    // TODAS las variantes llevan borde de un píxel, visible o transparente. Sin
    // él, un botón sólido medía dos píxeles menos que un campo o que un botón
    // con contorno, y en una fila de filtros —campo, select, botón— el botón
    // quedaba más bajo que todo lo demás.
    $colorClasses = match($variant) {
        'solid' => match($color) {
            'secondary' => 'border-kore-border bg-kore-secondary text-kore-secondary-fg hover:bg-kore-accent',
            'destructive' => 'border-transparent bg-kore-destructive text-kore-destructive-fg shadow-kore-solid hover:bg-kore-destructive/90',
            'success' => 'border-transparent bg-kore-success text-kore-success-fg shadow-kore-solid hover:bg-kore-success/90',
            'warning' => 'border-transparent bg-kore-warning text-kore-warning-fg shadow-kore-solid hover:bg-kore-warning/90',
            'info' => 'border-transparent bg-kore-info text-kore-info-fg shadow-kore-solid hover:bg-kore-info/90',
            default => 'border-transparent bg-kore-primary text-kore-primary-fg shadow-kore-solid hover:bg-kore-primary/90',
        },
        'outline' => match($color) {
            // El contorno neutro es el botón «por defecto» de un sistema: fondo de
            // campo, borde de campo y la misma sombra corta que un input.
            'secondary' => 'border-kore-input bg-kore-field text-kore-fg shadow-kore-xs hover:bg-kore-muted hover:border-kore-input-hover',
            'destructive' => 'border-kore-destructive/45 text-kore-destructive-text hover:bg-kore-destructive/10 hover:border-kore-destructive/70',
            'success' => 'border-kore-success/45 text-kore-success-text hover:bg-kore-success/10 hover:border-kore-success/70',
            'warning' => 'border-kore-warning/55 text-kore-warning-text hover:bg-kore-warning/10 hover:border-kore-warning/80',
            'info' => 'border-kore-info/45 text-kore-info-text hover:bg-kore-info/10 hover:border-kore-info/70',
            default => 'border-kore-primary/45 text-kore-primary-text hover:bg-kore-primary/10 hover:border-kore-primary/70',
        },
        'ghost' => match($color) {
            'secondary' => 'border-transparent text-kore-fg hover:bg-kore-muted',
            'destructive' => 'border-transparent text-kore-destructive-text hover:bg-kore-destructive/10',
            'success' => 'border-transparent text-kore-success-text hover:bg-kore-success/10',
            'warning' => 'border-transparent text-kore-warning-text hover:bg-kore-warning/10',
            'info' => 'border-transparent text-kore-info-text hover:bg-kore-info/10',
            default => 'border-transparent text-kore-primary-text hover:bg-kore-primary/10',
        },
        'soft' => match($color) {
            'secondary' => 'border-transparent bg-kore-secondary text-kore-secondary-fg hover:bg-kore-accent',
            'destructive' => 'border-transparent bg-kore-destructive/10 text-kore-destructive-text hover:bg-kore-destructive/20',
            'success' => 'border-transparent bg-kore-success/10 text-kore-success-text hover:bg-kore-success/20',
            'warning' => 'border-transparent bg-kore-warning/10 text-kore-warning-text hover:bg-kore-warning/20',
            'info' => 'border-transparent bg-kore-info/10 text-kore-info-text hover:bg-kore-info/20',
            default => 'border-transparent bg-kore-primary/10 text-kore-primary-text hover:bg-kore-primary/20',
        },
        'link' => match($color) {
            'secondary' => 'border-transparent text-kore-fg underline-offset-4 hover:underline',
            'destructive' => 'border-transparent text-kore-destructive-text underline-offset-4 hover:underline',
            default => 'border-transparent text-kore-primary-text underline-offset-4 hover:underline',
        },
        default => 'border-transparent bg-kore-primary text-kore-primary-fg shadow-kore-solid hover:bg-kore-primary/90',
    };

    // Un botón que solo lleva icono es cuadrado: con el relleno horizontal de
    // uno con texto salía un rectángulo apaisado alrededor de un icono.
    $soloIcono = ! $label && $slot->isEmpty() && ($icon || $loading) && ! $iconRight;

    $sizeClasses = match($size) {
        'sm' => 'text-xs py-1.5 gap-1 ' . ($soloIcono ? 'px-1.5' : 'px-3'),
        'lg' => 'text-base py-2.5 gap-2 ' . ($soloIcono ? 'px-2.5' : 'px-5'),
        default => 'text-sm py-2 gap-1.5 ' . ($soloIcono ? 'px-2' : 'px-4'),
    };

    // El icono ocupa la altura de una línea de texto: así un botón de solo
    // icono mide exactamente lo mismo que uno con etiqueta.
    $iconSize = match($size) {
        'sm' => 'size-4 p-px',
        'lg' => 'size-6 p-0.5',
        default => 'size-5 p-0.5',
    };

    $baseClasses = 'inline-flex items-center justify-center border font-medium rounded-kore-md select-none whitespace-nowrap kore-transition kore-focus kore-press disabled:opacity-50 disabled:pointer-events-none';
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
