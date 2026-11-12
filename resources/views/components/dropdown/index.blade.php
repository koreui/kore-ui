@props([
    'position' => null,
    'width' => null,
    'maxHeight' => null,
    'persistent' => false,
    'ariaLabel' => null,
    // `false` apaga las transiciones de este componente y de su panel. Ver
    // KoreUi\Core\Support\Motion.
    'animated' => null,
])

@php $attributes = \KoreUi\Core\Support\Motion::marcar($attributes, $animated); @endphp

@php
    $position = $position ?? config('kore-ui.ui.dropdown.position', 'bottom-start');
    $width = $width ?? config('kore-ui.ui.dropdown.width', 'auto');
    $maxHeightClass = $maxHeight ? '' : 'max-h-72';
    $ariaLabel = $ariaLabel ?? config('kore-ui.ui.translations.menu', 'Menú');
@endphp

<div
    {{ $attributes
        ->except(['position', 'width', 'maxHeight', 'persistent', 'ariaLabel'])
        ->class('relative inline-flex')
    }}
    x-data="KoreDropdown({
        position: '{{ $position }}',
        width: '{{ $width }}',
        persistent: {{ $persistent ? 'true' : 'false' }},
    })"
    x-on:keydown="onKeydown($event)"
>
    {{-- Trigger --}}
    <div x-ref="trigger" x-on:click="toggle()" class="inline-flex">
        {{ $trigger }}
    </div>

    {{-- Panel --}}
    {{-- Este panel SÍ existe desde la carga, al contrario que el de un select o
         un calendario. Su contenido es el slot del consumidor, que pinta el
         servidor y que Livewire actualiza en cada re-render: dentro de un
         `x-if` se quedaría con lo que había la primera vez que se abrió. --}}
    <template x-teleport="body">
        <div
            data-kore-teleport
            x-ref="dropdown"
            x-show="open"
            x-cloak
            {{-- El panel escucha el teclado por su cuenta: está teleportado a
                 `<body>`, así que los eventos NO burbujean hasta la raíz que
                 lleva el otro `x-on:keydown`. Y la primera flecha mueve el foco
                 real a un item de aquí dentro, de modo que a partir de ese
                 momento el menú se quedaba sordo: ni seguía bajando ni cerraba
                 con Escape. --}}
            x-on:keydown="onKeydown($event)"
            role="menu"
            aria-orientation="vertical"
            {{-- Un `role="menu"` sin nombre se anuncia como «menú» y nada más.
                 El disparador es el slot del consumidor, así que no hay un id
                 fiable al que apuntar con `aria-labelledby`. --}}
            aria-label="{{ $ariaLabel }}"
            class="fixed z-50 kore-panel kore-pop p-1 overflow-y-auto {{ $maxHeightClass }}"
            @if($maxHeight) style="max-height: {{ $maxHeight }}" @endif
        >
            {{ $slot }}
        </div>
    </template>
</div>
