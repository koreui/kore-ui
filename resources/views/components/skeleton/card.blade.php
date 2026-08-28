{{-- La silueta de una <x-kore::card>.

     No es el velo de `loading`, que va por encima de un contenido ya pintado:
     esto es lo que se enseña cuando todavía no hay datos, con el mismo marco que
     tendrá la tarjeta para que nada salte al llegar. --}}
@props([
    'lines' => 3,
    'image' => false,
    'imagePosition' => 'top',
    'header' => true,
    'footer' => false,
    'bordered' => null,
    'shadow' => null,
    'padding' => true,
])

@php
    $bordered = $bordered ?? config('kore-ui.ui.card.bordered', true);
    $shadow = $shadow ?? config('kore-ui.ui.card.shadow', true);
    $lines = max(1, (int) $lines);
    $relleno = $padding ? 'px-6 py-4' : 'px-4 py-3';
@endphp

<div
    {{ $attributes->class([
        'relative rounded-kore-lg bg-kore-surface overflow-hidden',
        'border border-kore-border' => $bordered,
        'shadow-sm' => $shadow,
        'flex' => $image && $imagePosition === 'left',
    ]) }}
    role="status"
    aria-busy="true"
>
    <span class="sr-only">{{ config('kore-ui.ui.translations.loading', 'Cargando') }}</span>

    @if($image && in_array($imagePosition, ['top', 'left']))
        {{-- El ancho y el alto van como props y no como clases: `<x-kore::skeleton>`
             los escribe en el `style`, y un `style` inline gana a cualquier `w-48`.
             A la izquierda copia lo que hace la tarjeta de verdad —`w-48` y alto
             completo— para que la franja llegue hasta abajo en vez de cortarse a
             media tarjeta. --}}
        <x-kore::skeleton
            :rounded="'rounded-none'"
            :width="$imagePosition === 'left' ? '12rem' : '100%'"
            :height="$imagePosition === 'left' ? '100%' : '10rem'"
            :class="$imagePosition === 'left' ? 'shrink-0 self-stretch' : ''"
        />
    @endif

    <div class="flex-1 min-w-0">
        @if($header)
            <div class="{{ $relleno }} border-b border-kore-border">
                <x-kore::skeleton shape="text" height="1.25rem" width="40%" />
            </div>
        @endif

        <div class="{{ $relleno }} space-y-3">
            @for($i = 0; $i < $lines; $i++)
                <x-kore::skeleton shape="text" height="0.875rem" :width="$i === $lines - 1 && $lines > 1 ? '60%' : '100%'" />
            @endfor
        </div>

        @if($footer)
            <div class="{{ $relleno }} border-t border-kore-border flex justify-end gap-2">
                <x-kore::skeleton width="5rem" height="2rem" />
            </div>
        @endif
    </div>

    @if($image && $imagePosition === 'bottom')
        <x-kore::skeleton :rounded="'rounded-none'" height="10rem" />
    @endif
</div>
