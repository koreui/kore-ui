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
            default => 'bg-kore-muted text-kore-fg',
        },
        'outline' => match($color) {
            'primary' => 'border border-kore-primary text-kore-primary-text',
            'secondary' => 'border border-kore-border text-kore-fg',
            'success' => 'border border-kore-success text-kore-success-text',
            'warning' => 'border border-kore-warning text-kore-warning-text',
            'destructive' => 'border border-kore-destructive text-kore-destructive-text',
            'info' => 'border border-kore-info text-kore-info-text',
            default => 'border border-kore-border text-kore-fg',
        },
        default => match($color) {
            'primary' => 'bg-kore-primary/10 text-kore-primary-text',
            'secondary' => 'bg-kore-secondary text-kore-secondary-fg',
            'success' => 'bg-kore-success/10 text-kore-success-text',
            'warning' => 'bg-kore-warning/10 text-kore-warning-text',
            'destructive' => 'bg-kore-destructive/10 text-kore-destructive-text',
            'info' => 'bg-kore-info/10 text-kore-info-text',
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
        {{-- `size-6` y no `p-0.5`: medido, la caja salía de 18×18 y WCAG 2.2
             pide 24×24 como mínimo. El margen negativo compensa el ancho extra
             para que el chip no crezca. --}}
        <button type="button" x-on:click="visible = false; $dispatch('chip-removed')"
            aria-label="{{ config('kore-ui.ui.translations.remove', 'Quitar') }}"
            class="-mr-2 inline-flex size-6 shrink-0 items-center justify-center rounded-full hover:bg-black/10 dark:hover:bg-white/10 transition-colors">
            <x-lucide-x class="{{ $iconSize }}" />
        </button>
    @endif
</span>
