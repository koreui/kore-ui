@props([
    'text' => null,
    'position' => null,
    'delay' => null,
])

@php
    $position = $position ?? config('kore-ui.ui.tooltip.position', 'top');
    $delay = $delay ?? config('kore-ui.ui.tooltip.delay', 200);

    // El panel necesita id porque el control que lo dispara tiene que apuntarle
    // con `aria-describedby`. Determinista, o la referencia quedaría colgando en
    // cada render. Ver IdContext.
    //
    // El id de la descripción. Ver el `<span class="sr-only">` de abajo.
    $descripcionId = \KoreUi\Core\Support\IdContext::secuencia('kore-tooltip');
@endphp

<div
    {{ $attributes
        ->except(['text', 'position', 'delay'])
        ->class('inline-flex')
    }}
    x-data="KoreTooltip({ placement: '{{ $position }}', delay: {{ $delay }}, descripcionId: '{{ $descripcionId }}' })"
    x-on:mouseenter="open()"
    x-on:mouseleave="close()"
    x-on:focus.capture="open()"
    x-on:blur.capture="close()"
    {{-- WCAG 1.4.13: lo que aparece al pasar por encima o al enfocar tiene que
         poder descartarse sin mover el foco. Se escucha en el elemento y no en
         `window`, y solo se marca la tecla si había algo abierto: es el contrato
         del Escape que sigue el resto de la librería. --}}
    x-on:keydown.escape="if (show) { $event.preventDefault(); close() }"
>
    <div x-ref="trigger" class="inline-flex">
        {{ $slot }}
    </div>

    {{-- El texto para quien no ve el panel, AQUÍ y no en el panel teleportado.

         El panel vive en `<body>`, lejos de este componente, y todo intento de
         darle un id acababa mal: escrito en el HTML, el morph lo emparejaba con
         el nodo ya teleportado y lo arrancaba de su ámbito; asignado desde el
         JavaScript, pedir `$refs.tooltip` durante el montaje dejaba paneles sin
         ámbito. Los dos daban `ReferenceError: show is not defined` en una tabla
         con veinticinco tooltips.

         Este nodo, en cambio, es HTML normal del componente: el morph lo trata
         como cualquier otro y el `aria-describedby` del control apunta a algo
         que existe desde el primer render, se abra el tooltip o no. --}}
    <span id="{{ $descripcionId }}" class="sr-only">{{ $text }}</span>

    <template x-teleport="body">
        <div
            data-kore-teleport
            x-ref="tooltip"
            x-show="show"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            {{-- Decorativo: el texto ya lo lee el `sr-only` de arriba, y sin
                 esto un lector lo encontraría dos veces. --}}
            aria-hidden="true"
            class="z-[70] pointer-events-none max-w-xs"
        >
            <div class="bg-kore-fg text-kore-bg rounded-kore-md text-xs px-2.5 py-1.5 font-medium">
                {{ $text }}
            </div>
        </div>
    </template>
</div>
